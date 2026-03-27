<?php
declare(strict_types=1);
namespace Guard51\Repository;

use Guard51\Entity\DispatchAssignment;

/** @extends BaseRepository<DispatchAssignment> */
class DispatchAssignmentRepository extends BaseRepository
{
    protected function getEntityClass(): string { return DispatchAssignment::class; }
    public function findByDispatch(string $dispatchId): array { return $this->findBy(['dispatchId' => $dispatchId], ['assignedAt' => 'ASC']); }
    public function findByGuard(string $guardId, int $limit = 20): array
    {
        return $this->createQueryBuilder('da')->where('da.guardId = :gid')->setParameter('gid', $guardId)
            ->orderBy('da.assignedAt', 'DESC')->setMaxResults($limit)->getQuery()->getResult();
    }
}
