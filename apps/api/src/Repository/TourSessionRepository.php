<?php
declare(strict_types=1);
namespace Guard51\Repository;

use Guard51\Entity\TourSession;

/** @extends BaseRepository<TourSession> */
class TourSessionRepository extends BaseRepository
{
    protected function getEntityClass(): string { return TourSession::class; }

    public function findBySite(string $siteId, int $limit = 20): array
    {
        return $this->createQueryBuilder('ts')
            ->where('ts.siteId = :sid')->setParameter('sid', $siteId)
            ->orderBy('ts.startedAt', 'DESC')->setMaxResults($limit)->getQuery()->getResult();
    }

    public function findByGuard(string $guardId, int $limit = 20): array
    {
        return $this->createQueryBuilder('ts')
            ->where('ts.guardId = :gid')->setParameter('gid', $guardId)
            ->orderBy('ts.startedAt', 'DESC')->setMaxResults($limit)->getQuery()->getResult();
    }
}
