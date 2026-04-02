<?php
declare(strict_types=1);
namespace Guard51\Repository;
use Guard51\Entity\Permission;

/** @extends BaseRepository<Permission> */
class PermissionRepository extends BaseRepository
{
    protected function getEntityClass(): string { return Permission::class; }
    public function findByUser(string $userId): array { return $this->findBy(['userId' => $userId]); }
    public function findByUserAndModule(string $userId, string $moduleKey): ?Permission { return $this->findOneBy(['userId' => $userId, 'moduleKey' => $moduleKey]); }
    public function findByTenant(string $tenantId): array { return $this->findBy(['tenantId' => $tenantId]); }
}
