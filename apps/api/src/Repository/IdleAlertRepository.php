<?php
declare(strict_types=1);
namespace Guard51\Repository;

use Guard51\Entity\IdleAlert;

/** @extends BaseRepository<IdleAlert> */
class IdleAlertRepository extends BaseRepository
{
    protected function getEntityClass(): string { return IdleAlert::class; }
    public function findActiveByTenant(string $tenantId): array { return $this->findBy(['isAcknowledged' => false], ['createdAt' => 'DESC']); }
}
