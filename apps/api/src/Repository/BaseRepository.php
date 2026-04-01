<?php

declare(strict_types=1);

namespace Guard51\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

/**
 * Base repository providing common CRUD and query methods.
 * Tenant scoping is handled automatically by the TenantFilter for TenantAware entities.
 *
 * @template T of object
 */
abstract class BaseRepository
{
    protected EntityRepository $repository;

    public function __construct(
        protected readonly EntityManagerInterface $em,
    ) {
        $this->repository = $em->getRepository($this->getEntityClass());
    }

    /**
     * Return the fully qualified entity class name.
     * @return class-string<T>
     */
    abstract protected function getEntityClass(): string;

    /**
     * Find entity by ID.
     * @return T|null
     */
    public function find(string $id): ?object
    {
        return $this->repository->find($id);
    }

    /**
     * Find entity by ID or throw.
     * @return T
     * @throws \Guard51\Exception\ApiException
     */
    public function findOrFail(string $id): object
    {
        $entity = $this->find($id);
        if ($entity === null) {
            throw \Guard51\Exception\ApiException::notFound(
                sprintf('%s not found.', $this->getEntityShortName())
            );
        }
        return $entity;
    }

    /**
     * Find all entities.
     * @return T[]
     */
    public function findAll(): array
    {
        return $this->repository->findAll();
    }

    /**
     * Find entities by criteria.
     * @return T[]
     */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
    {
        return $this->repository->findBy($criteria, $orderBy, $limit, $offset);
    }

    /**
     * Find one entity by criteria.
     * @return T|null
     */
    public function findOneBy(array $criteria): ?object
    {
        return $this->repository->findOneBy($criteria);
    }

    /**
     * Count entities matching criteria.
     */
    public function count(array $criteria = []): int
    {
        return $this->repository->count($criteria);
    }

    /**
     * Persist and flush an entity.
     * @param T $entity
     */
    public function save(object $entity): void
    {
        $this->em->persist($entity);
        $this->em->flush();
    }

    /**
     * Remove and flush an entity.
     * @param T $entity
     */
    public function remove(object $entity): void
    {
        $this->em->remove($entity);
        $this->em->flush();
    }

    /**
     * Create a query builder with the entity alias.
     */
    protected function createQueryBuilder(string $alias = 'e'): QueryBuilder
    {
        return $this->repository->createQueryBuilder($alias);
    }

    /**
     * Paginated query.
     * @return array{items: T[], total: int, page: int, per_page: int}
     */
    public function paginate(int $page = 1, int $perPage = 20, array $criteria = [], array $orderBy = ['createdAt' => 'DESC']): array
    {
        $qb = $this->createQueryBuilder('e');

        foreach ($criteria as $field => $value) {
            if ($value === null) {
                $qb->andWhere("e.{$field} IS NULL");
            } else {
                $qb->andWhere("e.{$field} = :{$field}")
                   ->setParameter($field, $value);
            }
        }

        foreach ($orderBy as $field => $direction) {
            $qb->addOrderBy("e.{$field}", $direction);
        }

        // Count total (remove ordering for count query — PostgreSQL requires it)
        $countQb = clone $qb;
        $countQb->resetDQLPart('orderBy');
        $total = (int) $countQb->select('COUNT(e.id)')->getQuery()->getSingleScalarResult();

        // Fetch page
        $items = $qb
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
        ];
    }

    /**
     * Get the short class name for error messages.
     */
    protected function getEntityShortName(): string
    {
        $parts = explode('\\', $this->getEntityClass());
        return end($parts);
    }
}
