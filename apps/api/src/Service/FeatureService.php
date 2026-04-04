<?php

declare(strict_types=1);

namespace Guard51\Service;

use Doctrine\ORM\EntityManagerInterface;
use Guard51\Entity\FeatureModule;
use Guard51\Entity\TenantFeatureModule;
use Guard51\Entity\TenantType;
use Psr\Log\LoggerInterface;

final class FeatureService
{
    /** @var array<string, bool>|null Runtime cache per request */
    private ?array $enabledCache = null;
    private ?string $cachedTenantId = null;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Check if a module is enabled for the given tenant.
     */
    public function isEnabled(string $tenantId, string $moduleKey): bool
    {
        $this->loadCache($tenantId);
        return $this->enabledCache[$moduleKey] ?? false;
    }

    /**
     * Get all enabled module keys for a tenant.
     * @return string[]
     */
    public function getEnabledModules(string $tenantId): array
    {
        $this->loadCache($tenantId);
        return array_keys(array_filter($this->enabledCache));
    }

    /**
     * Enable a module for a tenant. Auto-enables dependencies.
     * @return string[] List of module keys that were enabled (including deps)
     */
    public function enableModule(string $tenantId, string $moduleKey, string $enabledBy = 'system'): array
    {
        $module = $this->em->getRepository(FeatureModule::class)->findOneBy(['moduleKey' => $moduleKey]);
        if (!$module) {
            throw new \InvalidArgumentException("Module not found: {$moduleKey}");
        }

        $enabled = [];

        // Resolve and enable dependencies first
        foreach ($module->getDependencies() as $depKey) {
            if (!$this->isEnabled($tenantId, $depKey)) {
                $depEnabled = $this->enableModule($tenantId, $depKey, $enabledBy);
                $enabled = array_merge($enabled, $depEnabled);
            }
        }

        // Enable the module itself
        $existing = $this->em->getRepository(TenantFeatureModule::class)
            ->findOneBy(['tenantId' => $tenantId, 'moduleKey' => $moduleKey]);

        if ($existing) {
            if (!$existing->isEnabled()) {
                $existing->enable($enabledBy);
                $this->em->flush();
            }
        } else {
            $tfm = new TenantFeatureModule();
            $tfm->setTenantId($tenantId)
                ->setModuleKey($moduleKey)
                ->enable($enabledBy);
            $this->em->persist($tfm);
            $this->em->flush();
        }

        $enabled[] = $moduleKey;
        $this->clearCache();

        $this->logger->info('Module enabled.', [
            'tenant_id' => $tenantId,
            'module' => $moduleKey,
            'dependencies_enabled' => count($enabled) - 1,
        ]);

        return $enabled;
    }

    /**
     * Disable a module for a tenant. Refuses if other enabled modules depend on it.
     * @throws \InvalidArgumentException if module has active dependents
     */
    public function disableModule(string $tenantId, string $moduleKey): void
    {
        $module = $this->em->getRepository(FeatureModule::class)->findOneBy(['moduleKey' => $moduleKey]);
        if (!$module) {
            throw new \InvalidArgumentException("Module not found: {$moduleKey}");
        }

        if ($module->isCore()) {
            throw new \InvalidArgumentException("Cannot disable core module: {$moduleKey}");
        }

        // Check for dependent modules that are currently enabled
        $dependents = $this->findEnabledDependents($tenantId, $moduleKey);
        if (!empty($dependents)) {
            throw new \InvalidArgumentException(sprintf(
                'Cannot disable %s. The following enabled modules depend on it: %s',
                $moduleKey,
                implode(', ', $dependents)
            ));
        }

        $existing = $this->em->getRepository(TenantFeatureModule::class)
            ->findOneBy(['tenantId' => $tenantId, 'moduleKey' => $moduleKey]);

        if ($existing && $existing->isEnabled()) {
            $existing->disable();
            $this->em->flush();
            $this->clearCache();
        }
    }

    /**
     * Sync a tenant's modules based on their subscription plan's included modules.
     * Enables all plan modules + core modules, disables non-plan non-core modules.
     * @param string[] $planModuleKeys
     */
    public function syncWithPlan(string $tenantId, array $planModuleKeys, TenantType $tenantType): void
    {
        // Get all modules available for this tenant type
        $allModules = $this->em->getRepository(FeatureModule::class)->findBy(['isActive' => true]);

        foreach ($allModules as $module) {
            if (!$module->isAvailableForTenantType($tenantType)) {
                continue;
            }

            $shouldBeEnabled = $module->isCore() || in_array($module->getModuleKey(), $planModuleKeys, true);

            if ($shouldBeEnabled) {
                $this->enableModule($tenantId, $module->getModuleKey(), 'plan_sync');
            }
        }

        $this->clearCache();
    }

    /**
     * Get all modules with their enabled status for a tenant.
     * Used by the frontend sidebar to show/hide menu items.
     */
    public function getModulesForTenant(string $tenantId, TenantType $tenantType): array
    {
        $allModules = $this->em->getRepository(FeatureModule::class)->findBy(
            ['isActive' => true],
            ['sortOrder' => 'ASC']
        );

        $this->loadCache($tenantId);
        $hasAnyRecords = !empty($this->enabledCache);

        $result = [];
        foreach ($allModules as $module) {
            if (!$module->isAvailableForTenantType($tenantType)) {
                continue;
            }

            $data = $module->toArray();
            // If tenant has no feature records yet, default ALL modules to enabled
            // If tenant has records, use the cache (explicitly disabled = false)
            if (!$hasAnyRecords) {
                $data['is_enabled'] = true;
            } else {
                $data['is_enabled'] = $this->enabledCache[$module->getModuleKey()] ?? $module->isCore();
            }
            $result[] = $data;
        }

        return $result;
    }

    /**
     * Find enabled modules that depend on the given module key.
     * @return string[]
     */
    private function findEnabledDependents(string $tenantId, string $moduleKey): array
    {
        $allModules = $this->em->getRepository(FeatureModule::class)->findBy(['isActive' => true]);
        $dependents = [];

        foreach ($allModules as $module) {
            if (in_array($moduleKey, $module->getDependencies(), true)) {
                if ($this->isEnabled($tenantId, $module->getModuleKey())) {
                    $dependents[] = $module->getModuleKey();
                }
            }
        }

        return $dependents;
    }

    private function loadCache(string $tenantId): void
    {
        if ($this->enabledCache !== null && $this->cachedTenantId === $tenantId) {
            return;
        }

        $this->enabledCache = [];
        $this->cachedTenantId = $tenantId;

        // Load all core modules as enabled by default
        $coreModules = $this->em->getRepository(FeatureModule::class)->findBy(['isCore' => true, 'isActive' => true]);
        foreach ($coreModules as $module) {
            $this->enabledCache[$module->getModuleKey()] = true;
        }

        // Load tenant-specific enabled modules
        $tenantModules = $this->em->getRepository(TenantFeatureModule::class)
            ->findBy(['tenantId' => $tenantId, 'isEnabled' => true]);

        foreach ($tenantModules as $tfm) {
            $this->enabledCache[$tfm->getModuleKey()] = true;
        }
    }

    private function clearCache(): void
    {
        $this->enabledCache = null;
        $this->cachedTenantId = null;
    }
}
