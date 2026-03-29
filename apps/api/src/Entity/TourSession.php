<?php
declare(strict_types=1);
namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'tour_sessions')]
#[ORM\Index(name: 'idx_ts_tenant', columns: ['tenant_id'])]
#[ORM\Index(name: 'idx_ts_guard', columns: ['guard_id'])]
#[ORM\Index(name: 'idx_ts_site', columns: ['site_id'])]
class TourSession implements TenantAwareInterface
{
    use TenantAwareTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'guard_id', type: 'string', length: 36)]
    private string $guardId;

    #[ORM\Column(name: 'site_id', type: 'string', length: 36)]
    private string $siteId;

    #[ORM\Column(name: 'shift_id', type: 'string', length: 36, nullable: true)]
    private ?string $shiftId = null;

    #[ORM\Column(name: 'started_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $startedAt;

    #[ORM\Column(name: 'completed_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    #[ORM\Column(type: 'string', length: 20, enumType: TourSessionStatus::class)]
    private TourSessionStatus $status = TourSessionStatus::IN_PROGRESS;

    #[ORM\Column(name: 'total_checkpoints', type: 'integer')]
    private int $totalCheckpoints;

    #[ORM\Column(name: 'scanned_checkpoints', type: 'integer', options: ['default' => 0])]
    private int $scannedCheckpoints = 0;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct() { $this->id = Uuid::uuid4()->toString(); $this->startedAt = new \DateTimeImmutable(); $this->createdAt = new \DateTimeImmutable(); }

    public function getId(): string { return $this->id; }
    public function getGuardId(): string { return $this->guardId; }
    public function getSiteId(): string { return $this->siteId; }
    public function getStatus(): TourSessionStatus { return $this->status; }
    public function getTotalCheckpoints(): int { return $this->totalCheckpoints; }
    public function getScannedCheckpoints(): int { return $this->scannedCheckpoints; }

    public function setGuardId(string $id): static { $this->guardId = $id; return $this; }
    public function setSiteId(string $id): static { $this->siteId = $id; return $this; }
    public function setShiftId(?string $id): static { $this->shiftId = $id; return $this; }
    public function setTotalCheckpoints(int $v): static { $this->totalCheckpoints = $v; return $this; }

    public function recordScan(): static { $this->scannedCheckpoints++; return $this; }

    public function complete(): static
    {
        $this->completedAt = new \DateTimeImmutable();
        $this->status = $this->scannedCheckpoints >= $this->totalCheckpoints ? TourSessionStatus::COMPLETED : TourSessionStatus::INCOMPLETE;
        return $this;
    }

    public function getCompletionRate(): float
    {
        return $this->totalCheckpoints > 0 ? round(($this->scannedCheckpoints / $this->totalCheckpoints) * 100, 1) : 0;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id, 'tenant_id' => $this->tenantId, 'guard_id' => $this->guardId,
            'site_id' => $this->siteId, 'shift_id' => $this->shiftId,
            'started_at' => $this->startedAt->format(\DateTimeInterface::ATOM),
            'completed_at' => $this->completedAt?->format(\DateTimeInterface::ATOM),
            'status' => $this->status->value, 'status_label' => $this->status->label(),
            'total_checkpoints' => $this->totalCheckpoints, 'scanned_checkpoints' => $this->scannedCheckpoints,
            'completion_rate' => $this->getCompletionRate(),
        ];
    }
}
