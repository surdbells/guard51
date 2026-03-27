<?php
declare(strict_types=1);
namespace Guard51\Repository;

use Guard51\Entity\WatchModeLog;

/** @extends BaseRepository<WatchModeLog> */
class WatchModeLogRepository extends BaseRepository
{
    protected function getEntityClass(): string { return WatchModeLog::class; }

    public function findBySite(string $siteId, int $limit = 50): array
    {
        return $this->createQueryBuilder('w')->where('w.siteId = :sid')->setParameter('sid', $siteId)
            ->orderBy('w.recordedAt', 'DESC')->setMaxResults($limit)->getQuery()->getResult();
    }

    public function findByTenantRecent(string $tenantId, int $hours = 24): array
    {
        $since = new \DateTimeImmutable("-{$hours} hours");
        return $this->createQueryBuilder('w')->where('w.tenantId = :tid')->andWhere('w.recordedAt > :since')
            ->setParameter('tid', $tenantId)->setParameter('since', $since)
            ->orderBy('w.recordedAt', 'DESC')->getQuery()->getResult();
    }
}
