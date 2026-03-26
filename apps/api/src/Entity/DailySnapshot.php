<?php

declare(strict_types=1);

namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'daily_snapshots')]
#[ORM\UniqueConstraint(name: 'uq_ds_tenant_date', columns: ['tenant_id', 'snapshot_date'])]
#[ORM\Index(name: 'idx_ds_tenant', columns: ['tenant_id'])]
class DailySnapshot implements TenantAwareInterface
{
    use TenantAwareTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $snapshotDate;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $totalGuards = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $guardsOnDuty = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $guardsLate = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $guardsAbsent = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $totalSites = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $sitesCovered = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $incidentsCount = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $shiftsTotal = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $shiftsFilled = 0;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->id = Uuid::uuid4()->toString();
        $this->snapshotDate = new \DateTimeImmutable();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): string { return $this->id; }
    public function getSnapshotDate(): \DateTimeImmutable { return $this->snapshotDate; }
    public function getTotalGuards(): int { return $this->totalGuards; }
    public function getGuardsOnDuty(): int { return $this->guardsOnDuty; }
    public function getGuardsLate(): int { return $this->guardsLate; }
    public function getGuardsAbsent(): int { return $this->guardsAbsent; }
    public function getTotalSites(): int { return $this->totalSites; }
    public function getSitesCovered(): int { return $this->sitesCovered; }
    public function getIncidentsCount(): int { return $this->incidentsCount; }
    public function getShiftsTotal(): int { return $this->shiftsTotal; }
    public function getShiftsFilled(): int { return $this->shiftsFilled; }

    public function setSnapshotDate(\DateTimeImmutable $d): static { $this->snapshotDate = $d; return $this; }
    public function setTotalGuards(int $v): static { $this->totalGuards = $v; return $this; }
    public function setGuardsOnDuty(int $v): static { $this->guardsOnDuty = $v; return $this; }
    public function setGuardsLate(int $v): static { $this->guardsLate = $v; return $this; }
    public function setGuardsAbsent(int $v): static { $this->guardsAbsent = $v; return $this; }
    public function setTotalSites(int $v): static { $this->totalSites = $v; return $this; }
    public function setSitesCovered(int $v): static { $this->sitesCovered = $v; return $this; }
    public function setIncidentsCount(int $v): static { $this->incidentsCount = $v; return $this; }
    public function setShiftsTotal(int $v): static { $this->shiftsTotal = $v; return $this; }
    public function setShiftsFilled(int $v): static { $this->shiftsFilled = $v; return $this; }

    public function getAttendanceRate(): float
    {
        if ($this->totalGuards === 0) return 0.0;
        return round(($this->guardsOnDuty / $this->totalGuards) * 100, 1);
    }

    public function getSiteCoverageRate(): float
    {
        if ($this->totalSites === 0) return 0.0;
        return round(($this->sitesCovered / $this->totalSites) * 100, 1);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id, 'tenant_id' => $this->tenantId,
            'snapshot_date' => $this->snapshotDate->format('Y-m-d'),
            'total_guards' => $this->totalGuards, 'guards_on_duty' => $this->guardsOnDuty,
            'guards_late' => $this->guardsLate, 'guards_absent' => $this->guardsAbsent,
            'total_sites' => $this->totalSites, 'sites_covered' => $this->sitesCovered,
            'incidents_count' => $this->incidentsCount,
            'shifts_total' => $this->shiftsTotal, 'shifts_filled' => $this->shiftsFilled,
            'attendance_rate' => $this->getAttendanceRate(),
            'site_coverage_rate' => $this->getSiteCoverageRate(),
        ];
    }
}
