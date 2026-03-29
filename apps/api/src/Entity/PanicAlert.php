<?php
declare(strict_types=1);
namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'panic_alerts')]
#[ORM\Index(name: 'idx_pa_tenant', columns: ['tenant_id'])]
#[ORM\Index(name: 'idx_pa_status', columns: ['status'])]
class PanicAlert implements TenantAwareInterface
{
    use TenantAwareTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'guard_id', type: 'string', length: 36)]
    private string $guardId;

    #[ORM\Column(name: 'site_id', type: 'string', length: 36, nullable: true)]
    private ?string $siteId = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 8)]
    private string $latitude;

    #[ORM\Column(type: 'decimal', precision: 11, scale: 8)]
    private string $longitude;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $message = null;

    #[ORM\Column(name: 'audio_url', type: 'string', length: 500, nullable: true)]
    private ?string $audioUrl = null;

    #[ORM\Column(type: 'string', length: 20, enumType: PanicAlertStatus::class)]
    private PanicAlertStatus $status = PanicAlertStatus::TRIGGERED;

    #[ORM\Column(name: 'acknowledged_by', type: 'string', length: 36, nullable: true)]
    private ?string $acknowledgedBy = null;

    #[ORM\Column(name: 'acknowledged_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $acknowledgedAt = null;

    #[ORM\Column(name: 'resolved_by', type: 'string', length: 36, nullable: true)]
    private ?string $resolvedBy = null;

    #[ORM\Column(name: 'resolved_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $resolvedAt = null;

    #[ORM\Column(name: 'resolution_notes', type: 'text', nullable: true)]
    private ?string $resolutionNotes = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct() { $this->id = Uuid::uuid4()->toString(); $this->createdAt = new \DateTimeImmutable(); }

    public function getId(): string { return $this->id; }
    public function getGuardId(): string { return $this->guardId; }
    public function getSiteId(): ?string { return $this->siteId; }
    public function getStatus(): PanicAlertStatus { return $this->status; }
    public function isActive(): bool { return $this->status->isActive(); }

    public function setGuardId(string $id): static { $this->guardId = $id; return $this; }
    public function setSiteId(?string $id): static { $this->siteId = $id; return $this; }
    public function setLatitude(float $v): static { $this->latitude = (string) $v; return $this; }
    public function setLongitude(float $v): static { $this->longitude = (string) $v; return $this; }
    public function setMessage(?string $m): static { $this->message = $m; return $this; }
    public function setAudioUrl(?string $u): static { $this->audioUrl = $u; return $this; }

    public function acknowledge(string $userId): static
    {
        $this->status = PanicAlertStatus::ACKNOWLEDGED;
        $this->acknowledgedBy = $userId;
        $this->acknowledgedAt = new \DateTimeImmutable();
        return $this;
    }

    public function markResponding(): static { $this->status = PanicAlertStatus::RESPONDING; return $this; }

    public function resolve(string $userId, ?string $notes = null): static
    {
        $this->status = PanicAlertStatus::RESOLVED;
        $this->resolvedBy = $userId;
        $this->resolvedAt = new \DateTimeImmutable();
        $this->resolutionNotes = $notes;
        return $this;
    }

    public function markFalseAlarm(string $userId, ?string $notes = null): static
    {
        $this->status = PanicAlertStatus::FALSE_ALARM;
        $this->resolvedBy = $userId;
        $this->resolvedAt = new \DateTimeImmutable();
        $this->resolutionNotes = $notes ?? 'Marked as false alarm';
        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id, 'tenant_id' => $this->tenantId, 'guard_id' => $this->guardId,
            'site_id' => $this->siteId, 'lat' => (float) $this->latitude, 'lng' => (float) $this->longitude,
            'message' => $this->message, 'audio_url' => $this->audioUrl,
            'status' => $this->status->value, 'status_label' => $this->status->label(),
            'is_active' => $this->isActive(),
            'acknowledged_by' => $this->acknowledgedBy, 'acknowledged_at' => $this->acknowledgedAt?->format(\DateTimeInterface::ATOM),
            'resolved_by' => $this->resolvedBy, 'resolved_at' => $this->resolvedAt?->format(\DateTimeInterface::ATOM),
            'resolution_notes' => $this->resolutionNotes,
            'created_at' => $this->createdAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
