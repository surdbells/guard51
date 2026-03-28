<?php
declare(strict_types=1);
namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'parking_incidents')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'idx_pki_tenant', columns: ['tenant_id'])]
class ParkingIncident implements TenantAwareInterface
{
    use TenantAwareTrait;
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $siteId;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $vehicleId = null;

    #[ORM\Column(type: 'string', length: 36)]
    private string $incidentTypeId;

    #[ORM\Column(type: 'text')]
    private string $description;

    #[ORM\Column(type: 'json')]
    private array $attachments = [];

    #[ORM\Column(type: 'string', length: 36)]
    private string $reportedBy;

    #[ORM\Column(type: 'string', length: 10, enumType: ParkingIncidentStatus::class)]
    private ParkingIncidentStatus $status = ParkingIncidentStatus::REPORTED;

    public function __construct() { $this->id = Uuid::uuid4()->toString(); }

    public function getId(): string { return $this->id; }
    public function setSiteId(string $id): static { $this->siteId = $id; return $this; }
    public function setVehicleId(?string $id): static { $this->vehicleId = $id; return $this; }
    public function setIncidentTypeId(string $id): static { $this->incidentTypeId = $id; return $this; }
    public function setDescription(string $d): static { $this->description = $d; return $this; }
    public function setAttachments(array $a): static { $this->attachments = $a; return $this; }
    public function setReportedBy(string $id): static { $this->reportedBy = $id; return $this; }
    public function resolve(): static { $this->status = ParkingIncidentStatus::RESOLVED; return $this; }

    public function toArray(): array
    {
        return [
            'id' => $this->id, 'tenant_id' => $this->tenantId, 'site_id' => $this->siteId,
            'vehicle_id' => $this->vehicleId, 'incident_type_id' => $this->incidentTypeId,
            'description' => $this->description, 'attachments' => $this->attachments,
            'reported_by' => $this->reportedBy,
            'status' => $this->status->value, 'status_label' => $this->status->label(),
            'created_at' => $this->createdAt?->format(\DateTimeInterface::ATOM),
        ];
    }
}
