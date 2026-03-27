<?php
declare(strict_types=1);
namespace Guard51\Repository;
use Guard51\Entity\BreakLog;

/** @extends BaseRepository<BreakLog> */
class BreakLogRepository extends BaseRepository
{
    protected function getEntityClass(): string { return BreakLog::class; }
    public function findByTimeClock(string $timeClockId): array { return $this->findBy(['timeClockId' => $timeClockId], ['startTime' => 'ASC']); }
    public function findActiveByTimeClock(string $timeClockId): ?BreakLog { return $this->findOneBy(['timeClockId' => $timeClockId, 'endTime' => null]); }
}
