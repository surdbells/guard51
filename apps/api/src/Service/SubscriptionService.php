<?php

declare(strict_types=1);

namespace Guard51\Service;

use Guard51\Entity\InvoiceStatus;
use Guard51\Entity\PaymentMethod;
use Guard51\Entity\Subscription;
use Guard51\Entity\SubscriptionInvoice;
use Guard51\Entity\SubscriptionPlan;
use Guard51\Entity\SubscriptionStatus;
use Guard51\Entity\Tenant;
use Guard51\Entity\TenantType;
use Guard51\Exception\ApiException;
use Guard51\Repository\SubscriptionRepository;
use Guard51\Repository\SubscriptionPlanRepository;
use Guard51\Repository\SubscriptionInvoiceRepository;
use Psr\Log\LoggerInterface;

final class SubscriptionService
{
    public function __construct(
        private readonly SubscriptionRepository $subscriptionRepo,
        private readonly SubscriptionPlanRepository $planRepo,
        private readonly SubscriptionInvoiceRepository $invoiceRepo,
        private readonly PaystackService $paystack,
        private readonly FeatureService $featureService,
        private readonly ZeptoMailService $mailer,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Initialize a Paystack subscription checkout.
     * Returns Paystack authorization URL for frontend redirect.
     */
    public function initializePaystack(Tenant $tenant, string $planId, string $email, string $billingCycle = 'monthly'): array
    {
        $plan = $this->planRepo->findOrFail($planId);
        $this->validatePlanForTenant($plan, $tenant);

        $amount = $billingCycle === 'annual' && $plan->getAnnualPrice()
            ? $plan->getAnnualPriceKobo()
            : $plan->getMonthlyPriceKobo();

        $result = $this->paystack->initializeTransaction($email, $amount, [
            'tenant_id' => $tenant->getId(),
            'plan_id' => $planId,
            'billing_cycle' => $billingCycle,
            'type' => 'subscription',
        ]);

        if (!$result) {
            throw ApiException::validation('Failed to initialize Paystack payment. Please try again.');
        }

        // Create pending subscription
        $subscription = new Subscription();
        $subscription->setTenantId($tenant->getId())
            ->setPlanId($planId)
            ->setBillingCycle($billingCycle)
            ->setAmount($billingCycle === 'annual' ? ($plan->getAnnualPrice() ?? $plan->getMonthlyPrice()) : $plan->getMonthlyPrice())
            ->setPaymentMethod(PaymentMethod::PAYSTACK)
            ->setStatus(SubscriptionStatus::PENDING);

        $this->subscriptionRepo->save($subscription);

        $this->logger->info('Paystack checkout initialized.', [
            'tenant_id' => $tenant->getId(),
            'plan' => $plan->getName(),
            'amount' => $amount,
        ]);

        return [
            'authorization_url' => $result['authorization_url'],
            'reference' => $result['reference'],
            'subscription_id' => $subscription->getId(),
        ];
    }

    /**
     * Verify a Paystack payment and activate subscription.
     */
    public function verifyPaystack(string $reference): Subscription
    {
        $data = $this->paystack->verifyTransaction($reference);

        if (!$data || ($data['status'] ?? '') !== 'success') {
            throw ApiException::validation('Payment verification failed.');
        }

        $metadata = $data['metadata'] ?? [];
        $tenantId = $metadata['tenant_id'] ?? null;
        $planId = $metadata['plan_id'] ?? null;
        $billingCycle = $metadata['billing_cycle'] ?? 'monthly';

        if (!$tenantId || !$planId) {
            throw ApiException::validation('Invalid payment metadata.');
        }

        // Find pending subscription for this tenant
        $subscription = $this->subscriptionRepo->findPendingByTenant($tenantId);
        if (!$subscription) {
            throw ApiException::notFound('No pending subscription found.');
        }

        // Activate
        $now = new \DateTimeImmutable();
        $subscription->activate();
        $subscription->setCurrentPeriodStart($now);
        $subscription->setCurrentPeriodEnd(
            $billingCycle === 'annual' ? $now->modify('+1 year') : $now->modify('+1 month')
        );
        $subscription->setPaystackCustomerCode($data['customer']['customer_code'] ?? null);

        $this->subscriptionRepo->save($subscription);

        // Create invoice
        $this->createInvoice($subscription, InvoiceStatus::PAID, $reference);

        // Sync features
        $plan = $this->planRepo->find($planId);
        if ($plan) {
            $tenant = $this->subscriptionRepo->getEntityManager()
                ->getRepository(\Guard51\Entity\Tenant::class)->find($tenantId);
            if ($tenant) {
                $this->featureService->syncWithPlan($tenantId, $plan->getIncludedModules(), $tenant->getTenantType());
            }
        }

        $this->logger->info('Paystack subscription activated.', [
            'tenant_id' => $tenantId,
            'subscription_id' => $subscription->getId(),
        ]);

        return $subscription;
    }

    /**
     * Initiate a bank transfer subscription. Creates pending subscription + invoice.
     */
    public function initiateBankTransfer(Tenant $tenant, string $planId, string $billingCycle = 'monthly'): Subscription
    {
        $plan = $this->planRepo->findOrFail($planId);
        $this->validatePlanForTenant($plan, $tenant);

        $amount = $billingCycle === 'annual' && $plan->getAnnualPrice()
            ? $plan->getAnnualPrice()
            : $plan->getMonthlyPrice();

        $subscription = new Subscription();
        $subscription->setTenantId($tenant->getId())
            ->setPlanId($planId)
            ->setBillingCycle($billingCycle)
            ->setAmount($amount)
            ->setPaymentMethod(PaymentMethod::BANK_TRANSFER)
            ->setStatus(SubscriptionStatus::PENDING);

        $this->subscriptionRepo->save($subscription);

        // Create pending invoice
        $this->createInvoice($subscription, InvoiceStatus::PENDING);

        $this->logger->info('Bank transfer subscription initiated.', [
            'tenant_id' => $tenant->getId(),
            'plan' => $plan->getName(),
            'amount' => $amount,
        ]);

        return $subscription;
    }

    /**
     * Super admin confirms a bank transfer payment.
     */
    public function confirmBankTransferPayment(
        string $subscriptionId,
        string $confirmedByUserId,
        ?string $transferReference = null,
        ?string $proofUrl = null,
    ): Subscription {
        $subscription = $this->subscriptionRepo->findOrFail($subscriptionId);

        if (!$subscription->isPendingBankTransfer()) {
            throw ApiException::conflict('This subscription is not pending bank transfer confirmation.');
        }

        $subscription->setBankTransferReference($transferReference);
        $subscription->setBankTransferProofUrl($proofUrl);
        $subscription->confirmBankTransferPayment($confirmedByUserId);

        $this->subscriptionRepo->save($subscription);

        // Mark invoice paid
        $invoice = $this->invoiceRepo->findLatestBySubscription($subscription->getId());
        if ($invoice) {
            $invoice->markPaid($confirmedByUserId);
            $invoice->setBankTransferReference($transferReference);
            $invoice->setBankTransferProofUrl($proofUrl);
            $this->invoiceRepo->save($invoice);
        }

        // Sync features
        $plan = $this->planRepo->find($subscription->getPlanId());
        if ($plan) {
            $tenant = $this->subscriptionRepo->getEntityManager()
                ->getRepository(\Guard51\Entity\Tenant::class)->find($subscription->getTenantId());
            if ($tenant) {
                $this->featureService->syncWithPlan($subscription->getTenantId(), $plan->getIncludedModules(), $tenant->getTenantType());
                $tenant->setStatus(\Guard51\Entity\TenantStatus::ACTIVE);
                $this->subscriptionRepo->getEntityManager()->flush();
            }
        }

        $this->logger->info('Bank transfer confirmed.', [
            'subscription_id' => $subscriptionId,
            'confirmed_by' => $confirmedByUserId,
        ]);

        return $subscription;
    }

    /**
     * Cancel a subscription.
     */
    public function cancel(string $subscriptionId, string $reason): Subscription
    {
        $subscription = $this->subscriptionRepo->findOrFail($subscriptionId);
        $subscription->cancel($reason);
        $this->subscriptionRepo->save($subscription);

        $this->logger->info('Subscription cancelled.', [
            'subscription_id' => $subscriptionId,
            'reason' => $reason,
        ]);

        return $subscription;
    }

    /**
     * Get current active subscription for a tenant.
     */
    public function getCurrentSubscription(string $tenantId): ?Subscription
    {
        return $this->subscriptionRepo->findActiveByTenant($tenantId);
    }

    private function validatePlanForTenant(SubscriptionPlan $plan, Tenant $tenant): void
    {
        if (!$plan->isActive()) {
            throw ApiException::validation('This plan is no longer available.');
        }

        if (!$plan->isAvailableForTenantType($tenant->getTenantType())) {
            throw ApiException::validation('This plan is not available for your organization type.');
        }

        if ($plan->isPrivatePlan() && $plan->getPrivateTenantId() !== $tenant->getId()) {
            throw ApiException::validation('This plan is not available for your organization.');
        }
    }

    private function createInvoice(Subscription $subscription, InvoiceStatus $status, ?string $paystackRef = null): SubscriptionInvoice
    {
        $now = new \DateTimeImmutable();
        $invoiceNumber = 'INV-' . strtoupper(substr(md5($subscription->getId() . time()), 0, 8));

        $invoice = new SubscriptionInvoice();
        $invoice->setTenantId($subscription->getTenantId())
            ->setSubscriptionId($subscription->getId())
            ->setInvoiceNumber($invoiceNumber)
            ->setAmount($subscription->getAmount())
            ->setCurrency($subscription->getCurrency())
            ->setPaymentMethod($subscription->getPaymentMethod())
            ->setStatus($status)
            ->setPeriodStart($now)
            ->setPeriodEnd(
                $subscription->getBillingCycle() === 'annual'
                    ? $now->modify('+1 year')
                    : $now->modify('+1 month')
            )
            ->setDueDate($now->modify('+7 days'));

        if ($paystackRef) {
            $invoice->setPaystackReference($paystackRef);
        }

        if ($status === InvoiceStatus::PAID) {
            $invoice->markPaid();
        }

        $this->invoiceRepo->save($invoice);
        return $invoice;
    }
}
