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
        $criteria = ['tenantId' => $tenantId];
        if ($status) $criteria['status'] = GuardStatus::from($status);
        return $this->findBy($criteria, ['lastName' => 'ASC', 'firstName' => 'ASC']);
    }

    public function findByUserId(string $userId): ?Guard
    {
        return $this->findOneBy(['userId' => $userId]);
    }

    public function findByEmployeeNumber(string $tenantId, string $empNum): ?Guard
    {
        return $this->findOneBy(['tenantId' => $tenantId, 'employeeNumber' => $empNum]);
    }

    public function countByTenant(string $tenantId): int { return $this->count(['tenantId' => $tenantId, 'tenantId' => $tenantId]); }
    public function countActiveByTenant(string $tenantId): int { return $this->count(['tenantId' => $tenantId, 'tenantId' => $tenantId, 'status' => GuardStatus::ACTIVE]); }

    public function searchByName(string $tenantId, string $query): array
    {
        $qb = $this->createQueryBuilder('g')
            ->where('LOWER(CONCAT(g.firstName, \' \', g.lastName)) LIKE :q')
            ->setParameter('q', '%' . strtolower($query) . '%')
            ->orderBy('g.lastName', 'ASC');
        return $qb->getQuery()->getResult();
    }
}
