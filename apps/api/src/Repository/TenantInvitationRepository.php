<?php

declare(strict_types=1);

namespace Guard51\Repository;

use Guard51\Entity\InvitationStatus;
use Guard51\Entity\TenantInvitation;

/**
 * @extends BaseRepository<TenantInvitation>
 */
class TenantInvitationRepository extends BaseRepository
{
    protected function getEntityClass(): string
    {
        return TenantInvitation::class;
    }

    public function findPendingByEmail(string $tenantId, string $email): ?TenantInvitation
    {
        $qb = $this->createQueryBuilder('i')
            ->where('i.tenantId = :tenantId')
            ->andWhere('i.email = :email')
            ->andWhere('i.status = :status')
            ->andWhere('i.expiresAt > :now')
            ->setParameter('tenantId', $tenantId)
            ->setParameter('email', strtolower(trim($email)))
            ->setParameter('status', InvitationStatus::PENDING)
            ->setParameter('now', new \DateTimeImmutable())
            ->setMaxResults(1);
        return $qb->getQuery()->getOneOrNullResult();
    }

    /** @return TenantInvitation[] */
    public function findByEmail(string $email): array
    {
        return $this->findBy(['email' => strtolower(trim($email))], ['createdAt' => 'DESC']);
    }

    /** @return TenantInvitation[] */
    public function findPendingByTenant(string $tenantId): array
    {
        $qb = $this->createQueryBuilder('i')
            ->where('i.tenantId = :tenantId')
            ->andWhere('i.status = :status')
            ->andWhere('i.expiresAt > :now')
            ->setParameter('tenantId', $tenantId)
            ->setParameter('status', InvitationStatus::PENDING)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('i.createdAt', 'DESC');
        return $qb->getQuery()->getResult();
    }

    public function countPendingByTenant(string $tenantId): int
    {
        $qb = $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->where('i.tenantId = :tenantId')
            ->andWhere('i.status = :status')
            ->andWhere('i.expiresAt > :now')
            ->setParameter('tenantId', $tenantId)
            ->setParameter('status', InvitationStatus::PENDING)
            ->setParameter('now', new \DateTimeImmutable());
        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
