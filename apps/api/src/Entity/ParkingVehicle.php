<?php
declare(strict_types=1);
namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'parking_vehicles')]
#[ORM\Index(name: 'idx_pkv_tenant', columns: ['tenant_id'])]
#[ORM\Index(name: 'idx_pkv_site', columns: ['site_id'])]
class ParkingVehicle implements TenantAwareInterface
{
    use TenantAwareTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'site_id', type: 'string', length: 36)]
    private string $siteId;

    #[ORM\Column(name: 'parking_lot_id', type: 'string', length: 36, nullable: true)]
    private ?string $parkingLotId = null;

    #[ORM\Column(name: 'plate_number', type: 'string', length: 20)]
    private string $plateNumber;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $make = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $model = null;

    #[ORM\Column(type: 'string', length: 30, nullable: true)]
    private ?string $color = null;

    #[ORM\Column(name: 'owner_name', type: 'string', length: 200, nullable: true)]
    private ?string $ownerName = null;

    #[ORM\Column(name: 'owner_phone', type: 'string', length: 50, nullable: true)]
    private ?string $ownerPhone = null;

    #[ORM\Column(name: 'owner_type', type: 'string', length: 10, enumType: OwnerType::class)]
    private OwnerType $ownerType = OwnerType::UNKNOWN;

    #[ORM\Column(type: 'string', length: 10, enumType: ParkingVehicleStatus::class)]
    private ParkingVehicleStatus $status = ParkingVehicleStatus::PARKED;

    #[ORM\Column(name: 'entry_time', type: 'datetime_immutable')]
    private \DateTimeImmutable $entryTime;

    #[ORM\Column(name: 'exit_time', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $exitTime = null;

    #[ORM\Column(name: 'logged_by', type: 'string', length: 36)]
    private string $loggedBy;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct() { $this->id = Uuid::uuid4()->toString(); $this->entryTime = new \DateTimeImmutable(); $this->createdAt = new \DateTimeImmutable(); }

    public function getId(): string { return $this->id; }
    public function setSiteId(string $id): static { $this->siteId = $id; return $this; }
    public function setParkingLotId(?string $id): static { $this->parkingLotId = $id; return $this; }
    public function setPlateNumber(string $p): static { $this->plateNumber = $p; return $this; }
    public function setMake(?string $m): static { $this->make = $m; return $this; }
    public function setModel(?string $m): static { $this->model = $m; return $this; }
    public function setColor(?string $c): static { $this->color = $c; return $this; }
    public function setOwnerName(?string $n): static { $this->ownerName = $n; return $this; }
    public function setOwnerPhone(?string $p): static { $this->ownerPhone = $p; return $this; }
    public function setOwnerType(OwnerType $t): static { $this->ownerType = $t; return $this; }
    public function setLoggedBy(string $id): static { $this->loggedBy = $id; return $this; }
    public function setNotes(?string $n): static { $this->notes = $n; return $this; }
    public function markDeparted(): static { $this->status = ParkingVehicleStatus::DEPARTED; $this->exitTime = new \DateTimeImmutable(); return $this; }
    public function markViolation(): static { $this->status = ParkingVehicleStatus::VIOLATION; return $this; }

    public function toArray(): array
    {
        return [
            'id' => $this->id, 'tenant_id' => $this->tenantId, 'site_id' => $this->siteId,
            'parking_lot_id' => $this->parkingLotId, 'plate_number' => $this->plateNumber,
            'make' => $this->make, 'model' => $this->model, 'color' => $this->color,
            'owner_name' => $this->ownerName, 'owner_phone' => $this->ownerPhone,
            'owner_type' => $this->ownerType->value, 'owner_type_label' => $this->ownerType->label(),
            'status' => $this->status->value, 'status_label' => $this->status->label(),
            'entry_time' => $this->entryTime->format(\DateTimeInterface::ATOM),
            'exit_time' => $this->exitTime?->format(\DateTimeInterface::ATOM),
            'logged_by' => $this->loggedBy, 'notes' => $this->notes,
        ];
    }
}
