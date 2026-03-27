<?php

declare(strict_types=1);

namespace Guard51\Service;

use Guard51\Entity\Shift;
use Guard51\Entity\ShiftStatus;
use Guard51\Entity\ShiftSwapRequest;
use Guard51\Entity\ShiftTemplate;
use Guard51\Exception\ApiException;
use Guard51\Repository\ShiftRepository;
use Guard51\Repository\ShiftSwapRequestRepository;
use Guard51\Repository\ShiftTemplateRepository;
use Psr\Log\LoggerInterface;

final class ShiftService
{
    public function __construct(
        private readonly ShiftTemplateRepository $templateRepo,
        private readonly ShiftRepository $shiftRepo,
        private readonly ShiftSwapRequestRepository $swapRepo,
        private readonly LoggerInterface $logger,
    ) {}

    // ── Templates ────────────────────────────────────

    public function createTemplate(string $tenantId, array $data): ShiftTemplate
    {
        if (empty($data['name']) || empty($data['start_time']) || empty($data['end_time'])) {
            throw ApiException::validation('Template name, start_time, and end_time are required.');
        }
        $t = new ShiftTemplate();
        $t->setTenantId($tenantId);
        $this->hydrateTemplate($t, $data);
        $this->templateRepo->save($t);
        return $t;
    }

    public function updateTemplate(string $id, array $data): ShiftTemplate
    {
        $t = $this->templateRepo->findOrFail($id);
        $this->hydrateTemplate($t, $data);
        $this->templateRepo->save($t);
        return $t;
    }

    public function listTemplates(string $tenantId, bool $activeOnly = false): array
    {
        return $activeOnly ? $this->templateRepo->findActiveByTenant($tenantId) : $this->templateRepo->findByTenant($tenantId);
    }

    // ── Shifts ───────────────────────────────────────

    public function createShift(string $tenantId, array $data, string $createdBy): Shift
    {
        if (empty($data['site_id']) || empty($data['shift_date']) || empty($data['start_time']) || empty($data['end_time'])) {
            throw ApiException::validation('site_id, shift_date, start_time, and end_time are required.');
        }

        $shift = new Shift();
        $shift->setTenantId($tenantId)
            ->setSiteId($data['site_id'])
            ->setShiftDate(new \DateTimeImmutable($data['shift_date']))
            ->setStartTime(new \DateTimeImmutable($data['start_time']))
            ->setEndTime(new \DateTimeImmutable($data['end_time']))
            ->setCreatedBy($createdBy);

        if (!empty($data['guard_id'])) {
            $this->checkConflicts($data['guard_id'], $shift->getStartTime(), $shift->getEndTime());
            $shift->setGuardId($data['guard_id']);
        }
        if (!empty($data['template_id'])) $shift->setTemplateId($data['template_id']);
        if (!empty($data['is_open'])) $shift->setIsOpen(true);
        if (!empty($data['notes'])) $shift->setNotes($data['notes']);
        if (!empty($data['status'])) $shift->setStatus(ShiftStatus::from($data['status']));

        $this->shiftRepo->save($shift);
        return $shift;
    }

    public function bulkGenerate(string $tenantId, string $templateId, string $startDate, string $endDate, string $createdBy, ?string $siteIdOverride = null): array
    {
        $template = $this->templateRepo->findOrFail($templateId);
        $siteId = $siteIdOverride ?? $template->getSiteId();
        if (!$siteId) {
            throw ApiException::validation('Site ID is required — either set it on the template or pass site_id.');
        }

        $current = new \DateTimeImmutable($startDate);
        $end = new \DateTimeImmutable($endDate);
        $created = [];

        while ($current <= $end) {
            $isoDay = (int) $current->format('N'); // 1=Mon ... 7=Sun
            if ($template->appliesToDay($isoDay)) {
                $shiftDate = $current->format('Y-m-d');
                $startTime = $shiftDate . ' ' . $template->getStartTime() . ':00';
                $endTime = $shiftDate . ' ' . $template->getEndTime() . ':00';

                $startDt = new \DateTimeImmutable($startTime);
                $endDt = new \DateTimeImmutable($endTime);
                if ($endDt <= $startDt) $endDt = $endDt->modify('+1 day');

                $shift = new Shift();
                $shift->setTenantId($tenantId)
                    ->setSiteId($siteId)
                    ->setTemplateId($templateId)
                    ->setShiftDate(new \DateTimeImmutable($shiftDate))
                    ->setStartTime($startDt)
                    ->setEndTime($endDt)
                    ->setCreatedBy($createdBy)
                    ->setStatus(ShiftStatus::DRAFT);

                $this->shiftRepo->save($shift);
                $created[] = $shift;
            }
            $current = $current->modify('+1 day');
        }

        $this->logger->info('Bulk shifts generated.', ['count' => count($created), 'template' => $template->getName()]);
        return $created;
    }

    public function publishShifts(string $tenantId, array $shiftIds): int
    {
        $count = 0;
        foreach ($shiftIds as $id) {
            $shift = $this->shiftRepo->find($id);
            if ($shift && $shift->getStatus() === ShiftStatus::DRAFT) {
                $shift->publish();
                $this->shiftRepo->save($shift);
                $count++;
            }
        }
        return $count;
    }

