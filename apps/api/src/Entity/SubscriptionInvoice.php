<?php

declare(strict_types=1);

namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

/**
 * Tracks subscription payment history. One per billing cycle.
 */
#[ORM\Entity]
#[ORM\Table(name: 'subscription_invoices')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'idx_si_tenant', columns: ['tenant_id'])]
#[ORM\Index(name: 'idx_si_subscription', columns: ['subscription_id'])]
#[ORM\Index(name: 'idx_si_status', columns: ['status'])]
class SubscriptionInvoice implements TenantAwareInterface
{
    use TenantAwareTrait;
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'subscription_id', type: 'string', length: 36)]
    private string $subscriptionId;

    #[ORM\Column(name: 'invoice_number', type: 'string', length: 50)]
    private string $invoiceNumber;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private string $amount;

    #[ORM\Column(type: 'string', length: 3, options: ['default' => 'NGN'])]
    private string $currency = 'NGN';

    #[ORM\Column(type: 'string', length: 30, enumType: InvoiceStatus::class)]
    private InvoiceStatus $status = InvoiceStatus::PENDING;

    #[ORM\Column(name: 'payment_method', type: 'string', length: 30, enumType: PaymentMethod::class)]
    private PaymentMethod $paymentMethod;

    #[ORM\Column(name: 'paystack_reference', type: 'string', length: 200, nullable: true)]
    private ?string $paystackReference = null;

    #[ORM\Column(name: 'bank_transfer_reference', type: 'string', length: 200, nullable: true)]
    private ?string $bankTransferReference = null;

    #[ORM\Column(name: 'bank_transfer_proof_url', type: 'string', length: 500, nullable: true)]
    private ?string $bankTransferProofUrl = null;

    #[ORM\Column(name: 'period_start', type: 'date_immutable')]
    private \DateTimeImmutable $periodStart;

    #[ORM\Column(name: 'period_end', type: 'date_immutable')]
    private \DateTimeImmutable $periodEnd;

    #[ORM\Column(name: 'due_date', type: 'date_immutable')]
    private \DateTimeImmutable $dueDate;

    #[ORM\Column(name: 'paid_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $paidAt = null;

    #[ORM\Column(name: 'confirmed_by', type: 'string', length: 36, nullable: true)]
    private ?string $confirmedBy = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    public function __construct()
    {
        $this->id = Uuid::uuid4()->toString();
    }

    public function getId(): string { return $this->id; }
    public function getSubscriptionId(): string { return $this->subscriptionId; }
    public function getInvoiceNumber(): string { return $this->invoiceNumber; }
    public function getAmount(): string { return $this->amount; }
    public function getCurrency(): string { return $this->currency; }
    public function getStatus(): InvoiceStatus { return $this->status; }
    public function getPaymentMethod(): PaymentMethod { return $this->paymentMethod; }
    public function getPaystackReference(): ?string { return $this->paystackReference; }
    public function getBankTransferReference(): ?string { return $this->bankTransferReference; }
    public function getPeriodStart(): \DateTimeImmutable { return $this->periodStart; }
    public function getPeriodEnd(): \DateTimeImmutable { return $this->periodEnd; }
    public function getDueDate(): \DateTimeImmutable { return $this->dueDate; }
    public function getPaidAt(): ?\DateTimeImmutable { return $this->paidAt; }

    public function setSubscriptionId(string $id): static { $this->subscriptionId = $id; return $this; }
    public function setInvoiceNumber(string $num): static { $this->invoiceNumber = $num; return $this; }
    public function setAmount(string $amount): static { $this->amount = $amount; return $this; }
    public function setCurrency(string $currency): static { $this->currency = $currency; return $this; }
    public function setStatus(InvoiceStatus $status): static { $this->status = $status; return $this; }
    public function setPaymentMethod(PaymentMethod $method): static { $this->paymentMethod = $method; return $this; }
    public function setPaystackReference(?string $ref): static { $this->paystackReference = $ref; return $this; }
    public function setBankTransferReference(?string $ref): static { $this->bankTransferReference = $ref; return $this; }
    public function setBankTransferProofUrl(?string $url): static { $this->bankTransferProofUrl = $url; return $this; }
    public function setPeriodStart(\DateTimeImmutable $dt): static { $this->periodStart = $dt; return $this; }
    public function setPeriodEnd(\DateTimeImmutable $dt): static { $this->periodEnd = $dt; return $this; }
    public function setDueDate(\DateTimeImmutable $dt): static { $this->dueDate = $dt; return $this; }
    public function setNotes(?string $notes): static { $this->notes = $notes; return $this; }

    public function markPaid(?string $confirmedBy = null): static
    {
        $this->status = InvoiceStatus::PAID;
        $this->paidAt = new \DateTimeImmutable();
        $this->confirmedBy = $confirmedBy;
        return $this;
    }

    public function markFailed(): static
    {
        $this->status = InvoiceStatus::FAILED;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenantId,
            'subscription_id' => $this->subscriptionId,
            'invoice_number' => $this->invoiceNumber,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'status' => $this->status->value,
            'payment_method' => $this->paymentMethod->value,
            'period_start' => $this->periodStart->format('Y-m-d'),
            'period_end' => $this->periodEnd->format('Y-m-d'),
            'due_date' => $this->dueDate->format('Y-m-d'),
            'paid_at' => $this->paidAt?->format(\DateTimeInterface::ATOM),
            'created_at' => $this->createdAt?->format(\DateTimeInterface::ATOM),
        ];
    }
}
