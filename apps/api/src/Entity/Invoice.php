<?php
declare(strict_types=1);
namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'invoices')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'idx_inv_tenant', columns: ['tenant_id'])]
#[ORM\Index(name: 'idx_inv_client', columns: ['client_id'])]
#[ORM\Index(name: 'idx_inv_status', columns: ['status'])]
class Invoice implements TenantAwareInterface
{
    use TenantAwareTrait;
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $clientId;

    #[ORM\Column(type: 'string', length: 50)]
    private string $invoiceNumber;

    #[ORM\Column(type: 'string', length: 10, enumType: InvoiceType::class)]
    private InvoiceType $type = InvoiceType::INVOICE;

    #[ORM\Column(type: 'string', length: 20, enumType: InvoiceStatus::class)]
    private InvoiceStatus $status = InvoiceStatus::DRAFT;

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $issueDate;

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $dueDate;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private string $subtotal = '0';

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2)]
    private string $taxRate = '7.50';

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private string $taxAmount = '0';

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private string $total = '0';

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private string $amountPaid = '0';

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private string $balanceDue = '0';

    #[ORM\Column(type: 'string', length: 3)]
    private string $currency = 'NGN';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $paymentTerms = null;

    #[ORM\Column(type: 'string', length: 36)]
    private string $createdBy;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $sentAt = null;

    public function __construct()
    {
        $this->id = Uuid::uuid4()->toString();
        $this->issueDate = new \DateTimeImmutable();
        $this->dueDate = new \DateTimeImmutable('+30 days');
    }

    public function getId(): string { return $this->id; }
    public function getClientId(): string { return $this->clientId; }
    public function getInvoiceNumber(): string { return $this->invoiceNumber; }
    public function getType(): InvoiceType { return $this->type; }
    public function getStatus(): InvoiceStatus { return $this->status; }
    public function getSubtotal(): float { return (float) $this->subtotal; }
    public function getTotal(): float { return (float) $this->total; }
    public function getAmountPaid(): float { return (float) $this->amountPaid; }
    public function getBalanceDue(): float { return (float) $this->balanceDue; }

    public function setClientId(string $id): static { $this->clientId = $id; return $this; }
    public function setInvoiceNumber(string $n): static { $this->invoiceNumber = $n; return $this; }
    public function setType(InvoiceType $t): static { $this->type = $t; return $this; }
    public function setStatus(InvoiceStatus $s): static { $this->status = $s; return $this; }
    public function setIssueDate(\DateTimeImmutable $d): static { $this->issueDate = $d; return $this; }
    public function setDueDate(\DateTimeImmutable $d): static { $this->dueDate = $d; return $this; }
    public function setTaxRate(float $r): static { $this->taxRate = (string) $r; return $this; }
    public function setCurrency(string $c): static { $this->currency = $c; return $this; }
    public function setNotes(?string $n): static { $this->notes = $n; return $this; }
    public function setPaymentTerms(?string $t): static { $this->paymentTerms = $t; return $this; }
    public function setCreatedBy(string $id): static { $this->createdBy = $id; return $this; }

    public function calculateTotals(float $subtotal): static
    {
        $this->subtotal = (string) $subtotal;
        $this->taxAmount = (string) round($subtotal * (float) $this->taxRate / 100, 2);
        $this->total = (string) round($subtotal + (float) $this->taxAmount, 2);
        $this->balanceDue = (string) round((float) $this->total - (float) $this->amountPaid, 2);
        return $this;
    }

    public function recordPayment(float $amount): static
    {
        $this->amountPaid = (string) round((float) $this->amountPaid + $amount, 2);
        $this->balanceDue = (string) round((float) $this->total - (float) $this->amountPaid, 2);
        if ((float) $this->balanceDue <= 0) { $this->status = InvoiceStatus::PAID; }
        elseif ((float) $this->amountPaid > 0) { $this->status = InvoiceStatus::PARTIALLY_PAID; }
        return $this;
    }

    public function send(): static { $this->status = InvoiceStatus::SENT; $this->sentAt = new \DateTimeImmutable(); return $this; }
    public function markViewed(): static { $this->status = InvoiceStatus::VIEWED; return $this; }
    public function markOverdue(): static { $this->status = InvoiceStatus::OVERDUE; return $this; }
    public function cancel(): static { $this->status = InvoiceStatus::CANCELLED; return $this; }

    public function convertEstimateToInvoice(): static
    {
        if ($this->type !== InvoiceType::ESTIMATE) return $this;
        $this->type = InvoiceType::INVOICE;
        $this->status = InvoiceStatus::DRAFT;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id, 'tenant_id' => $this->tenantId, 'client_id' => $this->clientId,
            'invoice_number' => $this->invoiceNumber, 'type' => $this->type->value, 'type_label' => $this->type->label(),
            'status' => $this->status->value, 'status_label' => $this->status->label(), 'is_active' => $this->status->isActive(),
            'issue_date' => $this->issueDate->format('Y-m-d'), 'due_date' => $this->dueDate->format('Y-m-d'),
            'subtotal' => $this->getSubtotal(), 'tax_rate' => (float) $this->taxRate, 'tax_amount' => (float) $this->taxAmount,
            'total' => $this->getTotal(), 'amount_paid' => $this->getAmountPaid(), 'balance_due' => $this->getBalanceDue(),
            'currency' => $this->currency, 'notes' => $this->notes, 'payment_terms' => $this->paymentTerms,
            'created_by' => $this->createdBy, 'sent_at' => $this->sentAt?->format(\DateTimeInterface::ATOM),
            'created_at' => $this->createdAt?->format(\DateTimeInterface::ATOM),
        ];
    }
}
