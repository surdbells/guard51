<?php
declare(strict_types=1);
namespace Guard51\Repository;

use Guard51\Entity\GeofenceAlert;

/** @extends BaseRepository<GeofenceAlert> */
class GeofenceAlertRepository extends BaseRepository
{
    protected function getEntityClass(): string { return GeofenceAlert::class; }

    public function findActiveByTenant(string $tenantId): array
    {
        return $this->findBy(['tenantId' => $tenantId, 'isAcknowledged' => false], ['createdAt' => 'DESC']);
    }

    public function findByTenantRecent(string $tenantId, int $hours = 24): array
    {
        $since = new \DateTimeImmutable("-{$hours} hours");
        return $this->createQueryBuilder('ga')
            ->where('ga.tenantId = :tid')->setParameter('tid', $tenantId)
            ->orderBy('ga.createdAt', 'DESC')->getQuery()->getResult();
    }
}
