<?php
declare(strict_types=1);
namespace Guard51\Repository;
use Guard51\Entity\GuardLicense;

/** @extends BaseRepository<GuardLicense> */
class GuardLicenseRepository extends BaseRepository
{
    protected function getEntityClass(): string { return GuardLicense::class; }

    public function findByGuard(string $guardId): array
    {
        return $this->findBy(['guardId' => $guardId], ['expiryDate' => 'ASC']);
    }

    public function findByTenant(string $tenantId): array
    {
        return $this->findBy(['tenantId' => $tenantId], ['expiryDate' => 'ASC']);
    }

    public function findExpired(string $tenantId): array
    {
        $now = new \DateTimeImmutable();
        return $this->createQueryBuilder('l')
            ->where('l.tenantId = :tid')->setParameter('tid', $tenantId)
            ->andWhere('l.expiryDate < :now')->setParameter('now', $now)
            ->orderBy('l.expiryDate', 'ASC')
            ->getQuery()->getResult();
    }

    public function findExpiringSoon(string $tenantId, int $days = 30): array
    {
        $now = new \DateTimeImmutable();
        $cutoff = new \DateTimeImmutable("+{$days} days");
        return $this->createQueryBuilder('l')
            ->where('l.tenantId = :tid')->setParameter('tid', $tenantId)
            ->andWhere('l.expiryDate >= :now')->setParameter('now', $now)
            ->andWhere('l.expiryDate <= :cutoff')->setParameter('cutoff', $cutoff)
            ->orderBy('l.expiryDate', 'ASC')
            ->getQuery()->getResult();
    }
}
