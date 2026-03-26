<?php

declare(strict_types=1);

namespace Guard51\Repository;

use Guard51\Entity\Site;
use Guard51\Entity\SiteStatus;

/**
 * @extends BaseRepository<Site>
 */
class SiteRepository extends BaseRepository
{
    protected function getEntityClass(): string
    {
        return Site::class;
    }

    /** @return Site[] */
    public function findByTenant(string $tenantId, ?string $status = null): array
    {
        $criteria = ['tenantId' => $tenantId];
        if ($status) $criteria['status'] = SiteStatus::from($status);
        return $this->findBy($criteria, ['name' => 'ASC']);
    }

    /** @return Site[] */
    public function findActiveByTenant(string $tenantId): array
    {
        return $this->findBy(['tenantId' => $tenantId, 'status' => SiteStatus::ACTIVE], ['name' => 'ASC']);
    }

    /** @return Site[] */
    public function findByClient(string $clientId): array
    {
        return $this->findBy(['clientId' => $clientId], ['name' => 'ASC']);
    }

    public function countByTenant(string $tenantId): int
    {
        return $this->count(['tenantId' => $tenantId]);
    }

    /** @return Site[] Sites with coordinates for map display */
    public function findWithCoordinates(string $tenantId): array
    {
        $qb = $this->createQueryBuilder('s')
            ->where('s.tenantId = :tid')
            ->andWhere('s.latitude IS NOT NULL')
            ->andWhere('s.longitude IS NOT NULL')
            ->andWhere('s.status = :status')
            ->setParameter('tid', $tenantId)
            ->setParameter('status', SiteStatus::ACTIVE)
            ->orderBy('s.name', 'ASC');
        return $qb->getQuery()->getResult();
    }

    public function searchByName(string $tenantId, string $query): array
    {
        $qb = $this->createQueryBuilder('s')
            ->where('s.tenantId = :tid')
            ->andWhere('LOWER(s.name) LIKE :q')
            ->setParameter('tid', $tenantId)
            ->setParameter('q', '%' . strtolower($query) . '%')
            ->orderBy('s.name', 'ASC');
        return $qb->getQuery()->getResult();
    }
}
