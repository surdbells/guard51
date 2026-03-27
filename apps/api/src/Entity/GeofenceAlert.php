<?php
declare(strict_types=1);
namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'geofence_alerts')]
#[ORM\Index(name: 'idx_ga_tenant', columns: ['tenant_id'])]
#[ORM\Index(name: 'idx_ga_guard', columns: ['guard_id'])]
class GeofenceAlert implements TenantAwareInterface
{
    use TenantAwareTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $guardId;

    #[ORM\Column(type: 'string', length: 36)]
    private string $siteId;

    #[ORM\Column(type: 'string', length: 30, enumType: GeofenceAlertType::class)]
    private GeofenceAlertType $alertType;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 8)]
    private string $latitude;

    #[ORM\Column(type: 'decimal', precision: 11, scale: 8)]
    private string $longitude;

    #[ORM\Column(type: 'text')]
    private string $message;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isAcknowledged = false;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $acknowledgedBy = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $acknowledgedAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct() { $this->id = Uuid::uuid4()->toString(); $this->createdAt = new \DateTimeImmutable(); }

    public function getId(): string { return $this->id; }
    public function getGuardId(): string { return $this->guardId; }
    public function getSiteId(): string { return $this->siteId; }
    public function getAlertType(): GeofenceAlertType { return $this->alertType; }
    public function isAcknowledged(): bool { return $this->isAcknowledged; }

    public function setGuardId(string $id): static { $this->guardId = $id; return $this; }
    public function setSiteId(string $id): static { $this->siteId = $id; return $this; }
    public function setAlertType(GeofenceAlertType $t): static { $this->alertType = $t; return $this; }
    public function setLatitude(float $v): static { $this->latitude = (string) $v; return $this; }
    public function setLongitude(float $v): static { $this->longitude = (string) $v; return $this; }
    public function setMessage(string $m): static { $this->message = $m; return $this; }

    public function acknowledge(string $userId): static
    {
        $this->isAcknowledged = true;
        $this->acknowledgedBy = $userId;
        $this->acknowledgedAt = new \DateTimeImmutable();
        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id, 'tenant_id' => $this->tenantId, 'guard_id' => $this->guardId,
            'site_id' => $this->siteId, 'alert_type' => $this->alertType->value,
            'alert_type_label' => $this->alertType->label(), 'severity' => $this->alertType->severity(),
            'lat' => (float) $this->latitude, 'lng' => (float) $this->longitude,
            'message' => $this->message, 'is_acknowledged' => $this->isAcknowledged,
            'acknowledged_by' => $this->acknowledgedBy,
            'acknowledged_at' => $this->acknowledgedAt?->format(\DateTimeInterface::ATOM),
            'created_at' => $this->createdAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
