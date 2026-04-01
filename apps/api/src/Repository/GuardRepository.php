<?php

declare(strict_types=1);

namespace Guard51\Repository;

use Guard51\Entity\Guard;
use Guard51\Entity\GuardStatus;

/** @extends BaseRepository<Guard> */
class GuardRepository extends BaseRepository
{
    protected function getEntityClass(): string { return Guard::class; }

    /** @return Guard[] */
    public function findByTenant(string $tenantId, ?string $status = null): array
    {
        // Note: TenantFilter automatically scopes by tenant_id — no need to add it explicitly
        $criteria = [];
        if ($status) $criteria['status'] = GuardStatus::from($status);
        return $this->findBy($criteria, ['lastName' => 'ASC', 'firstName' => 'ASC']);
    }

    public function findByUserId(string $userId): ?Guard
    {
        return $this->findOneBy(['userId' => $userId]);
    }

    public function findByEmployeeNumber(string $tenantId, string $empNum): ?Guard
    {
        return $this->findOneBy(['employeeNumber' => $empNum]);
    }

    public function countByTenant(string $tenantId): int { return $this->count([]); }
    public function countActiveByTenant(string $tenantId): int { return $this->count(['status' => GuardStatus::ACTIVE]); }

    public function searchByName(string $tenantId, string $query): array
    {
        $qb = $this->createQueryBuilder('g')
            ->where('LOWER(CONCAT(g.firstName, \' \', g.lastName)) LIKE :q')
            ->setParameter('q', '%' . strtolower($query) . '%')
            ->orderBy('g.lastName', 'ASC');
        return $qb->getQuery()->getResult();
    }
}
