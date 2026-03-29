<?php
declare(strict_types=1);
namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'incident_reports')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'idx_ir_tenant', columns: ['tenant_id'])]
#[ORM\Index(name: 'idx_ir_site', columns: ['site_id'])]
#[ORM\Index(name: 'idx_ir_status', columns: ['status'])]
class IncidentReport implements TenantAwareInterface
{
    use TenantAwareTrait;
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'guard_id', type: 'string', length: 36)]
    private string $guardId;

    #[ORM\Column(name: 'site_id', type: 'string', length: 36)]
    private string $siteId;

    #[ORM\Column(name: 'incident_type', type: 'string', length: 30, enumType: IncidentType::class)]
    private IncidentType $incidentType;

    #[ORM\Column(type: 'string', length: 10, enumType: Severity::class)]
    private Severity $severity;

    #[ORM\Column(type: 'string', length: 300)]
    private string $title;

    #[ORM\Column(type: 'text')]
    private string $description;

    #[ORM\Column(name: 'location_detail', type: 'string', length: 200, nullable: true)]
    private ?string $locationDetail = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 8, nullable: true)]
    private ?string $latitude = null;

    #[ORM\Column(type: 'decimal', precision: 11, scale: 8, nullable: true)]
    private ?string $longitude = null;

    #[ORM\Column(name: 'occurred_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $occurredAt;

    #[ORM\Column(name: 'reported_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $reportedAt;

    #[ORM\Column(type: 'json')]
    private array $attachments = [];

    #[ORM\Column(type: 'string', length: 20, enumType: IncidentStatus::class)]
    private IncidentStatus $status = IncidentStatus::REPORTED;

    #[ORM\Column(name: 'assigned_to', type: 'string', length: 36, nullable: true)]
    private ?string $assignedTo = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $resolution = null;

    #[ORM\Column(name: 'resolved_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $resolvedAt = null;

    #[ORM\Column(name: 'resolved_by', type: 'string', length: 36, nullable: true)]
    private ?string $resolvedBy = null;

    #[ORM\Column(name: 'client_notified', type: 'boolean', options: ['default' => false])]
    private bool $clientNotified = false;

    public function __construct() { $this->id = Uuid::uuid4()->toString(); $this->reportedAt = new \DateTimeImmutable(); $this->occurredAt = new \DateTimeImmutable(); }

    public function getId(): string { return $this->id; }
    public function getGuardId(): string { return $this->guardId; }
    public function getSiteId(): string { return $this->siteId; }
    public function getIncidentType(): IncidentType { return $this->incidentType; }
    public function getSeverity(): Severity { return $this->severity; }
    public function getStatus(): IncidentStatus { return $this->status; }

    public function setGuardId(string $id): static { $this->guardId = $id; return $this; }
    public function setSiteId(string $id): static { $this->siteId = $id; return $this; }
    public function setIncidentType(IncidentType $t): static { $this->incidentType = $t; return $this; }
    public function setSeverity(Severity $s): static { $this->severity = $s; return $this; }
    public function setTitle(string $t): static { $this->title = $t; return $this; }
    public function setDescription(string $d): static { $this->description = $d; return $this; }
    public function setLocationDetail(?string $d): static { $this->locationDetail = $d; return $this; }
    public function setLatitude(?float $v): static { $this->latitude = $v !== null ? (string) $v : null; return $this; }
    public function setLongitude(?float $v): static { $this->longitude = $v !== null ? (string) $v : null; return $this; }
    public function setOccurredAt(\DateTimeImmutable $t): static { $this->occurredAt = $t; return $this; }
    public function setAttachments(array $a): static { $this->attachments = $a; return $this; }
    public function setStatus(IncidentStatus $s): static { $this->status = $s; return $this; }
    public function setAssignedTo(?string $id): static { $this->assignedTo = $id; return $this; }

    public function acknowledge(): static { $this->status = IncidentStatus::ACKNOWLEDGED; return $this; }
    public function investigate(): static { $this->status = IncidentStatus::INVESTIGATING; return $this; }
    public function escalate(): static { $this->status = IncidentStatus::ESCALATED; return $this; }
    public function resolve(string $userId, string $resolution): static
    {
        $this->status = IncidentStatus::RESOLVED; $this->resolvedBy = $userId;
        $this->resolvedAt = new \DateTimeImmutable(); $this->resolution = $resolution;
        return $this;
    }
    public function close(): static { $this->status = IncidentStatus::CLOSED; return $this; }
    public function notifyClient(): static { $this->clientNotified = true; return $this; }

    public function toArray(): array
    {
        return [
            'id' => $this->id, 'tenant_id' => $this->tenantId, 'guard_id' => $this->guardId,
            'site_id' => $this->siteId, 'incident_type' => $this->incidentType->value,
            'incident_type_label' => $this->incidentType->label(),
            'severity' => $this->severity->value, 'severity_label' => $this->severity->label(),
            'title' => $this->title, 'description' => $this->description,
            'location_detail' => $this->locationDetail,
            'lat' => $this->latitude ? (float) $this->latitude : null,
            'lng' => $this->longitude ? (float) $this->longitude : null,
            'occurred_at' => $this->occurredAt->format(\DateTimeInterface::ATOM),
            'reported_at' => $this->reportedAt->format(\DateTimeInterface::ATOM),
            'attachments' => $this->attachments, 'status' => $this->status->value,
            'status_label' => $this->status->label(), 'is_active' => $this->status->isActive(),
            'assigned_to' => $this->assignedTo, 'resolution' => $this->resolution,
            'resolved_at' => $this->resolvedAt?->format(\DateTimeInterface::ATOM),
            'client_notified' => $this->clientNotified,
            'created_at' => $this->createdAt?->format(\DateTimeInterface::ATOM),
        ];
    }
}
