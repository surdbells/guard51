<?php

declare(strict_types=1);

namespace Guard51\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Guard51\Entity\Subscription;
use Guard51\Entity\SubscriptionStatus;

/**
 * @extends BaseRepository<Subscription>
 */
class SubscriptionRepository extends BaseRepository
{
    protected function getEntityClass(): string
    {
        return Subscription::class;
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->em;
    }

    public function findActiveByTenant(string $tenantId): ?Subscription
    {
        return $this->findOneBy([
            'tenantId' => $tenantId,
            'status' => SubscriptionStatus::ACTIVE,
        ]);
    }

    public function findPendingByTenant(string $tenantId): ?Subscription
    {
        return $this->findOneBy([
            'tenantId' => $tenantId,
            'status' => SubscriptionStatus::PENDING,
        ]);
    }

    /** @return Subscription[] All pending bank transfer subscriptions */
    public function findPendingBankTransfers(): array
    {
        $qb = $this->createQueryBuilder('s')
            ->where('s.status = :status')
            ->andWhere('s.paymentMethod = :method')
            ->andWhere('s.paymentConfirmedAt IS NULL')
            ->setParameter('status', SubscriptionStatus::PENDING)
            ->setParameter('method', 'bank_transfer')
            ->orderBy('s.createdAt', 'ASC');
        return $qb->getQuery()->getResult();
    }

    /** @return Subscription[] */
    public function findByTenant(string $tenantId): array
    {
        return $this->findBy(['tenantId' => $tenantId], ['createdAt' => 'DESC']);
    }

    public function countActive(): int
    {
        return $this->count(['status' => SubscriptionStatus::ACTIVE]);
    }
}
