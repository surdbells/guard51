<?php
declare(strict_types=1);
namespace Guard51\Repository;
use Guard51\Entity\GuardLicense;

/** @extends BaseRepository<GuardLicense> */
class GuardLicenseRepository extends BaseRepository
{
    protected function getEntityClass(): string { return GuardLicense::class; }
    public function findByGuard(string $guardId): array { return $this->findBy(['guardId' => $guardId], ['expiryDate' => 'ASC']); }
    public function findExpiringSoon(string $tenantId, int $days = 30): array
    {
        $cutoff = new \DateTimeImmutable("+{$days} days");
        return $this->createQueryBuilder('l')->where('l.tenantId = :tid')->andWhere('l.expiryDate <= :c')
            ->andWhere('l.expiryDate > :now')->andWhere('l.isValid = true')->setParameter('c', $cutoff)->setParameter('now', new \DateTimeImmutable())
            ->orderBy('l.expiryDate', 'ASC')->getQuery()->getResult();
    }
    public function findExpired(string $tenantId): array
    {
        return $this->createQueryBuilder('l')->where('l.expiryDate < :now')->andWhere('l.isValid = true')->setParameter('now', new \DateTimeImmutable())
            ->getQuery()->getResult();
    }
}
