<?php
declare(strict_types=1);
namespace Guard51\Repository;

use Guard51\Entity\DispatchCall;

/** @extends BaseRepository<DispatchCall> */
class DispatchCallRepository extends BaseRepository
{
    protected function getEntityClass(): string { return DispatchCall::class; }

    public function findActiveByTenant(string $tenantId): array
    {
        return $this->createQueryBuilder('d')->where('d.status IN (:active)')->setParameter('active', ['received', 'dispatched', 'in_progress'])
            ->orderBy('d.receivedAt', 'DESC')->getQuery()->getResult();
    }

    public function findByTenantRecent(string $tenantId, int $hours = 24): array
    {
        $since = new \DateTimeImmutable("-{$hours} hours");
        return $this->createQueryBuilder('d')->where('d.tenantId = :tid')->andWhere('d.receivedAt > :since')->setParameter('since', $since)
            ->orderBy('d.receivedAt', 'DESC')->getQuery()->getResult();
    }
}
