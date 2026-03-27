<?php
declare(strict_types=1);
namespace Guard51\Repository;
use Guard51\Entity\TimeClock;
use Guard51\Entity\TimeClockStatus;

/** @extends BaseRepository<TimeClock> */
class TimeClockRepository extends BaseRepository
{
    protected function getEntityClass(): string { return TimeClock::class; }

    public function findActiveByGuard(string $guardId): ?TimeClock
    {
        return $this->findOneBy(['guardId' => $guardId, 'status' => TimeClockStatus::CLOCKED_IN]);
    }

    public function findBySiteAndDate(string $siteId, \DateTimeImmutable $date): array
    {
        $start = $date->setTime(0, 0);
        $end = $date->setTime(23, 59, 59);
        return $this->createQueryBuilder('tc')
            ->where('tc.siteId = :sid')->andWhere('tc.clockInTime BETWEEN :start AND :end')
            ->setParameter('sid', $siteId)->setParameter('start', $start)->setParameter('end', $end)
            ->orderBy('tc.clockInTime', 'DESC')->getQuery()->getResult();
    }

    public function findActiveBySite(string $siteId): array
    {
        return $this->findBy(['siteId' => $siteId, 'status' => TimeClockStatus::CLOCKED_IN]);
    }

    public function findByGuardAndDateRange(string $guardId, \DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        return $this->createQueryBuilder('tc')
            ->where('tc.guardId = :gid')->andWhere('tc.clockInTime >= :start')->andWhere('tc.clockInTime <= :end')
            ->setParameter('gid', $guardId)->setParameter('start', $start)->setParameter('end', $end)
            ->orderBy('tc.clockInTime', 'DESC')->getQuery()->getResult();
    }
}
