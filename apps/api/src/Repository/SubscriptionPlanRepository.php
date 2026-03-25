<?php

declare(strict_types=1);

namespace Guard51\Repository;

use Guard51\Entity\SubscriptionPlan;
use Guard51\Entity\SubscriptionTier;
use Guard51\Entity\TenantType;

/**
 * @extends BaseRepository<SubscriptionPlan>
 */
class SubscriptionPlanRepository extends BaseRepository
{
    protected function getEntityClass(): string
    {
        return SubscriptionPlan::class;
    }

    /** @return SubscriptionPlan[] Public plans (no private tenant) */
    public function findPublicActive(): array
    {
        $qb = $this->createQueryBuilder('p')
            ->where('p.isActive = :active')
            ->andWhere('p.privateTenantId IS NULL')
            ->setParameter('active', true)
            ->orderBy('p.sortOrder', 'ASC');
        return $qb->getQuery()->getResult();
    }

    /** @return SubscriptionPlan[] Plans available for a specific tenant type */
    public function findForTenantType(TenantType $type): array
    {
        $plans = $this->findPublicActive();
        return array_filter($plans, fn(SubscriptionPlan $p) => $p->isAvailableForTenantType($type));
    }

    /** @return SubscriptionPlan[] Private plans for a specific tenant */
    public function findPrivateForTenant(string $tenantId): array
    {
        return $this->findBy([
            'privateTenantId' => $tenantId,
            'isActive' => true,
        ], ['sortOrder' => 'ASC']);
    }

    public function findByTier(SubscriptionTier $tier): array
    {
        return $this->findBy(['tier' => $tier, 'isActive' => true], ['sortOrder' => 'ASC']);
    }
}
