<?php

declare(strict_types=1);

namespace Guard51\Repository;

use Guard51\Entity\TenantFeatureModule;

/**
 * @extends BaseRepository<TenantFeatureModule>
 */
class TenantFeatureModuleRepository extends BaseRepository
{
    protected function getEntityClass(): string
    {
        return TenantFeatureModule::class;
    }

    /** @return TenantFeatureModule[] */
    public function findEnabledByTenant(string $tenantId): array
    {
        return $this->findBy(['tenantId' => $tenantId, 'isEnabled' => true]);
    }

    public function findByTenantAndKey(string $tenantId, string $moduleKey): ?TenantFeatureModule
    {
        return $this->findOneBy(['tenantId' => $tenantId, 'moduleKey' => $moduleKey]);
    }

    /** @return string[] */
    public function getEnabledModuleKeys(string $tenantId): array
    {
        return array_map(
            fn(TenantFeatureModule $tfm) => $tfm->getModuleKey(),
            $this->findEnabledByTenant($tenantId)
        );
    }
}
