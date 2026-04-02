<?php
declare(strict_types=1);
namespace Guard51\Repository;
use Guard51\Entity\AuditLog;

/** @extends BaseRepository<AuditLog> */
class AuditLogRepository extends BaseRepository
{
    protected function getEntityClass(): string { return AuditLog::class; }
    public function findByTenant(string $tenantId, int $limit = 100): array
    {
        return $this->createQueryBuilder('a')->where('a.tenantId = :tid')->setParameter('tid', $tenantId)
            ->orderBy('a.createdAt', 'DESC')->setMaxResults($limit)->getQuery()->getResult();
    }

    public function findByUser(string $userId, int $limit = 50): array
    {
        return $this->createQueryBuilder('a')->where('a.userId = :uid')->setParameter('uid', $userId)
            ->orderBy('a.createdAt', 'DESC')->setMaxResults($limit)->getQuery()->getResult();
    }
}
