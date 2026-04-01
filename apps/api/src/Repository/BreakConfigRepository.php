<?php
declare(strict_types=1);
namespace Guard51\Repository;
use Guard51\Entity\BreakConfig;

/** @extends BaseRepository<BreakConfig> */
class BreakConfigRepository extends BaseRepository
{
    protected function getEntityClass(): string { return BreakConfig::class; }
    public function findByTenant(string $tenantId): array { return $this->findBy([], ['name' => 'ASC']); }
    public function findActiveByTenant(string $tenantId): array { return $this->findBy(['isActive' => true], ['name' => 'ASC']); }
}
