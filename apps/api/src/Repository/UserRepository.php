<?php

declare(strict_types=1);

namespace Guard51\Repository;

use Guard51\Entity\User;
use Guard51\Entity\UserRole;

/**
 * @extends BaseRepository<User>
 */
class UserRepository extends BaseRepository
{
    protected function getEntityClass(): string
    {
        return User::class;
    }

    public function findByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => strtolower(trim($email))]);
    }

    /**
     * @return User[]
     */
    public function findByTenant(string $tenantId): array
    {
        return $this->findBy(['tenantId' => $tenantId, 'tenantId' => $tenantId], ['firstName' => 'ASC']);
    }

    /**
     * @return User[]
     */
    public function findByRole(UserRole $role): array
    {
        return $this->findBy(['role' => $role], ['firstName' => 'ASC']);
    }

    /**
     * @return User[]
     */
    public function findActiveByTenantAndRole(string $tenantId, UserRole $role): array
    {
        $qb = $this->createQueryBuilder('u')
            ->where('u.tenantId = :tenantId')
            ->andWhere('u.role = :role')
            ->andWhere('u.isActive = :active')
            ->setParameter('tenantId', $tenantId)
            ->setParameter('role', $role)
            ->setParameter('active', true)
            ->orderBy('u.firstName', 'ASC');

        return $qb->getQuery()->getResult();
    }

    public function countByTenant(string $tenantId): int
    {
        return $this->count(['tenantId' => $tenantId, 'tenantId' => $tenantId]);
    }

    public function emailExists(string $email): bool
    {
        return $this->findByEmail($email) !== null;
    }

    /**
     * Find the super admin users (platform-level, no tenant).
     * @return User[]
     */
    public function findSuperAdmins(): array
    {
        return $this->findBy(['role' => UserRole::SUPER_ADMIN], ['firstName' => 'ASC']);
    }
}
