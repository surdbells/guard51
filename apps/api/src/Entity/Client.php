<?php

declare(strict_types=1);

namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'clients')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'idx_client_tenant', columns: ['tenant_id'])]
#[ORM\Index(name: 'idx_client_status', columns: ['status'])]
class Client implements TenantAwareInterface
{
    use TenantAwareTrait;
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 200)]
    private string $companyName;

    #[ORM\Column(type: 'string', length: 200)]
    private string $contactName;

    #[ORM\Column(type: 'string', length: 255)]
    private string $contactEmail;

    #[ORM\Column(type: 'string', length: 50)]
    private string $contactPhone;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $address = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $state = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $contractStart = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $contractEnd = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $billingRate = null;

    #[ORM\Column(type: 'string', length: 20, enumType: BillingType::class, nullable: true)]
    private ?BillingType $billingType = null;

    #[ORM\Column(type: 'string', length: 20, enumType: ClientStatus::class)]
    private ClientStatus $status = ClientStatus::ACTIVE;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    public function __construct() { $this->id = Uuid::uuid4()->toString(); }

    public function getId(): string { return $this->id; }
    public function getCompanyName(): string { return $this->companyName; }
    public function getContactName(): string { return $this->contactName; }
    public function getContactEmail(): string { return $this->contactEmail; }
    public function getContactPhone(): string { return $this->contactPhone; }
    public function getAddress(): ?string { return $this->address; }
    public function getCity(): ?string { return $this->city; }
    public function getState(): ?string { return $this->state; }
    public function getContractStart(): ?\DateTimeImmutable { return $this->contractStart; }
    public function getContractEnd(): ?\DateTimeImmutable { return $this->contractEnd; }
    public function getBillingRate(): ?float { return $this->billingRate !== null ? (float) $this->billingRate : null; }
    public function getBillingType(): ?BillingType { return $this->billingType; }
    public function getStatus(): ClientStatus { return $this->status; }
    public function getNotes(): ?string { return $this->notes; }

    public function setCompanyName(string $n): static { $this->companyName = $n; return $this; }
    public function setContactName(string $n): static { $this->contactName = $n; return $this; }
    public function setContactEmail(string $e): static { $this->contactEmail = $e; return $this; }
    public function setContactPhone(string $p): static { $this->contactPhone = $p; return $this; }
    public function setAddress(?string $a): static { $this->address = $a; return $this; }
    public function setCity(?string $c): static { $this->city = $c; return $this; }
    public function setState(?string $s): static { $this->state = $s; return $this; }
    public function setContractStart(?\DateTimeImmutable $d): static { $this->contractStart = $d; return $this; }
    public function setContractEnd(?\DateTimeImmutable $d): static { $this->contractEnd = $d; return $this; }
    public function setBillingRate(?float $r): static { $this->billingRate = $r !== null ? (string) $r : null; return $this; }
    public function setBillingType(?BillingType $t): static { $this->billingType = $t; return $this; }
    public function setStatus(ClientStatus $s): static { $this->status = $s; return $this; }
    public function setNotes(?string $n): static { $this->notes = $n; return $this; }

    public function toArray(): array
    {
        return [
            'id' => $this->id, 'tenant_id' => $this->tenantId,
            'company_name' => $this->companyName, 'contact_name' => $this->contactName,
            'contact_email' => $this->contactEmail, 'contact_phone' => $this->contactPhone,
            'address' => $this->address, 'city' => $this->city, 'state' => $this->state,
            'contract_start' => $this->contractStart?->format('Y-m-d'),
            'contract_end' => $this->contractEnd?->format('Y-m-d'),
            'billing_rate' => $this->getBillingRate(),
            'billing_type' => $this->billingType?->value,
            'status' => $this->status->value, 'status_label' => $this->status->label(),
            'notes' => $this->notes,
            'created_at' => $this->createdAt?->format(\DateTimeInterface::ATOM),
            'updated_at' => $this->updatedAt?->format(\DateTimeInterface::ATOM),
        ];
    }
}
