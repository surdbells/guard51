<?php
declare(strict_types=1);
namespace Guard51\Repository;
use Guard51\Entity\PayRateMultiplier;

/** @extends BaseRepository<PayRateMultiplier> */
class PayRateMultiplierRepository extends BaseRepository
{
    protected function getEntityClass(): string { return PayRateMultiplier::class; }
    public function findByTenant(string $tenantId): array { return $this->findBy(['tenantId' => $tenantId, 'tenantId' => $tenantId], ['name' => 'ASC']); }
    public function findActiveByTenant(string $tenantId): array { return $this->findBy(['tenantId' => $tenantId, 'isActive' => true]); }
}
