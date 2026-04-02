<?php
declare(strict_types=1);
namespace Guard51\Repository;
use Guard51\Entity\HelpArticle;

/** @extends BaseRepository<HelpArticle> */
class HelpArticleRepository extends BaseRepository
{
    protected function getEntityClass(): string { return HelpArticle::class; }

    public function findPublished(): array { return $this->findBy(['isPublished' => true], ['sortOrder' => 'ASC']); }

    public function findByCategory(string $cat): array { return $this->findBy(['category' => $cat, 'isPublished' => true], ['sortOrder' => 'ASC']); }

    public function search(string $q): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.isPublished = true')
            ->andWhere('LOWER(a.title) LIKE :q OR LOWER(a.content) LIKE :q')
            ->setParameter('q', '%' . strtolower($q) . '%')
            ->orderBy('a.sortOrder', 'ASC')->getQuery()->getResult();
    }
}
