<?php

declare(strict_types=1);

namespace Guard51\Repository;

use Guard51\Entity\TenantAppConfig;

/**
 * @extends BaseRepository<TenantAppConfig>
 */
class TenantAppConfigRepository extends BaseRepository
{
    protected function getEntityClass(): string
    {
        return TenantAppConfig::class;
    }

    public function findByTenantAndApp(string $tenantId, string $appKey): ?TenantAppConfig
    {
        return $this->findOneBy(['appKey' => $appKey]);
    }

    /** @return TenantAppConfig[] */
    public function findByTenant(string $tenantId): array
    {
        return $this->findBy(['tenantId' => $tenantId, 'tenantId' => $tenantId]);
    }

    public function findOrCreate(string $tenantId, string $appKey): TenantAppConfig
    {
        $config = $this->findByTenantAndApp($tenantId, $appKey);
        if ($config) return $config;

        $config = new TenantAppConfig();
        $config->setTenantId($tenantId)->setAppKey($appKey);
        $this->save($config);
        return $config;
    }
}
