<?php
declare(strict_types=1);
namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'visitors')]
#[ORM\Index(name: 'idx_vis_tenant', columns: ['tenant_id'])]
#[ORM\Index(name: 'idx_vis_site', columns: ['site_id'])]
class Visitor implements TenantAwareInterface
{
    use TenantAwareTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'site_id', type: 'string', length: 36)]
    private string $siteId;

    #[ORM\Column(name: 'first_name', type: 'string', length: 100)]
    private string $firstName;

    #[ORM\Column(name: 'last_name', type: 'string', length: 100)]
    private string $lastName;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(type: 'string', length: 200, nullable: true)]
    private ?string $company = null;

    #[ORM\Column(type: 'string', length: 300)]
    private string $purpose;

    #[ORM\Column(name: 'host_name', type: 'string', length: 200, nullable: true)]
    private ?string $hostName = null;

    #[ORM\Column(name: 'id_type', type: 'string', length: 20, nullable: true, enumType: IdDocType::class)]
    private ?IdDocType $idType = null;

    #[ORM\Column(name: 'id_number', type: 'string', length: 50, nullable: true)]
    private ?string $idNumber = null;

    #[ORM\Column(name: 'photo_url', type: 'string', length: 500, nullable: true)]
    private ?string $photoUrl = null;

    #[ORM\Column(name: 'vehicle_plate', type: 'string', length: 20, nullable: true)]
    private ?string $vehiclePlate = null;

    #[ORM\Column(type: 'string', length: 15, enumType: VisitorStatus::class)]
    private VisitorStatus $status = VisitorStatus::CHECKED_IN;

    #[ORM\Column(name: 'check_in_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $checkInAt;

    #[ORM\Column(name: 'check_out_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $checkOutAt = null;

    #[ORM\Column(name: 'checked_in_by', type: 'string', length: 36)]
    private string $checkedInBy;

    #[ORM\Column(name: 'checked_out_by', type: 'string', length: 36, nullable: true)]
    private ?string $checkedOutBy = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct() { $this->id = Uuid::uuid4()->toString(); $this->checkInAt = new \DateTimeImmutable(); $this->createdAt = new \DateTimeImmutable(); }

    public function getId(): string { return $this->id; }
    public function getStatus(): VisitorStatus { return $this->status; }
    public function setSiteId(string $id): static { $this->siteId = $id; return $this; }
    public function setFirstName(string $n): static { $this->firstName = $n; return $this; }
    public function setLastName(string $n): static { $this->lastName = $n; return $this; }
    public function setPhone(?string $p): static { $this->phone = $p; return $this; }
    public function setEmail(?string $e): static { $this->email = $e; return $this; }
    public function setCompany(?string $c): static { $this->company = $c; return $this; }
    public function setPurpose(string $p): static { $this->purpose = $p; return $this; }
    public function setHostName(?string $n): static { $this->hostName = $n; return $this; }
    public function setIdType(?IdDocType $t): static { $this->idType = $t; return $this; }
    public function setIdNumber(?string $n): static { $this->idNumber = $n; return $this; }
    public function setPhotoUrl(?string $u): static { $this->photoUrl = $u; return $this; }
    public function setVehiclePlate(?string $p): static { $this->vehiclePlate = $p; return $this; }
    public function setCheckedInBy(string $id): static { $this->checkedInBy = $id; return $this; }
    public function setNotes(?string $n): static { $this->notes = $n; return $this; }

    public function checkOut(string $guardId): static
    {
        $this->status = VisitorStatus::CHECKED_OUT;
        $this->checkOutAt = new \DateTimeImmutable();
        $this->checkedOutBy = $guardId;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id, 'tenant_id' => $this->tenantId, 'site_id' => $this->siteId,
            'first_name' => $this->firstName, 'last_name' => $this->lastName, 'full_name' => "{$this->firstName} {$this->lastName}",
            'phone' => $this->phone, 'email' => $this->email, 'company' => $this->company,
            'purpose' => $this->purpose, 'host_name' => $this->hostName,
            'id_type' => $this->idType?->value, 'id_number' => $this->idNumber,
            'photo_url' => $this->photoUrl, 'vehicle_plate' => $this->vehiclePlate,
            'status' => $this->status->value, 'status_label' => $this->status->label(),
            'check_in_at' => $this->checkInAt->format(\DateTimeInterface::ATOM),
            'check_out_at' => $this->checkOutAt?->format(\DateTimeInterface::ATOM),
            'checked_in_by' => $this->checkedInBy, 'checked_out_by' => $this->checkedOutBy,
            'notes' => $this->notes, 'created_at' => $this->createdAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
