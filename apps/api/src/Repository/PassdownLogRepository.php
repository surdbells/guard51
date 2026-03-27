<?php
declare(strict_types=1);
namespace Guard51\Repository;
use Guard51\Entity\PassdownLog;

/** @extends BaseRepository<PassdownLog> */
class PassdownLogRepository extends BaseRepository
{
    protected function getEntityClass(): string { return PassdownLog::class; }

    public function findBySite(string $siteId, int $limit = 20): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.siteId = :sid')->setParameter('sid', $siteId)
            ->orderBy('p.createdAt', 'DESC')->setMaxResults($limit)->getQuery()->getResult();
    }

    public function findUnacknowledged(string $tenantId): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.tenantId = :tid')->andWhere('p.acknowledgedAt IS NULL')
            ->setParameter('tid', $tenantId)->orderBy('p.createdAt', 'DESC')->getQuery()->getResult();
    }
}
