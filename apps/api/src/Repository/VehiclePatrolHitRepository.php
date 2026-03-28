<?php
declare(strict_types=1);
namespace Guard51\Repository;
use Guard51\Entity\VehiclePatrolHit;

/** @extends BaseRepository<VehiclePatrolHit> */
class VehiclePatrolHitRepository extends BaseRepository
{
    protected function getEntityClass(): string { return VehiclePatrolHit::class; }

    public function findByRoute(string $routeId, ?string $date = null): array
    {
        $qb = $this->createQueryBuilder('h')->where('h.routeId = :rid')->setParameter('rid', $routeId);
        if ($date) $qb->andWhere('DATE(h.recordedAt) = :d')->setParameter('d', $date);
        return $qb->orderBy('h.recordedAt', 'DESC')->getQuery()->getResult();
    }

    public function countTodayHits(string $routeId): int
    {
        $today = (new \DateTimeImmutable())->format('Y-m-d');
        return (int) $this->createQueryBuilder('h')->select('COUNT(h.id)')
            ->where('h.routeId = :rid')->andWhere('DATE(h.recordedAt) = :d')
            ->setParameter('rid', $routeId)->setParameter('d', $today)
            ->getQuery()->getSingleScalarResult();
    }
}
