<?php
declare(strict_types=1);
namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'invoice_items')]
#[ORM\Index(name: 'idx_ii_invoice', columns: ['invoice_id'])]
class InvoiceItem
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $invoiceId;

    #[ORM\Column(type: 'string', length: 500)]
    private string $description;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $quantity;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $unitPrice;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private string $amount;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $isTaxable = true;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct() { $this->id = Uuid::uuid4()->toString(); $this->createdAt = new \DateTimeImmutable(); }

    public function getId(): string { return $this->id; }
    public function getInvoiceId(): string { return $this->invoiceId; }
    public function getAmount(): float { return (float) $this->amount; }

    public function setInvoiceId(string $id): static { $this->invoiceId = $id; return $this; }
    public function setDescription(string $d): static { $this->description = $d; return $this; }
    public function setQuantity(float $q): static { $this->quantity = (string) $q; return $this; }
    public function setUnitPrice(float $p): static { $this->unitPrice = (string) $p; return $this; }
    public function setIsTaxable(bool $v): static { $this->isTaxable = $v; return $this; }

    public function calculateAmount(): static { $this->amount = (string) round((float) $this->quantity * (float) $this->unitPrice, 2); return $this; }

    public function toArray(): array
    {
        return [
            'id' => $this->id, 'invoice_id' => $this->invoiceId, 'description' => $this->description,
            'quantity' => (float) $this->quantity, 'unit_price' => (float) $this->unitPrice,
            'amount' => $this->getAmount(), 'is_taxable' => $this->isTaxable,
        ];
    }
}
