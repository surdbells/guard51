<?php
declare(strict_types=1);
namespace Guard51\Repository;
use Guard51\Entity\ShiftSwapRequest;
use Guard51\Entity\SwapRequestStatus;

/** @extends BaseRepository<ShiftSwapRequest> */
class ShiftSwapRequestRepository extends BaseRepository
{
    protected function getEntityClass(): string { return ShiftSwapRequest::class; }
    public function findPendingByTenant(string $tenantId): array { return $this->findBy(['tenantId' => $tenantId, 'status' => SwapRequestStatus::PENDING], ['createdAt' => 'DESC']); }
    public function findByGuard(string $guardId): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.requestingGuardId = :gid OR r.targetGuardId = :gid')
            ->setParameter('gid', $guardId)->orderBy('r.createdAt', 'DESC')->getQuery()->getResult();
    }
}
