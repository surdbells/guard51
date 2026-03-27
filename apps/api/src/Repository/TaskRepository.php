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
        if ($status) $qb->andWhere('t.status = :s')->setParameter('s', $status);
        return $qb->orderBy('t.createdAt', 'DESC')->getQuery()->getResult();
    }

    public function findByGuard(string $guardId): array { return $this->findBy(['assignedTo' => $guardId], ['createdAt' => 'DESC']); }
    public function findBySite(string $siteId): array { return $this->findBy(['siteId' => $siteId], ['createdAt' => 'DESC']); }

    public function findOverdue(string $tenantId): array
    {
        return $this->createQueryBuilder('t')->where('t.tenantId = :tid')
            ->andWhere('t.dueDate < :now')->andWhere('t.status IN (:active)')
            ->setParameter('tid', $tenantId)->setParameter('now', new \DateTimeImmutable())
            ->setParameter('active', ['pending', 'in_progress'])
            ->orderBy('t.dueDate', 'ASC')->getQuery()->getResult();
    }
}
