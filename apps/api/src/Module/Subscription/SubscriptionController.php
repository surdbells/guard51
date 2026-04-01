<?php

declare(strict_types=1);

namespace Guard51\Module\Subscription;

use Guard51\Exception\ApiException;
use Guard51\Helper\JsonResponse;
use Guard51\Repository\SubscriptionInvoiceRepository;
use Guard51\Repository\SubscriptionPlanRepository;
use Guard51\Repository\SubscriptionRepository;
use Guard51\Repository\TenantRepository;
use Guard51\Service\PaystackService;
use Guard51\Service\SubscriptionService;
use Guard51\Service\ValidationService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

final class SubscriptionController
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService,
        private readonly PaystackService $paystackService,
        private readonly SubscriptionRepository $subscriptionRepo,
        private readonly SubscriptionPlanRepository $planRepo,
        private readonly SubscriptionInvoiceRepository $invoiceRepo,
        private readonly TenantRepository $tenantRepo,
        private readonly ValidationService $validator,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * GET /api/v1/subscriptions/current — Get current subscription
     */
    public function current(Request $request, Response $response): Response
    {
        $tenantId = $request->getAttribute('tenant_id');
        $subscription = $this->subscriptionService->getCurrentSubscription($tenantId);

        if (!$subscription) {
            return JsonResponse::success($response, [
                'subscription' => null,
                'message' => 'No active subscription.',
            ]);
        }

        $plan = $this->planRepo->find($subscription->getPlanId());

        return JsonResponse::success($response, [
            'subscription' => $subscription->toArray(),
            'plan' => $plan?->toArray(),
        ]);
    }

    /**
     * POST /api/v1/subscriptions/initialize — Start Paystack checkout
     */
    public function initialize(Request $request, Response $response): Response
    {
        $tenantId = $request->getAttribute('tenant_id');
        $tenant = $this->tenantRepo->findOrFail($tenantId);
        $body = (array) $request->getParsedBody();

        $planId = $body['plan_id'] ?? '';
        $billingCycle = $body['billing_cycle'] ?? 'monthly';

        if (empty($planId)) {
            throw ApiException::validation('Plan ID is required.');
        }

        $result = $this->subscriptionService->initializePaystack(
            $tenant, $planId, $tenant->getEmail() ?? '', $billingCycle
        );

        return JsonResponse::success($response, $result);
    }

    /**
     * POST /api/v1/subscriptions/verify — Verify Paystack payment
     */
    public function verify(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();
        $reference = $body['reference'] ?? '';

        if (empty($reference)) {
            throw ApiException::validation('Payment reference is required.');
        }

        $subscription = $this->subscriptionService->verifyPaystack($reference);

        return JsonResponse::success($response, [
            'subscription' => $subscription->toArray(),
            'message' => 'Subscription activated successfully.',
        ]);
    }

    /**
     * POST /api/v1/subscriptions/webhook — Paystack webhook
     */
    public function webhook(Request $request, Response $response): Response
    {
        $payload = (string) $request->getBody();
        $signature = $request->getHeaderLine('x-paystack-signature');

        if (!$this->paystackService->validateWebhook($payload, $signature)) {
            $this->logger->warning('Paystack webhook: invalid signature.');
            return JsonResponse::error($response, 'Invalid signature.', 401);
        }

        $event = json_decode($payload, true);
        $eventType = $event['event'] ?? '';
        $data = $event['data'] ?? [];

        $this->logger->info('Paystack webhook received.', ['event' => $eventType, 'reference' => $data['reference'] ?? '']);

        try {
            switch ($eventType) {
                case 'charge.success':
                    if (!empty($data['reference'])) {
                        $this->subscriptionService->verifyPaystack($data['reference']);
                        $this->logger->info('Paystack charge.success processed.', ['reference' => $data['reference']]);
                    }
                    break;

                case 'subscription.not_renew':
                case 'subscription.disable':
                    $subCode = $data['subscription_code'] ?? '';
                    if ($subCode) {
                        $sub = $this->subscriptionRepo->findByPaystackCode($subCode);
                        if ($sub) {
                            $sub->setStatus(\Guard51\Entity\SubscriptionStatus::CANCELLED);
                            $sub->setCancelledAt(new \DateTimeImmutable());
                            $sub->setCancellationReason('Paystack: ' . $eventType);
                            $this->subscriptionRepo->save($sub);
                        }
                    }
                    break;

                case 'invoice.payment_failed':
                    $subCode = $data['subscription']['subscription_code'] ?? ($data['subscription_code'] ?? '');
                    if ($subCode) {
                        $sub = $this->subscriptionRepo->findByPaystackCode($subCode);
                        if ($sub) {
                            $sub->setStatus(\Guard51\Entity\SubscriptionStatus::PAST_DUE);
                            $this->subscriptionRepo->save($sub);
                        }
                    }
                    break;

                default:
                    $this->logger->info('Unhandled Paystack webhook.', ['event' => $eventType]);
            }
        } catch (\Throwable $e) {
            $this->logger->error('Paystack webhook error.', ['event' => $eventType, 'error' => $e->getMessage()]);
        }

        return JsonResponse::success($response, ['message' => 'Webhook processed.']);
    }

    /**
     * POST /api/v1/subscriptions/bank-transfer — Initiate bank transfer
     */
    public function bankTransfer(Request $request, Response $response): Response
    {
        $tenantId = $request->getAttribute('tenant_id');
        $tenant = $this->tenantRepo->findOrFail($tenantId);
        $body = (array) $request->getParsedBody();

        $planId = $body['plan_id'] ?? '';
        $billingCycle = $body['billing_cycle'] ?? 'monthly';

        if (empty($planId)) {
            throw ApiException::validation('Plan ID is required.');
        }

        $subscription = $this->subscriptionService->initiateBankTransfer($tenant, $planId, $billingCycle);

        return JsonResponse::success($response, [
            'subscription' => $subscription->toArray(),
            'message' => 'Bank transfer subscription initiated. Please transfer funds and await confirmation.',
        ], 201);
    }

    /**
     * POST /api/v1/admin/subscriptions/{id}/confirm-payment — Super admin confirms bank transfer
     */
    public function confirmPayment(Request $request, Response $response): Response
    {
        $subscriptionId = $request->getAttribute('id') ?? '';
        $userId = $request->getAttribute('user_id');
        $body = (array) $request->getParsedBody();

        $subscription = $this->subscriptionService->confirmBankTransferPayment(
            $subscriptionId,
            $userId,
            $body['transfer_reference'] ?? null,
            $body['proof_url'] ?? null,
        );

        return JsonResponse::success($response, [
            'subscription' => $subscription->toArray(),
            'message' => 'Payment confirmed. Subscription activated.',
        ]);
    }

    /**
     * POST /api/v1/subscriptions/cancel — Cancel subscription
     */
    public function cancel(Request $request, Response $response): Response
    {
        $tenantId = $request->getAttribute('tenant_id');
        $body = (array) $request->getParsedBody();
        $reason = $body['reason'] ?? 'No reason provided';

        $subscription = $this->subscriptionService->getCurrentSubscription($tenantId);
        if (!$subscription) {
            throw ApiException::notFound('No active subscription to cancel.');
        }

        $subscription = $this->subscriptionService->cancel($subscription->getId(), $reason);

        return JsonResponse::success($response, [
            'subscription' => $subscription->toArray(),
            'message' => 'Subscription cancelled.',
        ]);
    }

    /**
     * POST /api/v1/subscriptions/upgrade — Upgrade to a new plan
     */
    public function upgrade(Request $request, Response $response): Response
    {
        $tenantId = $request->getAttribute('tenant_id');
        $tenant = $this->tenantRepo->findOrFail($tenantId);
        $body = (array) $request->getParsedBody();

        $newPlanId = $body['plan_id'] ?? '';
        $billingCycle = $body['billing_cycle'] ?? 'monthly';
        $paymentMethod = $body['payment_method'] ?? 'paystack';

        if (empty($newPlanId)) {
            throw ApiException::validation('Plan ID is required.');
        }

        // Cancel current subscription if active
        $current = $this->subscriptionService->getCurrentSubscription($tenantId);
        if ($current) {
            $this->subscriptionService->cancel($current->getId(), 'Upgraded to new plan');
        }

        // Initialize new subscription based on payment method
        if ($paymentMethod === 'bank_transfer') {
            $subscription = $this->subscriptionService->initiateBankTransfer($tenant, $newPlanId, $billingCycle);
            return JsonResponse::success($response, [
                'subscription' => $subscription->toArray(),
                'message' => 'Upgrade initiated via bank transfer. Please transfer funds and await confirmation.',
            ]);
        }

        $result = $this->subscriptionService->initializePaystack(
            $tenant, $newPlanId, $tenant->getEmail() ?? '', $billingCycle
        );

        return JsonResponse::success($response, [
            'authorization_url' => $result['authorization_url'],
            'subscription_id' => $result['subscription_id'],
            'message' => 'Upgrade initiated. Complete payment to activate.',
        ]);
    }

    /**
     * GET /api/v1/subscriptions/invoices — Tenant's subscription invoices
     */
    public function invoices(Request $request, Response $response): Response
    {
        $tenantId = $request->getAttribute('tenant_id');
        $invoices = $this->invoiceRepo->findByTenant($tenantId);

        return JsonResponse::success($response, [
            'invoices' => array_map(fn($i) => $i->toArray(), $invoices),
        ]);
    }

    /**
     * GET /api/v1/admin/subscriptions/pending — Pending bank transfers (super admin)
     */
    public function pendingTransfers(Request $request, Response $response): Response
    {
        $pending = $this->subscriptionRepo->findPendingBankTransfers();
        $result = [];

        foreach ($pending as $sub) {
            $data = $sub->toArray();
            $tenant = $this->tenantRepo->find($sub->getTenantId());
            $plan = $this->planRepo->find($sub->getPlanId());
            $data['tenant_name'] = $tenant?->getName();
            $data['plan_name'] = $plan?->getName();
            $result[] = $data;
        }

        return JsonResponse::success($response, [
            'pending' => $result,
            'count' => count($result),
        ]);
    }
}
