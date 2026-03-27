<?php

declare(strict_types=1);

namespace Guard51\Service;

use Guard51\Entity\AttendanceRecord;
use Guard51\Entity\AttendanceStatus;
use Guard51\Entity\BreakConfig;
use Guard51\Entity\BreakLog;
use Guard51\Entity\BreakType;
use Guard51\Entity\ClockMethod;
use Guard51\Entity\TimeClock;
use Guard51\Entity\TimeClockStatus;
use Guard51\Exception\ApiException;
use Guard51\Repository\AttendanceRecordRepository;
use Guard51\Repository\BreakConfigRepository;
use Guard51\Repository\BreakLogRepository;
use Guard51\Repository\ShiftRepository;
use Guard51\Repository\SiteRepository;
use Guard51\Repository\TimeClockRepository;
use Psr\Log\LoggerInterface;

final class TimeClockService
{
    public function __construct(
        private readonly TimeClockRepository $clockRepo,
        private readonly AttendanceRecordRepository $attendanceRepo,
        private readonly BreakConfigRepository $breakConfigRepo,
        private readonly BreakLogRepository $breakLogRepo,
        private readonly ShiftRepository $shiftRepo,
        private readonly SiteRepository $siteRepo,
        private readonly GeofenceService $geofenceService,
        private readonly LoggerInterface $logger,
    ) {}

    // ── Clock In/Out ─────────────────────────────────

    public function clockIn(string $tenantId, string $guardId, string $siteId, float $lat, float $lng, ClockMethod $method, ?string $shiftId = null, ?string $photoUrl = null): TimeClock
    {
        // Check not already clocked in
        $existing = $this->clockRepo->findActiveByGuard($guardId);
        if ($existing) {
            throw ApiException::conflict('Guard is already clocked in. Please clock out first.');
        }

        // Geofence check
        $site = $this->siteRepo->findOrFail($siteId);
        $withinGeofence = $this->geofenceService->isInsideGeofence($siteId, $lat, $lng);

        $clock = new TimeClock();
        $clock->setTenantId($tenantId)
            ->setGuardId($guardId)
            ->setSiteId($siteId)
            ->setShiftId($shiftId)
            ->setClockInLat($lat)
            ->setClockInLng($lng)
            ->setClockInMethod($method)
            ->setClockInPhotoUrl($photoUrl)
            ->setIsWithinGeofenceIn($withinGeofence);

        $this->clockRepo->save($clock);

        // Auto-generate attendance record if linked to shift
        if ($shiftId) {
            $this->generateAttendanceFromClockIn($tenantId, $guardId, $shiftId, $siteId, $clock);
        }

        $this->logger->info('Guard clocked in.', ['guard_id' => $guardId, 'site_id' => $siteId, 'geofence' => $withinGeofence]);
        return $clock;
    }

    public function clockOut(string $guardId, float $lat, float $lng, ClockMethod $method): TimeClock
    {
        $clock = $this->clockRepo->findActiveByGuard($guardId);
        if (!$clock) {
            throw ApiException::notFound('Guard is not currently clocked in.');
        }

        $site = $this->siteRepo->findOrFail($clock->getSiteId());
        $withinGeofence = $this->geofenceService->isInsideGeofence($clock->getSiteId(), $lat, $lng);

        $clock->clockOut($lat, $lng, $method, $withinGeofence);
        $this->clockRepo->save($clock);

        // Update attendance record
        if ($clock->getShiftId()) {
            $this->updateAttendanceFromClockOut($guardId, $clock);
        }

        $this->logger->info('Guard clocked out.', ['guard_id' => $guardId, 'hours' => $clock->getTotalHours()]);
        return $clock;
    }

    public function getActiveClockByGuard(string $guardId): ?TimeClock
    {
        return $this->clockRepo->findActiveByGuard($guardId);
    }

    public function getActiveBySite(string $siteId): array
    {
        return $this->clockRepo->findActiveBySite($siteId);
    }

    public function getClockHistory(string $guardId, string $startDate, string $endDate): array
    {
        return $this->clockRepo->findByGuardAndDateRange($guardId, new \DateTimeImmutable($startDate), new \DateTimeImmutable($endDate));
    }

    // ── Attendance ────────────────────────────────────

    public function getAttendanceByDate(string $tenantId, string $date): array
    {
        return $this->attendanceRepo->findByTenantAndDate($tenantId, new \DateTimeImmutable($date));
    }

    public function getAttendanceByGuard(string $guardId, string $startDate, string $endDate): array
    {
        return $this->attendanceRepo->findByGuardAndDateRange($guardId, new \DateTimeImmutable($startDate), new \DateTimeImmutable($endDate));
    }

