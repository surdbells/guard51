<?php

declare(strict_types=1);

namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'attendance_records')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'idx_ar_tenant', columns: ['tenant_id'])]
#[ORM\Index(name: 'idx_ar_guard', columns: ['guard_id'])]
#[ORM\Index(name: 'idx_ar_date', columns: ['attendance_date'])]
#[ORM\Index(name: 'idx_ar_status', columns: ['status'])]
#[ORM\UniqueConstraint(name: 'uq_ar_guard_shift', columns: ['guard_id', 'shift_id'])]
class AttendanceRecord implements TenantAwareInterface
{
    use TenantAwareTrait;
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $guardId;

    #[ORM\Column(type: 'string', length: 36)]
    private string $shiftId;

    #[ORM\Column(type: 'string', length: 36)]
    private string $siteId;

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $attendanceDate;

    #[ORM\Column(type: 'string', length: 20, enumType: AttendanceStatus::class)]
    private AttendanceStatus $status = AttendanceStatus::ABSENT;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $scheduledStart;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $actualStart = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $scheduledEnd;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $actualEnd = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $lateMinutes = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $earlyLeaveMinutes = 0;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2, options: ['default' => '0'])]
    private string $totalWorkedHours = '0';

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $reconciled = false;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $reconciledBy = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    public function __construct()
    {
        $this->id = Uuid::uuid4()->toString();
        $this->attendanceDate = new \DateTimeImmutable();
        $this->scheduledStart = new \DateTimeImmutable();
        $this->scheduledEnd = new \DateTimeImmutable('+12 hours');
    }

    public function getId(): string { return $this->id; }
    public function getGuardId(): string { return $this->guardId; }
    public function getShiftId(): string { return $this->shiftId; }
    public function getSiteId(): string { return $this->siteId; }
    public function getAttendanceDate(): \DateTimeImmutable { return $this->attendanceDate; }
    public function getStatus(): AttendanceStatus { return $this->status; }
    public function getLateMinutes(): int { return $this->lateMinutes; }
    public function getTotalWorkedHours(): float { return (float) $this->totalWorkedHours; }
    public function isReconciled(): bool { return $this->reconciled; }

    public function setGuardId(string $id): static { $this->guardId = $id; return $this; }
    public function setShiftId(string $id): static { $this->shiftId = $id; return $this; }
    public function setSiteId(string $id): static { $this->siteId = $id; return $this; }
    public function setAttendanceDate(\DateTimeImmutable $d): static { $this->attendanceDate = $d; return $this; }
    public function setStatus(AttendanceStatus $s): static { $this->status = $s; return $this; }
    public function setScheduledStart(\DateTimeImmutable $t): static { $this->scheduledStart = $t; return $this; }
    public function setScheduledEnd(\DateTimeImmutable $t): static { $this->scheduledEnd = $t; return $this; }
    public function setNotes(?string $n): static { $this->notes = $n; return $this; }

    public function markPresent(\DateTimeImmutable $clockIn): static
    {
        $this->actualStart = $clockIn;
        $late = ($clockIn->getTimestamp() - $this->scheduledStart->getTimestamp()) / 60;
        if ($late > 5) { // 5 min grace period
            $this->lateMinutes = (int) $late;
            $this->status = AttendanceStatus::LATE;
        } else {
            $this->lateMinutes = 0;
            $this->status = AttendanceStatus::PRESENT;
        }
        return $this;
    }

    public function markClockOut(\DateTimeImmutable $clockOut): static
    {
        $this->actualEnd = $clockOut;
        $earlyLeave = ($this->scheduledEnd->getTimestamp() - $clockOut->getTimestamp()) / 60;
        $this->earlyLeaveMinutes = max(0, (int) $earlyLeave);
        if ($this->actualStart) {
            $this->totalWorkedHours = (string) round(
                ($clockOut->getTimestamp() - $this->actualStart->getTimestamp()) / 3600, 2
            );
        }
        return $this;
    }

    public function reconcile(string $userId, AttendanceStatus $status, ?string $notes = null): static
    {
        $this->reconciled = true;
        $this->reconciledBy = $userId;
        $this->status = $status;
        if ($notes) $this->notes = $notes;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id, 'tenant_id' => $this->tenantId, 'guard_id' => $this->guardId,
            'shift_id' => $this->shiftId, 'site_id' => $this->siteId,
            'attendance_date' => $this->attendanceDate->format('Y-m-d'),
            'status' => $this->status->value, 'status_label' => $this->status->label(),
            'scheduled_start' => $this->scheduledStart->format(\DateTimeInterface::ATOM),
            'actual_start' => $this->actualStart?->format(\DateTimeInterface::ATOM),
            'scheduled_end' => $this->scheduledEnd->format(\DateTimeInterface::ATOM),
            'actual_end' => $this->actualEnd?->format(\DateTimeInterface::ATOM),
            'late_minutes' => $this->lateMinutes, 'early_leave_minutes' => $this->earlyLeaveMinutes,
            'total_worked_hours' => $this->getTotalWorkedHours(),
            'reconciled' => $this->reconciled, 'reconciled_by' => $this->reconciledBy,
            'notes' => $this->notes,
            'created_at' => $this->createdAt?->format(\DateTimeInterface::ATOM),
        ];
    }
}
