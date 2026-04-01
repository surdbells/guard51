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
        return $this->createQueryBuilder('l')->where('l.tenantId = :tid')->setParameter('tid', $tenantId)
            ->getQuery()->getResult();
    }
}
