<?php

declare(strict_types=1);

namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

/**
 * Reusable shift pattern — defines time range and days of week.
 * Used to quickly generate shifts across multiple dates.
 */
#[ORM\Entity]
#[ORM\Table(name: 'shift_templates')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'idx_st_tenant', columns: ['tenant_id'])]
class ShiftTemplate implements TenantAwareInterface
{
    use TenantAwareTrait;
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 100)]
    private string $name;

    /** Time of day shift starts, e.g. "06:00" */
    #[ORM\Column(type: 'string', length: 8)]
    private string $startTime;

    /** Time of day shift ends, e.g. "18:00" */
    #[ORM\Column(type: 'string', length: 8)]
    private string $endTime;

    /** JSONB array of ISO day numbers: 1=Mon … 7=Sun, e.g. [1,2,3,4,5] */
    #[ORM\Column(type: 'json')]
    private array $daysOfWeek = [1, 2, 3, 4, 5];

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $siteId = null;

    #[ORM\Column(type: 'string', length: 7, nullable: true)]
    private ?string $color = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $isActive = true;

    public function __construct()
    {
        $this->id = Uuid::uuid4()->toString();
    }

    // ── Getters ──────────────────────────────────────

    public function getId(): string { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getStartTime(): string { return $this->startTime; }
    public function getEndTime(): string { return $this->endTime; }
    public function getDaysOfWeek(): array { return $this->daysOfWeek; }
    public function getSiteId(): ?string { return $this->siteId; }
    public function getColor(): ?string { return $this->color; }
    public function isActive(): bool { return $this->isActive; }

    // ── Setters ──────────────────────────────────────

    public function setName(string $name): static { $this->name = $name; return $this; }
    public function setStartTime(string $time): static { $this->startTime = $time; return $this; }
    public function setEndTime(string $time): static { $this->endTime = $time; return $this; }
    public function setDaysOfWeek(array $days): static { $this->daysOfWeek = $days; return $this; }
    public function setSiteId(?string $id): static { $this->siteId = $id; return $this; }
    public function setColor(?string $color): static { $this->color = $color; return $this; }
    public function setIsActive(bool $active): static { $this->isActive = $active; return $this; }

    // ── Business Logic ───────────────────────────────

    /**
     * Calculate duration in hours between start and end time.
     * Handles overnight shifts (e.g. 18:00 → 06:00 = 12 hours).
     */
    public function getDurationHours(): float
    {
        $start = \DateTimeImmutable::createFromFormat('H:i', $this->startTime);
        $end = \DateTimeImmutable::createFromFormat('H:i', $this->endTime);
        if (!$start || !$end) return 0;

        $diff = $start->diff($end);
        $hours = $diff->h + ($diff->i / 60);
        // Overnight: if end < start, add 24
        if ($end < $start) $hours = 24 - ($start->diff($end)->h + ($start->diff($end)->i / 60));
        return round($hours, 2);
    }

    public function isOvernight(): bool
    {
        return $this->endTime < $this->startTime;
    }

    /** Check if a given ISO day number (1=Mon … 7=Sun) is in this template */
    public function appliesToDay(int $isoDay): bool
    {
        return in_array($isoDay, $this->daysOfWeek, true);
    }

    public function getDayLabels(): array
    {
        $map = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'];
        return array_map(fn(int $d) => $map[$d] ?? '?', $this->daysOfWeek);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenantId,
            'name' => $this->name,
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
            'days_of_week' => $this->daysOfWeek,
            'day_labels' => $this->getDayLabels(),
            'duration_hours' => $this->getDurationHours(),
            'is_overnight' => $this->isOvernight(),
            'site_id' => $this->siteId,
            'color' => $this->color,
            'is_active' => $this->isActive,
            'created_at' => $this->createdAt?->format(\DateTimeInterface::ATOM),
            'updated_at' => $this->updatedAt?->format(\DateTimeInterface::ATOM),
        ];
    }
}
