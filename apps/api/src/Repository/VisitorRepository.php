<?php
declare(strict_types=1);
namespace Guard51\Repository;
use Guard51\Entity\Visitor;

/** @extends BaseRepository<Visitor> */
class VisitorRepository extends BaseRepository
{
    protected function getEntityClass(): string { return Visitor::class; }

    public function findBySite(string $siteId, ?string $date = null, int $limit = 50): array
    {
        $qb = $this->createQueryBuilder('v')->where('v.siteId = :sid')->setParameter('sid', $siteId);
        if ($date) $qb->andWhere('DATE(v.checkInAt) = :d')->setParameter('d', $date);
        return $qb->orderBy('v.checkInAt', 'DESC')->setMaxResults($limit)->getQuery()->getResult();
    }

    public function searchByName(string $tenantId, string $name): array
    {
        return $this->createQueryBuilder('v')->where('LOWER(v.firstName) LIKE :n OR LOWER(v.lastName) LIKE :n')->setParameter('n', '%' . strtolower($name) . '%')
            ->setMaxResults(10)->getQuery()->getResult();
    }

    public function findCheckedIn(string $siteId): array
    {
        return $this->findBy(['siteId' => $siteId, 'status' => 'checked_in'], ['checkInAt' => 'DESC']);
    }
}