    public function getUnreconciled(string $tenantId): array
    {
        return $this->attendanceRepo->findUnreconciled($tenantId);
    }

    public function reconcile(string $recordId, string $userId, string $status, ?string $notes = null): AttendanceRecord
    {
        $record = $this->attendanceRepo->findOrFail($recordId);
        $record->reconcile($userId, AttendanceStatus::from($status), $notes);
        $this->attendanceRepo->save($record);
        return $record;
    }

    /**
     * Bulk reconciliation: auto-approve all unreconciled records where guard was present
     * and late_minutes is below a threshold.
     */
    public function bulkReconcile(string $tenantId, string $userId, int $lateThresholdMinutes = 15): array
    {
        $unreconciled = $this->attendanceRepo->findUnreconciled($tenantId);
        $reconciled = [];

        foreach ($unreconciled as $record) {
            if ($record->getStatus() === AttendanceStatus::PRESENT ||
                ($record->getStatus() === AttendanceStatus::LATE && $record->getLateMinutes() <= $lateThresholdMinutes)) {
                $record->reconcile($userId, $record->getStatus(), 'Auto-approved via bulk reconciliation');
                $this->attendanceRepo->save($record);
                $reconciled[] = $record;
            }
        }

        $this->logger->info('Bulk reconciliation completed.', ['count' => count($reconciled), 'threshold' => $lateThresholdMinutes]);
        return $reconciled;
    }

    // ── Breaks ───────────────────────────────────────

    public function listBreakConfigs(string $tenantId): array
    {
        return $this->breakConfigRepo->findByTenant($tenantId);
    }

    public function createBreakConfig(string $tenantId, array $data): BreakConfig
    {
        if (empty($data['name']) || empty($data['duration_minutes'])) {
            throw ApiException::validation('Break name and duration are required.');
        }
        $bc = new BreakConfig();
        $bc->setTenantId($tenantId)
            ->setName($data['name'])
            ->setDurationMinutes((int) $data['duration_minutes']);
        if (isset($data['break_type'])) $bc->setBreakType(BreakType::from($data['break_type']));
        if (isset($data['auto_start'])) $bc->setAutoStart((bool) $data['auto_start']);
        if (isset($data['auto_start_after_minutes'])) $bc->setAutoStartAfterMinutes((int) $data['auto_start_after_minutes']);
        if (isset($data['can_end_early'])) $bc->setCanEndEarly((bool) $data['can_end_early']);
        $this->breakConfigRepo->save($bc);
        return $bc;
    }

    public function startBreak(string $timeClockId, string $breakConfigId): BreakLog
    {
        $existing = $this->breakLogRepo->findActiveByTimeClock($timeClockId);
        if ($existing) throw ApiException::conflict('Guard is already on a break.');

        $log = new BreakLog();
        $log->setTimeClockId($timeClockId)->setBreakConfigId($breakConfigId);
        $this->breakLogRepo->save($log);
        return $log;
    }

    public function endBreak(string $breakLogId): BreakLog
    {
        $log = $this->breakLogRepo->findOrFail($breakLogId);
        if (!$log->isOnBreak()) throw ApiException::conflict('Break has already ended.');
        $log->endBreak();
        $this->breakLogRepo->save($log);
        return $log;
    }

    // ── Helpers ──────────────────────────────────────

    private function generateAttendanceFromClockIn(string $tenantId, string $guardId, string $shiftId, string $siteId, TimeClock $clock): void
    {
        $shift = $this->shiftRepo->find($shiftId);
        if (!$shift) return;

        $record = new AttendanceRecord();
        $record->setTenantId($tenantId)
            ->setGuardId($guardId)
            ->setShiftId($shiftId)
            ->setSiteId($siteId)
            ->setAttendanceDate($shift->getShiftDate())
            ->setScheduledStart($shift->getStartTime())
            ->setScheduledEnd($shift->getEndTime())
            ->markPresent($clock->getClockInTime());

        $this->attendanceRepo->save($record);
    }

    private function updateAttendanceFromClockOut(string $guardId, TimeClock $clock): void
    {
        $records = $this->attendanceRepo->findByGuardAndDateRange(
            $guardId,
            $clock->getClockInTime()->modify('-1 day'),
            $clock->getClockOutTime() ?? new \DateTimeImmutable(),
        );

        foreach ($records as $record) {
            if ($record->getShiftId() === $clock->getShiftId() && !$record->isReconciled()) {
                $record->markClockOut($clock->getClockOutTime());
                $this->attendanceRepo->save($record);
                break;
            }
        }
    }
}
