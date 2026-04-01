<?php
declare(strict_types=1);
namespace Guard51\Repository;
use Guard51\Entity\Shift;
use Guard51\Entity\ShiftStatus;

/** @extends BaseRepository<Shift> */
class ShiftRepository extends BaseRepository
{
    protected function getEntityClass(): string { return Shift::class; }

    public function findByTenantAndDateRange(string $tenantId, \DateTimeImmutable $start, \DateTimeImmutable $end, ?string $siteId = null, ?string $guardId = null): array
    {
        $qb = $this->createQueryBuilder('s')
            ->where('s.tenantId = :tid')->andWhere('s.shiftDate >= :start')->andWhere('s.shiftDate <= :end')->setParameter('start', $start)->setParameter('end', $end);
        if ($siteId) $qb->andWhere('s.siteId = :sid')->setParameter('sid', $siteId);
        if ($guardId) $qb->andWhere('s.guardId = :gid')->setParameter('gid', $guardId);
        return $qb->orderBy('s.shiftDate', 'ASC')->addOrderBy('s.startTime', 'ASC')->getQuery()->getResult();
    }

    public function findOpenShifts(string $tenantId): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.tenantId = :tid')->andWhere('s.isOpen = true')->andWhere('s.status = :status')
            ->andWhere('s.shiftDate >= :today')->setParameter('status', ShiftStatus::PUBLISHED)
            ->setParameter('today', new \DateTimeImmutable('today'))
            ->orderBy('s.shiftDate', 'ASC')->getQuery()->getResult();
    }

    public function findByGuardAndDate(string $guardId, \DateTimeImmutable $date): array
    {
        return $this->findBy(['guardId' => $guardId, 'shiftDate' => $date]);
    }

    public function findConflicts(string $guardId, \DateTimeImmutable $start, \DateTimeImmutable $end, ?string $excludeId = null): array
    {
        $qb = $this->createQueryBuilder('s')
            ->where('s.guardId = :gid')->andWhere('s.startTime < :end')->andWhere('s.endTime > :start')
            ->andWhere('s.status NOT IN (:terminal)')
            ->setParameter('gid', $guardId)->setParameter('start', $start)->setParameter('end', $end)
            ->setParameter('terminal', [ShiftStatus::CANCELLED->value, ShiftStatus::MISSED->value]);
        if ($excludeId) $qb->andWhere('s.id != :eid')->setParameter('eid', $excludeId);
        return $qb->getQuery()->getResult();
    }
}
