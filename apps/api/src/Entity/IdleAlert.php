<?php
declare(strict_types=1);
namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'idle_alerts')]
#[ORM\Index(name: 'idx_ia_tenant', columns: ['tenant_id'])]
#[ORM\Index(name: 'idx_ia_guard', columns: ['guard_id'])]
class IdleAlert implements TenantAwareInterface
{
    use TenantAwareTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'guard_id', type: 'string', length: 36)]
    private string $guardId;

    #[ORM\Column(name: 'site_id', type: 'string', length: 36)]
    private string $siteId;

    #[ORM\Column(name: 'idle_start_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $idleStartAt;

    #[ORM\Column(name: 'idle_duration_minutes', type: 'integer')]
    private int $idleDurationMinutes;

    #[ORM\Column(name: 'last_known_lat', type: 'decimal', precision: 10, scale: 8)]
    private string $lastKnownLat;

    #[ORM\Column(name: 'last_known_lng', type: 'decimal', precision: 11, scale: 8)]
    private string $lastKnownLng;

    #[ORM\Column(name: 'is_acknowledged', type: 'boolean', options: ['default' => false])]
    private bool $isAcknowledged = false;

    #[ORM\Column(name: 'acknowledged_by', type: 'string', length: 36, nullable: true)]
    private ?string $acknowledgedBy = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct() { $this->id = Uuid::uuid4()->toString(); $this->createdAt = new \DateTimeImmutable(); }

    public function getId(): string { return $this->id; }
    public function getGuardId(): string { return $this->guardId; }
    public function getIdleDurationMinutes(): int { return $this->idleDurationMinutes; }
    public function isAcknowledged(): bool { return $this->isAcknowledged; }

    public function setGuardId(string $id): static { $this->guardId = $id; return $this; }
    public function setSiteId(string $id): static { $this->siteId = $id; return $this; }
    public function setIdleStartAt(\DateTimeImmutable $t): static { $this->idleStartAt = $t; return $this; }
    public function setIdleDurationMinutes(int $m): static { $this->idleDurationMinutes = $m; return $this; }
    public function setLastKnownLat(float $v): static { $this->lastKnownLat = (string) $v; return $this; }
    public function setLastKnownLng(float $v): static { $this->lastKnownLng = (string) $v; return $this; }

    public function acknowledge(string $userId): static
    {
        $this->isAcknowledged = true; $this->acknowledgedBy = $userId;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id, 'tenant_id' => $this->tenantId, 'guard_id' => $this->guardId,
            'site_id' => $this->siteId, 'idle_start_at' => $this->idleStartAt->format(\DateTimeInterface::ATOM),
            'idle_duration_minutes' => $this->idleDurationMinutes,
            'lat' => (float) $this->lastKnownLat, 'lng' => (float) $this->lastKnownLng,
            'is_acknowledged' => $this->isAcknowledged, 'acknowledged_by' => $this->acknowledgedBy,
            'created_at' => $this->createdAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
