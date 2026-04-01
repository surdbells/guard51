<?php
declare(strict_types=1);
namespace Guard51\Repository;

use Guard51\Entity\DailyActivityReport;

/** @extends BaseRepository<DailyActivityReport> */
class DailyActivityReportRepository extends BaseRepository
{
    protected function getEntityClass(): string { return DailyActivityReport::class; }

    public function findByTenantAndDate(string $tenantId, \DateTimeImmutable $date): array
    {
        return $this->findBy(['reportDate' => $date], ['createdAt' => 'DESC']);
    }

    public function findByGuard(string $guardId, int $limit = 20): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.guardId = :gid')->setParameter('gid', $guardId)
            ->orderBy('d.reportDate', 'DESC')->setMaxResults($limit)->getQuery()->getResult();
    }

    public function findByTenantFiltered(string $tenantId, ?string $siteId = null, ?string $guardId = null, ?string $status = null, int $limit = 50): array
    {
        $qb = $this->createQueryBuilder('d')->where('d.tenantId = :tid');
        if ($siteId) $qb->andWhere('d.siteId = :sid')->setParameter('sid', $siteId);
        if ($guardId) $qb->andWhere('d.guardId = :gid')->setParameter('gid', $guardId);
        if ($status) $qb->andWhere('d.status = :status')->setParameter('status', $status);
        return $qb->orderBy('d.reportDate', 'DESC')->setMaxResults($limit)->getQuery()->getResult();
    }
}
