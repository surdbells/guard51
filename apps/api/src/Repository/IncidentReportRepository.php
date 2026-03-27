<?php
declare(strict_types=1);
namespace Guard51\Repository;

use Guard51\Entity\IncidentReport;

/** @extends BaseRepository<IncidentReport> */
class IncidentReportRepository extends BaseRepository
{
    protected function getEntityClass(): string { return IncidentReport::class; }

    public function findActiveByTenant(string $tenantId): array
    {
        return $this->createQueryBuilder('i')->where('i.tenantId = :tid')
            ->andWhere('i.status NOT IN (:terminal)')
            ->setParameter('tid', $tenantId)->setParameter('terminal', ['resolved', 'closed'])
            ->orderBy('i.reportedAt', 'DESC')->getQuery()->getResult();
    }

    public function findByTenantFiltered(string $tenantId, ?string $siteId = null, ?string $severity = null, ?string $status = null, int $limit = 50): array
    {
        $qb = $this->createQueryBuilder('i')->where('i.tenantId = :tid')->setParameter('tid', $tenantId);
        if ($siteId) $qb->andWhere('i.siteId = :sid')->setParameter('sid', $siteId);
        if ($severity) $qb->andWhere('i.severity = :sev')->setParameter('sev', $severity);
        if ($status) $qb->andWhere('i.status = :status')->setParameter('status', $status);
        return $qb->orderBy('i.reportedAt', 'DESC')->setMaxResults($limit)->getQuery()->getResult();
    }
}
