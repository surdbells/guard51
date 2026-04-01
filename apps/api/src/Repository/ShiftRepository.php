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
            ->andWhere('s.status NOT IN (:terminal)')
            ->setParameter('gid', $guardId)->setParameter('start', $start)->setParameter('end', $end)
            ->setParameter('terminal', [ShiftStatus::CANCELLED->value, ShiftStatus::MISSED->value]);
        if ($excludeId) $qb->andWhere('s.id != :eid')->setParameter('eid', $excludeId);
        return $qb->getQuery()->getResult();
    }
}
