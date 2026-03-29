<?php

declare(strict_types=1);

namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

/**
 * A tenant's subscription to a plan. Supports Paystack auto-charge or manual bank transfer.
 */
#[ORM\Entity]
#[ORM\Table(name: 'subscriptions')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'idx_sub_tenant', columns: ['tenant_id'])]
#[ORM\Index(name: 'idx_sub_status', columns: ['status'])]
#[ORM\Index(name: 'idx_sub_plan', columns: ['plan_id'])]
class Subscription implements TenantAwareInterface
{
    use TenantAwareTrait;
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'plan_id', type: 'string', length: 36)]
    private string $planId;

    #[ORM\Column(name: 'billing_cycle', type: 'string', length: 20)]
    private string $billingCycle = 'monthly';

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private string $amount;

    #[ORM\Column(type: 'string', length: 3, options: ['default' => 'NGN'])]
    private string $currency = 'NGN';

    #[ORM\Column(type: 'string', length: 30, enumType: SubscriptionStatus::class)]
    private SubscriptionStatus $status = SubscriptionStatus::PENDING;

    #[ORM\Column(name: 'payment_method', type: 'string', length: 30, enumType: PaymentMethod::class)]
    private PaymentMethod $paymentMethod = PaymentMethod::PAYSTACK;

    #[ORM\Column(name: 'paystack_subscription_code', type: 'string', length: 200, nullable: true)]
    private ?string $paystackSubscriptionCode = null;

    #[ORM\Column(name: 'paystack_customer_code', type: 'string', length: 200, nullable: true)]
    private ?string $paystackCustomerCode = null;

    #[ORM\Column(name: 'paystack_authorization_code', type: 'string', length: 200, nullable: true)]
    private ?string $paystackAuthorizationCode = null;

    #[ORM\Column(name: 'current_period_start', type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $currentPeriodStart = null;

    #[ORM\Column(name: 'current_period_end', type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $currentPeriodEnd = null;

    #[ORM\Column(name: 'trial_ends_at', type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $trialEndsAt = null;

    #[ORM\Column(name: 'cancelled_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $cancelledAt = null;

    #[ORM\Column(name: 'cancellation_reason', type: 'string', length: 500, nullable: true)]
    private ?string $cancellationReason = null;

    /** For bank transfer: reference provided by the tenant */
    #[ORM\Column(name: 'bank_transfer_reference', type: 'string', length: 200, nullable: true)]
    private ?string $bankTransferReference = null;

    /** For bank transfer: proof of payment file URL */
    #[ORM\Column(name: 'bank_transfer_proof_url', type: 'string', length: 500, nullable: true)]
    private ?string $bankTransferProofUrl = null;

    /** For bank transfer: confirmed by super admin */
    #[ORM\Column(name: 'payment_confirmed_by', type: 'string', length: 36, nullable: true)]
    private ?string $paymentConfirmedBy = null;

    #[ORM\Column(name: 'payment_confirmed_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $paymentConfirmedAt = null;

    public function __construct()
    {
        $this->id = Uuid::uuid4()->toString();
    }

    // ── Getters ──────────────────────────────────────

    public function getId(): string { return $this->id; }
    public function getPlanId(): string { return $this->planId; }
    public function getBillingCycle(): string { return $this->billingCycle; }
    public function getAmount(): string { return $this->amount; }
    public function getCurrency(): string { return $this->currency; }
    public function getStatus(): SubscriptionStatus { return $this->status; }
    public function getPaymentMethod(): PaymentMethod { return $this->paymentMethod; }
    public function getPaystackSubscriptionCode(): ?string { return $this->paystackSubscriptionCode; }
    public function getPaystackCustomerCode(): ?string { return $this->paystackCustomerCode; }
    public function getCurrentPeriodStart(): ?\DateTimeImmutable { return $this->currentPeriodStart; }
    public function getCurrentPeriodEnd(): ?\DateTimeImmutable { return $this->currentPeriodEnd; }
    public function getTrialEndsAt(): ?\DateTimeImmutable { return $this->trialEndsAt; }
    public function getCancelledAt(): ?\DateTimeImmutable { return $this->cancelledAt; }
    public function getCancellationReason(): ?string { return $this->cancellationReason; }
    public function getBankTransferReference(): ?string { return $this->bankTransferReference; }
    public function getBankTransferProofUrl(): ?string { return $this->bankTransferProofUrl; }
    public function getPaymentConfirmedBy(): ?string { return $this->paymentConfirmedBy; }
    public function getPaymentConfirmedAt(): ?\DateTimeImmutable { return $this->paymentConfirmedAt; }

    // ── Setters ──────────────────────────────────────

    public function setPlanId(string $planId): static { $this->planId = $planId; return $this; }
    public function setBillingCycle(string $billingCycle): static { $this->billingCycle = $billingCycle; return $this; }
    public function setAmount(string $amount): static { $this->amount = $amount; return $this; }
    public function setCurrency(string $currency): static { $this->currency = $currency; return $this; }
    public function setStatus(SubscriptionStatus $status): static { $this->status = $status; return $this; }
    public function setPaymentMethod(PaymentMethod $paymentMethod): static { $this->paymentMethod = $paymentMethod; return $this; }
    public function setPaystackSubscriptionCode(?string $code): static { $this->paystackSubscriptionCode = $code; return $this; }
    public function setPaystackCustomerCode(?string $code): static { $this->paystackCustomerCode = $code; return $this; }
    public function setPaystackAuthorizationCode(?string $code): static { $this->paystackAuthorizationCode = $code; return $this; }
    public function setCurrentPeriodStart(?\DateTimeImmutable $dt): static { $this->currentPeriodStart = $dt; return $this; }
    public function setCurrentPeriodEnd(?\DateTimeImmutable $dt): static { $this->currentPeriodEnd = $dt; return $this; }
    public function setTrialEndsAt(?\DateTimeImmutable $dt): static { $this->trialEndsAt = $dt; return $this; }
    public function setBankTransferReference(?string $ref): static { $this->bankTransferReference = $ref; return $this; }
    public function setBankTransferProofUrl(?string $url): static { $this->bankTransferProofUrl = $url; return $this; }

    // ── Business Logic ───────────────────────────────

    public function activate(): static
    {
        $this->status = SubscriptionStatus::ACTIVE;
        return $this;
    }

    public function cancel(string $reason): static
    {
        $this->status = SubscriptionStatus::CANCELLED;
        $this->cancelledAt = new \DateTimeImmutable();
        $this->cancellationReason = $reason;
        return $this;
    }

    public function confirmBankTransferPayment(string $confirmedByUserId): static
    {
        $this->paymentConfirmedBy = $confirmedByUserId;
        $this->paymentConfirmedAt = new \DateTimeImmutable();
        $this->status = SubscriptionStatus::ACTIVE;
        $now = new \DateTimeImmutable();
        $this->currentPeriodStart = $now;
        $this->currentPeriodEnd = $this->billingCycle === 'annual'
            ? $now->modify('+1 year')
            : $now->modify('+1 month');
        return $this;
    }

    public function isInTrial(): bool
    {
        if ($this->trialEndsAt === null) return false;
        return $this->trialEndsAt > new \DateTimeImmutable();
    }

    public function isExpired(): bool
    {
        if ($this->currentPeriodEnd === null) return false;
        return $this->currentPeriodEnd < new \DateTimeImmutable();
    }

    public function isPendingBankTransfer(): bool
    {
        return $this->paymentMethod === PaymentMethod::BANK_TRANSFER
            && $this->status === SubscriptionStatus::PENDING
            && $this->paymentConfirmedAt === null;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenantId,
            'plan_id' => $this->planId,
            'billing_cycle' => $this->billingCycle,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'status' => $this->status->value,
            'payment_method' => $this->paymentMethod->value,
            'current_period_start' => $this->currentPeriodStart?->format('Y-m-d'),
            'current_period_end' => $this->currentPeriodEnd?->format('Y-m-d'),
            'trial_ends_at' => $this->trialEndsAt?->format('Y-m-d'),
            'is_in_trial' => $this->isInTrial(),
            'is_pending_bank_transfer' => $this->isPendingBankTransfer(),
            'cancelled_at' => $this->cancelledAt?->format(\DateTimeInterface::ATOM),
            'created_at' => $this->createdAt?->format(\DateTimeInterface::ATOM),
        ];
    }
}
