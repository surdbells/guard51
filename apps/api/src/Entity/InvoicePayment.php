<?php
declare(strict_types=1);
namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'invoice_payments')]
#[ORM\Index(name: 'idx_ip_invoice', columns: ['invoice_id'])]
class InvoicePayment
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'invoice_id', type: 'string', length: 36)]
    private string $invoiceId;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private string $amount;

    #[ORM\Column(name: 'payment_method', type: 'string', length: 20, enumType: PaymentMethod::class)]
    private PaymentMethod $paymentMethod;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $reference = null;

    #[ORM\Column(name: 'proof_url', type: 'string', length: 500, nullable: true)]
    private ?string $proofUrl = null;

    #[ORM\Column(name: 'received_by', type: 'string', length: 36)]
    private string $receivedBy;

    #[ORM\Column(name: 'payment_date', type: 'datetime_immutable')]
    private \DateTimeImmutable $paymentDate;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct() { $this->id = Uuid::uuid4()->toString(); $this->paymentDate = new \DateTimeImmutable(); $this->createdAt = new \DateTimeImmutable(); }

    public function getId(): string { return $this->id; }
    public function getInvoiceId(): string { return $this->invoiceId; }
    public function getAmount(): float { return (float) $this->amount; }

    public function setInvoiceId(string $id): static { $this->invoiceId = $id; return $this; }
    public function setAmount(float $a): static { $this->amount = (string) $a; return $this; }
    public function setPaymentMethod(PaymentMethod $m): static { $this->paymentMethod = $m; return $this; }
    public function setReference(?string $r): static { $this->reference = $r; return $this; }
    public function setProofUrl(?string $u): static { $this->proofUrl = $u; return $this; }
    public function setReceivedBy(string $id): static { $this->receivedBy = $id; return $this; }
    public function setPaymentDate(\DateTimeImmutable $d): static { $this->paymentDate = $d; return $this; }
    public function setNotes(?string $n): static { $this->notes = $n; return $this; }

    public function toArray(): array
    {
        return [
            'id' => $this->id, 'invoice_id' => $this->invoiceId, 'amount' => $this->getAmount(),
            'payment_method' => $this->paymentMethod->value, 'payment_method_label' => $this->paymentMethod->label(),
            'reference' => $this->reference, 'proof_url' => $this->proofUrl, 'received_by' => $this->receivedBy,
            'payment_date' => $this->paymentDate->format(\DateTimeInterface::ATOM), 'notes' => $this->notes,
        ];
    }
}
