<?php
declare(strict_types=1);
namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'patrol_vehicles')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'idx_pv_tenant', columns: ['tenant_id'])]
class PatrolVehicle implements TenantAwareInterface
{
    use TenantAwareTrait;
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 100)]
    private string $vehicleName;

    #[ORM\Column(type: 'string', length: 20)]
    private string $plateNumber;

    #[ORM\Column(type: 'string', length: 15, enumType: VehicleType::class)]
    private VehicleType $vehicleType;

    #[ORM\Column(type: 'string', length: 15, enumType: VehicleStatus::class)]
    private VehicleStatus $status = VehicleStatus::ACTIVE;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $assignedGuardId = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    public function __construct() { $this->id = Uuid::uuid4()->toString(); }

    public function getId(): string { return $this->id; }
    public function setVehicleName(string $n): static { $this->vehicleName = $n; return $this; }
    public function setPlateNumber(string $p): static { $this->plateNumber = $p; return $this; }
    public function setVehicleType(VehicleType $t): static { $this->vehicleType = $t; return $this; }
    public function setStatus(VehicleStatus $s): static { $this->status = $s; return $this; }
    public function setAssignedGuardId(?string $id): static { $this->assignedGuardId = $id; return $this; }
    public function setNotes(?string $n): static { $this->notes = $n; return $this; }

    public function toArray(): array
    {
        return [
            'id' => $this->id, 'tenant_id' => $this->tenantId, 'vehicle_name' => $this->vehicleName,
            'plate_number' => $this->plateNumber, 'vehicle_type' => $this->vehicleType->value,
            'vehicle_type_label' => $this->vehicleType->label(),
            'status' => $this->status->value, 'status_label' => $this->status->label(),
            'assigned_guard_id' => $this->assignedGuardId, 'notes' => $this->notes,
            'created_at' => $this->createdAt?->format(\DateTimeInterface::ATOM),
        ];
    }
}
