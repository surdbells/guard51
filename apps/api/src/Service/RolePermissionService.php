<?php
declare(strict_types=1);
namespace Guard51\Service;

use Doctrine\ORM\EntityManagerInterface;

/**
 * Manages role permissions for both system roles and custom roles.
 * System role overrides are stored in role_permission_overrides table.
 * Custom role permissions are stored in custom_roles.permissions column.
 */
final class RolePermissionService
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    /**
     * Get permissions for a role (system or custom) within a tenant.
     */
    public function getPermissions(string $tenantId, string $roleId): array
    {
        $conn = $this->em->getConnection();
        $this->ensureTable($conn);

        // Check for tenant-specific override first
        try {
            $row = $conn->fetchAssociative(
                "SELECT permissions FROM role_permission_overrides WHERE tenant_id = ? AND role_id = ?",
                [$tenantId, $roleId]
            );
            if ($row) {
                return json_decode($row['permissions'] ?? '[]', true);
            }
        } catch (\Throwable) {}

        // Check custom_roles table
        try {
            $row = $conn->fetchAssociative(
                "SELECT permissions FROM custom_roles WHERE id = ? AND tenant_id = ?",
                [$roleId, $tenantId]
            );
            if ($row) {
                return json_decode($row['permissions'] ?? '[]', true);
            }
        } catch (\Throwable) {}

        return []; // No override found — frontend will use defaults
    }

    /**
     * Save permissions for a role (system or custom) within a tenant.
     */
    public function savePermissions(string $tenantId, string $roleId, array $permissions): void
    {
        $conn = $this->em->getConnection();
        $this->ensureTable($conn);

        $permsJson = json_encode($permissions);

        // Try update first
        try {
            $affected = $conn->executeStatement(
                "UPDATE role_permission_overrides SET permissions = ?, updated_at = NOW() WHERE tenant_id = ? AND role_id = ?",
                [$permsJson, $tenantId, $roleId]
            );
            if ($affected > 0) return;
        } catch (\Throwable) {}

        // Insert new override
        try {
            $conn->executeStatement(
                "INSERT INTO role_permission_overrides (id, tenant_id, role_id, permissions, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())",
                [\Ramsey\Uuid\Uuid::uuid4()->toString(), $tenantId, $roleId, $permsJson]
            );
        } catch (\Throwable) {}

        // Also update custom_roles if it's a custom role
        try {
            $conn->executeStatement(
                "UPDATE custom_roles SET permissions = ? WHERE id = ? AND tenant_id = ?",
                [$permsJson, $roleId, $tenantId]
            );
        } catch (\Throwable) {}
    }

    /**
     * Get all role permission overrides for a tenant.
     */
    public function getAllOverrides(string $tenantId): array
    {
        $conn = $this->em->getConnection();
        $this->ensureTable($conn);

        $result = [];
        try {
            $rows = $conn->fetchAllAssociative(
                "SELECT role_id, permissions FROM role_permission_overrides WHERE tenant_id = ?",
                [$tenantId]
            );
            foreach ($rows as $row) {
                $result[$row['role_id']] = json_decode($row['permissions'] ?? '[]', true);
            }
        } catch (\Throwable) {}

        return $result;
    }

    private function ensureTable(\Doctrine\DBAL\Connection $conn): void
    {
        static $checked = false;
        if ($checked) return;
        try {
            $conn->executeStatement("CREATE TABLE IF NOT EXISTS role_permission_overrides (
                id VARCHAR(36) NOT NULL,
                tenant_id VARCHAR(36) NOT NULL,
                role_id VARCHAR(50) NOT NULL,
                permissions TEXT DEFAULT '[]',
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
                PRIMARY KEY (id),
                UNIQUE (tenant_id, role_id)
            )");
        } catch (\Throwable) {}
        $checked = true;
    }
}
