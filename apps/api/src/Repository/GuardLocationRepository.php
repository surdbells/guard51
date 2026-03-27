<?php
declare(strict_types=1);
namespace Guard51\Repository;

use Guard51\Entity\GuardLocation;

/** @extends BaseRepository<GuardLocation> */
class GuardLocationRepository extends BaseRepository
{
    protected function getEntityClass(): string { return GuardLocation::class; }

    public function findLatestByGuard(string $guardId): ?GuardLocation
    {
        return $this->createQueryBuilder('gl')
            ->where('gl.guardId = :gid')->setParameter('gid', $guardId)
            ->orderBy('gl.recordedAt', 'DESC')->setMaxResults(1)->getQuery()->getOneOrNullResult();
    }

    /** @return GuardLocation[] */
    public function findActiveGuardLocations(string $tenantId): array
    {
        // Latest location per guard within last 15 minutes
        $since = new \DateTimeImmutable('-15 minutes');
        $conn = $this->em->getConnection();
        $sql = "SELECT DISTINCT ON (guard_id) * FROM guard_locations WHERE tenant_id = ? AND recorded_at > ? ORDER BY guard_id, recorded_at DESC";
        return $conn->fetchAllAssociative($sql, [$tenantId, $since->format('Y-m-d H:i:s')]);
    }

    /** @return GuardLocation[] Path replay for a guard */
    public function findPath(string $guardId, \DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        return $this->createQueryBuilder('gl')
            ->where('gl.guardId = :gid')->andWhere('gl.recordedAt >= :start')->andWhere('gl.recordedAt <= :end')
            ->setParameter('gid', $guardId)->setParameter('start', $start)->setParameter('end', $end)
            ->orderBy('gl.recordedAt', 'ASC')->getQuery()->getResult();
    }

    /** Bulk insert for performance */
    public function bulkInsert(array $locations): int
    {
        $count = 0;
        foreach ($locations as $loc) {
            $this->em->persist($loc);
            $count++;
            if ($count % 100 === 0) $this->em->flush();
        }
        $this->em->flush();
        return $count;
    }
}
