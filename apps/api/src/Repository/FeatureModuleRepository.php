<?php

declare(strict_types=1);

namespace Guard51\Repository;

use Guard51\Entity\FeatureModule;
use Guard51\Entity\SubscriptionTier;
use Guard51\Entity\TenantType;

/**
 * @extends BaseRepository<FeatureModule>
 */
class FeatureModuleRepository extends BaseRepository
{
    protected function getEntityClass(): string
    {
        return FeatureModule::class;
    }

    /** @return FeatureModule[] */
    public function findCoreModules(): array
    {
        return $this->findBy(['isCore' => true, 'isActive' => true], ['sortOrder' => 'ASC']);
    }

    /** @return FeatureModule[] */
    public function findByCategory(string $category): array
    {
        return $this->findBy(['category' => $category, 'isActive' => true], ['sortOrder' => 'ASC']);
    }

    /** @return FeatureModule[] */
    public function findByTier(SubscriptionTier $tier): array
    {
        $qb = $this->createQueryBuilder('fm')
            ->where('fm.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('fm.sortOrder', 'ASC');
        return $qb->getQuery()->getResult();
    }

    public function findByKey(string $moduleKey): ?FeatureModule
    {
        return $this->findOneBy(['moduleKey' => $moduleKey]);
    }

    /** @return string[] All unique categories */
    public function getCategories(): array
    {
        $qb = $this->createQueryBuilder('fm')
            ->select('DISTINCT fm.category')
            ->where('fm.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('fm.category', 'ASC');
        return array_column($qb->getQuery()->getScalarResult(), 'category');
    }
}
