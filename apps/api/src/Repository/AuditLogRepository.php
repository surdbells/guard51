<?php

declare(strict_types=1);

namespace Guard51\Repository;

use Guard51\Entity\AuditLog;

/**
 * @extends BaseRepository<AuditLog>
 */
class AuditLogRepository extends BaseRepository
{
    protected function getEntityClass(): string
    {
        return AuditLog::class;
    }

    /**
     * @return AuditLog[]
     */
    public function findByEntity(string $entityType, string $entityId, int $limit = 50): array
    {
        $qb = $this->createQueryBuilder('a')
            ->where('a.entityType = :type')
            ->andWhere('a.entityId = :id')
            ->setParameter('type', $entityType)
            ->setParameter('id', $entityId)
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * @return AuditLog[]
     */
    public function findByTenant(string $tenantId, int $page = 1, int $perPage = 50): array
    {
        $qb = $this->createQueryBuilder('a')
            ->where('a.tenantId = :tenantId')
            ->setParameter('tenantId', $tenantId)
            ->orderBy('a.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage);

        return $qb->getQuery()->getResult();
    }

    /**
     * @return AuditLog[]
     */
    public function findByUser(string $userId, int $limit = 50): array
    {
        $qb = $this->createQueryBuilder('a')
            ->where('a.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }
}
