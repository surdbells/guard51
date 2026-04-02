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
            ->where('s.tenantId = :tid')->setParameter('tid', $tenantId)
            ->andWhere('s.shiftDate >= :start')->setParameter('start', $start)
            ->andWhere('s.shiftDate <= :end')->setParameter('end', $end);
        if ($siteId) $qb->andWhere('s.siteId = :sid')->setParameter('sid', $siteId);
        if ($guardId) $qb->andWhere('s.guardId = :gid')->setParameter('gid', $guardId);
        return $qb->orderBy('s.shiftDate', 'ASC')->addOrderBy('s.startTime', 'ASC')->getQuery()->getResult();
    }

    public function findOpenShifts(string $tenantId): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.tenantId = :tid')->setParameter('tid', $tenantId)
            ->andWhere('s.isOpen = true')
            ->andWhere('s.status = :status')->setParameter('status', ShiftStatus::SCHEDULED)
            ->orderBy('s.shiftDate', 'ASC')->getQuery()->getResult();
    }

    public function findByGuardAndDate(string $guardId, \DateTimeImmutable $date): array
    {
        return $this->findBy(['guardId' => $guardId, 'shiftDate' => $date], ['startTime' => 'ASC']);
    }

    public function findConflicting(string $tenantId, string $guardId, \DateTimeImmutable $date, string $startTime, string $endTime, ?string $excludeId = null): array
    {
        $qb = $this->createQueryBuilder('s')
            ->where('s.tenantId = :tid')->setParameter('tid', $tenantId)
            ->andWhere('s.guardId = :gid')->setParameter('gid', $guardId)
            ->andWhere('s.shiftDate = :date')->setParameter('date', $date)
            ->andWhere('s.startTime < :end')->setParameter('end', $endTime)
            ->andWhere('s.endTime > :start')->setParameter('start', $startTime)
            ->andWhere('s.status NOT IN (:terminal)')
            ->setParameter('terminal', [ShiftStatus::CANCELLED->value, ShiftStatus::MISSED->value]);
        if ($excludeId) $qb->andWhere('s.id != :eid')->setParameter('eid', $excludeId);
        return $qb->getQuery()->getResult();
    }

    public function countByTenant(string $tenantId): int { return $this->count(['tenantId' => $tenantId]); }
}
