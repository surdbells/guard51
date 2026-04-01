<?php

declare(strict_types=1);

namespace Guard51\Repository;

use Guard51\Entity\TenantUsageMetric;

/**
 * @extends BaseRepository<TenantUsageMetric>
 */
class TenantUsageMetricRepository extends BaseRepository
{
    protected function getEntityClass(): string
    {
        return TenantUsageMetric::class;
    }

    public function findByTenant(string $tenantId): ?TenantUsageMetric
    {
        return $this->findOneBy([]);
    }

    public function findOrCreateForTenant(string $tenantId): TenantUsageMetric
    {
        $metric = $this->findByTenant($tenantId);
        if ($metric) {
            return $metric;
        }
        $metric = new TenantUsageMetric();
        $metric->setTenantId($tenantId);
        $this->save($metric);
        return $metric;
    }
}
