<?php

declare(strict_types=1);

namespace Guard51\Repository;

use Guard51\Entity\DailySnapshot;

/** @extends BaseRepository<DailySnapshot> */
class DailySnapshotRepository extends BaseRepository
{
    protected function getEntityClass(): string { return DailySnapshot::class; }

    public function findByTenantAndDate(string $tenantId, \DateTimeImmutable $date): ?DailySnapshot
    {
        return $this->findOneBy(['tenantId' => $tenantId, 'snapshotDate' => $date]);
    }

    /** @return DailySnapshot[] */
    public function findRange(string $tenantId, int $days = 30): array
    {
        $since = new \DateTimeImmutable("-{$days} days");
        $qb = $this->createQueryBuilder('s')
            ->where('s.snapshotDate >= :since')
            ->setParameter('since', $since)
            ->orderBy('s.snapshotDate', 'ASC');
        return $qb->getQuery()->getResult();
    }
}
