<?php
declare(strict_types=1);
namespace Guard51\Repository;
use Guard51\Entity\ShiftTemplate;

/** @extends BaseRepository<ShiftTemplate> */
class ShiftTemplateRepository extends BaseRepository
{
    protected function getEntityClass(): string { return ShiftTemplate::class; }
    public function findByTenant(string $tenantId): array { return $this->findBy(['tenantId' => $tenantId], ['name' => 'ASC']); }
    public function findActiveByTenant(string $tenantId): array { return $this->findBy(['tenantId' => $tenantId, 'isActive' => true], ['name' => 'ASC']); }
}
