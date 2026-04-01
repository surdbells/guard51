<?php
declare(strict_types=1);
namespace Guard51\Repository;

use Guard51\Entity\PanicAlert;
use Guard51\Entity\PanicAlertStatus;

/** @extends BaseRepository<PanicAlert> */
class PanicAlertRepository extends BaseRepository
{
    protected function getEntityClass(): string { return PanicAlert::class; }

    public function findActiveByTenant(string $tenantId): array
    {
        return $this->createQueryBuilder('pa')
            ->where('pa.status IN (:statuses)')
            ->setParameter('statuses', [PanicAlertStatus::TRIGGERED->value, PanicAlertStatus::ACKNOWLEDGED->value, PanicAlertStatus::RESPONDING->value])
            ->orderBy('pa.createdAt', 'DESC')->getQuery()->getResult();
    }

    public function findRecentByTenant(string $tenantId, int $hours = 24): array
    {
        $since = new \DateTimeImmutable("-{$hours} hours");
        return $this->createQueryBuilder('pa')
            ->where('pa.tenantId = :tid')->setParameter('tid', $tenantId)
            ->orderBy('pa.createdAt', 'DESC')->getQuery()->getResult();
    }
}
