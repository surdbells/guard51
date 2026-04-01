<?php
declare(strict_types=1);
namespace Guard51\Repository;
use Guard51\Entity\PayrollPeriod;

/** @extends BaseRepository<PayrollPeriod> */
class PayrollPeriodRepository extends BaseRepository
{
    protected function getEntityClass(): string { return PayrollPeriod::class; }
    public function findByTenant(string $tenantId): array { return $this->findBy(['tenantId' => $tenantId, 'tenantId' => $tenantId], ['periodStart' => 'DESC']); }
}
