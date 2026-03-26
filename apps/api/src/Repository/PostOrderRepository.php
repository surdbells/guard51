<?php

declare(strict_types=1);

namespace Guard51\Repository;

use Guard51\Entity\PostOrder;

/**
 * @extends BaseRepository<PostOrder>
 */
class PostOrderRepository extends BaseRepository
{
    protected function getEntityClass(): string
    {
        return PostOrder::class;
    }

    /** @return PostOrder[] */
    public function findBySite(string $siteId): array
    {
        return $this->findBy(['siteId' => $siteId], ['priority' => 'DESC', 'createdAt' => 'DESC']);
    }

    /** @return PostOrder[] Active and currently effective orders for a site */
    public function findEffectiveBySite(string $siteId): array
    {
        $now = new \DateTimeImmutable();
        $qb = $this->createQueryBuilder('po')
            ->where('po.siteId = :sid')
            ->andWhere('po.isActive = :active')
            ->andWhere('po.effectiveFrom <= :now')
            ->andWhere('po.effectiveTo IS NULL OR po.effectiveTo >= :now')
            ->setParameter('sid', $siteId)
            ->setParameter('active', true)
            ->setParameter('now', $now)
            ->orderBy('po.priority', 'DESC')
            ->addOrderBy('po.createdAt', 'DESC');
        return $qb->getQuery()->getResult();
    }

    public function countBySite(string $siteId): int
    {
        return $this->count(['siteId' => $siteId]);
    }
}
