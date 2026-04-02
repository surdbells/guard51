<?php

declare(strict_types=1);

namespace Guard51\Repository;

use Guard51\Entity\SubscriptionInvoice;

/**
 * @extends BaseRepository<SubscriptionInvoice>
 */
class SubscriptionInvoiceRepository extends BaseRepository
{
    protected function getEntityClass(): string
    {
        return SubscriptionInvoice::class;
    }

    public function findLatestBySubscription(string $subscriptionId): ?SubscriptionInvoice
    {
        $results = $this->findBy(
            ['subscriptionId' => $subscriptionId],
            ['createdAt' => 'DESC'],
            1
        );
        return $results[0] ?? null;
    }

    /** @return SubscriptionInvoice[] */
    public function findByTenant(string $tenantId): array
    {
        return $this->findBy(['tenantId' => $tenantId], ['createdAt' => 'DESC']);
    }
}
