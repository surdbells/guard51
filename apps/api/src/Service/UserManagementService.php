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
}
