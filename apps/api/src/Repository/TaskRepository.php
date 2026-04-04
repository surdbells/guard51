<?php
declare(strict_types=1);
namespace Guard51\Repository;

use Guard51\Entity\Task;

/** @extends BaseRepository<Task> */
class TaskRepository extends BaseRepository
{
    protected function getEntityClass(): string { return Task::class; }

    public function findByTenant(string $tenantId, ?string $status = null): array
    {
        $qb = $this->createQueryBuilder('t')->where('t.tenantId = :tid')->setParameter('tid', $tenantId);
        if ($status) $qb->andWhere('t.status = :status')->setParameter('status', $status);
        return $qb->orderBy('t.dueDate', 'ASC')->getQuery()->getResult();
    }

    public function findByGuard(string $guardId): array
    {
        return $this->createQueryBuilder('t')->where('t.assignedTo = :gid')->setParameter('gid', $guardId)
            ->orderBy('t.dueDate', 'ASC')->getQuery()->getResult();
    }
}