    public function confirmShift(string $shiftId, string $guardId): Shift
    {
        $shift = $this->shiftRepo->findOrFail($shiftId);
        if (!$shift->getStatus()->canBeConfirmed()) {
            throw ApiException::conflict('Shift cannot be confirmed in its current status.');
        }
        $shift->confirm($guardId);
        $this->shiftRepo->save($shift);
        return $shift;
    }

    public function claimOpenShift(string $shiftId, string $guardId): Shift
    {
        $shift = $this->shiftRepo->findOrFail($shiftId);
        if (!$shift->isOpen() || $shift->isAssigned()) {
            throw ApiException::conflict('This shift is not available for claiming.');
        }
        $this->checkConflicts($guardId, $shift->getStartTime(), $shift->getEndTime());
        $shift->confirm($guardId);
        $this->shiftRepo->save($shift);
        return $shift;
    }

    public function getShifts(string $tenantId, string $startDate, string $endDate, ?string $siteId = null, ?string $guardId = null): array
    {
        return $this->shiftRepo->findByTenantAndDateRange($tenantId, new \DateTimeImmutable($startDate), new \DateTimeImmutable($endDate), $siteId, $guardId);
    }

    public function getOpenShifts(string $tenantId): array
    {
        return $this->shiftRepo->findOpenShifts($tenantId);
    }

    public function updateShift(string $shiftId, array $data): Shift
    {
        $shift = $this->shiftRepo->findOrFail($shiftId);
        if (isset($data['guard_id'])) {
            $this->checkConflicts($data['guard_id'], $shift->getStartTime(), $shift->getEndTime(), $shiftId);
            $shift->setGuardId($data['guard_id']);
        }
        if (isset($data['notes'])) $shift->setNotes($data['notes']);
        if (isset($data['status'])) $shift->setStatus(ShiftStatus::from($data['status']));
        if (isset($data['is_open'])) $shift->setIsOpen((bool) $data['is_open']);
        $this->shiftRepo->save($shift);
        return $shift;
    }

    public function cancelShift(string $shiftId): Shift
    {
        $shift = $this->shiftRepo->findOrFail($shiftId);
        $shift->cancel();
        $this->shiftRepo->save($shift);
        return $shift;
    }

    // ── Swap Requests ────────────────────────────────

    public function createSwapRequest(string $tenantId, array $data): ShiftSwapRequest
    {
        if (empty($data['shift_id']) || empty($data['target_guard_id']) || empty($data['reason'])) {
            throw ApiException::validation('shift_id, target_guard_id, and reason are required.');
        }
        $req = new ShiftSwapRequest();
        $req->setTenantId($tenantId)
            ->setRequestingGuardId($data['requesting_guard_id'])
            ->setTargetGuardId($data['target_guard_id'])
            ->setShiftId($data['shift_id'])
            ->setReason($data['reason']);
        $this->swapRepo->save($req);
        return $req;
    }

    public function approveSwap(string $requestId, string $reviewerId): ShiftSwapRequest
    {
        $req = $this->swapRepo->findOrFail($requestId);
        if (!$req->isPending()) throw ApiException::conflict('Swap request is already resolved.');

        $shift = $this->shiftRepo->findOrFail($req->getShiftId());
        $this->checkConflicts($req->getTargetGuardId(), $shift->getStartTime(), $shift->getEndTime(), $shift->getId());

        // Reassign shift
        $shift->setGuardId($req->getTargetGuardId());
        $this->shiftRepo->save($shift);

        $req->approve($reviewerId);
        $this->swapRepo->save($req);
        return $req;
    }

    public function rejectSwap(string $requestId, string $reviewerId, ?string $notes = null): ShiftSwapRequest
    {
        $req = $this->swapRepo->findOrFail($requestId);
        if (!$req->isPending()) throw ApiException::conflict('Swap request is already resolved.');
        $req->reject($reviewerId, $notes);
        $this->swapRepo->save($req);
        return $req;
    }

    public function listSwapRequests(string $tenantId): array
    {
        return $this->swapRepo->findPendingByTenant($tenantId);
    }

    // ── Helpers ──────────────────────────────────────

    private function checkConflicts(string $guardId, \DateTimeImmutable $start, \DateTimeImmutable $end, ?string $excludeId = null): void
    {
        $conflicts = $this->shiftRepo->findConflicts($guardId, $start, $end, $excludeId);
        if (count($conflicts) > 0) {
            throw ApiException::conflict('Guard has a conflicting shift during this time period.');
        }
    }

    private function hydrateTemplate(ShiftTemplate $t, array $data): void
    {
        if (isset($data['name'])) $t->setName($data['name']);
        if (isset($data['start_time'])) $t->setStartTime($data['start_time']);
        if (isset($data['end_time'])) $t->setEndTime($data['end_time']);
        if (isset($data['days_of_week'])) $t->setDaysOfWeek($data['days_of_week']);
        if (isset($data['site_id'])) $t->setSiteId($data['site_id']);
        if (isset($data['color'])) $t->setColor($data['color']);
        if (isset($data['is_active'])) $t->setIsActive((bool) $data['is_active']);
    }
}
