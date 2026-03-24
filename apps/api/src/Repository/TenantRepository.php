<?php

declare(strict_types=1);

namespace Guard51\Repository;

use Guard51\Entity\Tenant;
use Guard51\Entity\TenantStatus;
use Guard51\Entity\TenantType;

/**
 * @extends BaseRepository<Tenant>
 */
class TenantRepository extends BaseRepository
{
    protected function getEntityClass(): string
    {
        return Tenant::class;
    }

    public function findByEmail(string $email): ?Tenant
    {
        return $this->findOneBy(['email' => $email]);
    }

    public function findByCustomDomain(string $domain): ?Tenant
    {
        return $this->findOneBy(['customDomain' => $domain]);
    }

    /**
     * @return Tenant[]
     */
    public function findActive(): array
    {
        return $this->findBy(['status' => TenantStatus::ACTIVE], ['name' => 'ASC']);
    }

    /**
     * @return Tenant[]
     */
    public function findByType(TenantType $type): array
    {
        return $this->findBy(['tenantType' => $type], ['name' => 'ASC']);
    }

    /**
     * @return Tenant[]
     */
    public function findGovernmentTenants(): array
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.tenantType IN (:types)')
            ->setParameter('types', array_map(fn($t) => $t->value, TenantType::governmentTypes()))
            ->orderBy('t.name', 'ASC');

        return $qb->getQuery()->getResult();
    }

    public function countByStatus(TenantStatus $status): int
    {
        return $this->count(['status' => $status]);
    }

    public function countByType(TenantType $type): int
    {
        return $this->count(['tenantType' => $type]);
    }

    /**
     * Search tenants by name (partial match).
     * @return Tenant[]
     */
    public function search(string $query, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('t')
            ->where('LOWER(t.name) LIKE :query')
            ->setParameter('query', '%' . strtolower($query) . '%')
            ->orderBy('t.name', 'ASC')
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }
}
