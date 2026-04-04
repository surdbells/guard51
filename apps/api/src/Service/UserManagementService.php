<?php
declare(strict_types=1);
namespace Guard51\Service;

use Guard51\Entity\Permission;
use Guard51\Entity\UserRole;
use Guard51\Exception\ApiException;
use Guard51\Repository\PermissionRepository;
use Guard51\Repository\UserRepository;
use Psr\Log\LoggerInterface;

final class UserManagementService
{
    public function __construct(
        private readonly UserRepository $userRepo,
        private readonly PermissionRepository $permRepo,
        private readonly \Doctrine\ORM\EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
    ) {}

    public function listTenantUsers(string $tenantId): array
    {
        return $this->userRepo->findBy(['tenantId' => $tenantId]);
    }

    public function changeRole(string $userId, string $newRole): void
    {
        $user = $this->userRepo->findOrFail($userId);
        $role = UserRole::from($newRole);
        $user->setRole($role);
        $this->userRepo->save($user);
        $this->logger->info('Role changed', ['user' => $userId, 'role' => $newRole]);
    }

    public function getUserPermissions(string $userId): array
    {
        return $this->permRepo->findByUser($userId);
    }

    public function setModulePermission(string $tenantId, string $userId, string $moduleKey, array $perms): Permission
    {
        $existing = $this->permRepo->findByUserAndModule($userId, $moduleKey);
        if (!$existing) {
            $existing = new Permission();
            $existing->setTenantId($tenantId)->setUserId($userId)->setModuleKey($moduleKey);
        }
        if (isset($perms['can_view'])) $existing->setCanView((bool) $perms['can_view']);
        if (isset($perms['can_create'])) $existing->setCanCreate((bool) $perms['can_create']);
        if (isset($perms['can_edit'])) $existing->setCanEdit((bool) $perms['can_edit']);
        if (isset($perms['can_delete'])) $existing->setCanDelete((bool) $perms['can_delete']);
        if (isset($perms['can_export'])) $existing->setCanExport((bool) $perms['can_export']);
        if (isset($perms['can_approve'])) $existing->setCanApprove((bool) $perms['can_approve']);
        if (isset($perms['grant_all']) && $perms['grant_all']) $existing->grantAll();
        $this->permRepo->save($existing);
        return $existing;
    }

    public function revokeModulePermission(string $userId, string $moduleKey): void
    {
        $perm = $this->permRepo->findByUserAndModule($userId, $moduleKey);
        if ($perm) $this->permRepo->remove($perm);
    }

    public function getAvailableModules(): array
    {
        return [
            'sites', 'guards', 'clients', 'scheduling', 'attendance', 'tracking',
            'tours', 'panic', 'reports', 'incidents', 'dispatch', 'tasks',
            'invoices', 'payroll', 'chat', 'notifications', 'vehicle_patrol',
            'visitors', 'parking', 'analytics', 'licenses', 'security',
        ];
    }

    public function listRoles(string $tenantId): array
    {
        $conn = $this->em->getConnection();
        try {
            $rows = $conn->fetchAllAssociative("SELECT * FROM custom_roles WHERE tenant_id = ? ORDER BY name", [$tenantId]);
            return array_map(fn($r) => [...$r, 'permissions' => json_decode($r['permissions'] ?? '[]', true)], $rows);
        } catch (\Throwable $e) {
            // Auto-create table if it doesn't exist
            if (str_contains($e->getMessage(), 'custom_roles') || str_contains($e->getMessage(), 'relation') || str_contains($e->getMessage(), 'exist')) {
                try {
                    $conn->executeStatement("CREATE TABLE IF NOT EXISTS custom_roles (id VARCHAR(36) NOT NULL, tenant_id VARCHAR(36) NOT NULL, name VARCHAR(100) NOT NULL, description VARCHAR(255) DEFAULT NULL, color VARCHAR(10) DEFAULT '#3B82F6', permissions TEXT DEFAULT '[]', created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW(), PRIMARY KEY (id))");
                    $conn->executeStatement("CREATE INDEX IF NOT EXISTS idx_cr_tenant ON custom_roles (tenant_id)");
                    return [];
                } catch (\Throwable) { return []; }
            }
            return [];
        }
    }

    public function createRole(string $tenantId, array $data): array
    {
        $id = \Ramsey\Uuid\Uuid::uuid4()->toString();
        $conn = $this->em->getConnection();
        $perms = json_encode($data['permissions'] ?? []);
        $conn->executeStatement(
            "INSERT INTO custom_roles (id, tenant_id, name, description, color, permissions, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())",
            [$id, $tenantId, $data['name'] ?? '', $data['description'] ?? '', $data['color'] ?? '#3B82F6', $perms]
        );
        return ['id' => $id, 'name' => $data['name'] ?? '', 'description' => $data['description'] ?? '', 'color' => $data['color'] ?? '#3B82F6', 'permissions' => $data['permissions'] ?? []];
    }

    public function updateRole(string $id, array $data): array
    {
        $conn = $this->em->getConnection();
        $sets = []; $params = [];
        if (isset($data['name'])) { $sets[] = 'name = ?'; $params[] = $data['name']; }
        if (isset($data['description'])) { $sets[] = 'description = ?'; $params[] = $data['description']; }
        if (isset($data['color'])) { $sets[] = 'color = ?'; $params[] = $data['color']; }
        if (isset($data['permissions'])) { $sets[] = 'permissions = ?'; $params[] = json_encode($data['permissions']); }
        if ($sets) { $params[] = $id; $conn->executeStatement("UPDATE custom_roles SET " . implode(', ', $sets) . " WHERE id = ?", $params); }
        return $data;
    }

    public function updateRolePermissions(string $id, array $permissions): array
    {
        $conn = $this->em->getConnection();
        $conn->executeStatement("UPDATE custom_roles SET permissions = ? WHERE id = ?", [json_encode($permissions), $id]);
        return ['id' => $id, 'permissions' => $permissions];
    }

    public function saveRolePermissions(string $tenantId, string $roleId, array $permissions): void
    {
        $rps = new \Guard51\Service\RolePermissionService($this->em);
        $rps->savePermissions($tenantId, $roleId, $permissions);
    }

    public function getAllRoleOverrides(string $tenantId): array
    {
        $rps = new \Guard51\Service\RolePermissionService($this->em);
        return $rps->getAllOverrides($tenantId);
    }

    public function deleteRole(string $id): void
    {
        $this->em->getConnection()->executeStatement("DELETE FROM custom_roles WHERE id = ?", [$id]);
    }

    public function deactivateUser(string $userId): void
    {
        $user = $this->userRepo->find($userId);
        if ($user) {
            $user->setIsActive(false);
            $this->userRepo->save($user);
            $this->logger->info('User deactivated.', ['user_id' => $userId]);
        }
    }
}
