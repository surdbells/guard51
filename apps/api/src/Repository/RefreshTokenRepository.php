<?php

declare(strict_types=1);

namespace Guard51\Repository;

use Guard51\Entity\RefreshToken;

/**
 * @extends BaseRepository<RefreshToken>
 */
class RefreshTokenRepository extends BaseRepository
{
    protected function getEntityClass(): string
    {
        return RefreshToken::class;
    }

    public function findValidByUserId(string $userId): array
    {
        $qb = $this->createQueryBuilder('rt')
            ->where('rt.userId = :userId')
            ->andWhere('rt.isRevoked = :revoked')
            ->andWhere('rt.expiresAt > :now')
            ->setParameter('userId', $userId)
            ->setParameter('revoked', false)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('rt.createdAt', 'DESC');

        return $qb->getQuery()->getResult();
    }

    public function revokeAllForUser(string $userId): int
    {
        $qb = $this->em->createQueryBuilder()
            ->update(RefreshToken::class, 'rt')
            ->set('rt.isRevoked', ':revoked')
            ->set('rt.revokedAt', ':now')
            ->where('rt.userId = :userId')
            ->andWhere('rt.isRevoked = :notRevoked')
            ->setParameter('revoked', true)
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('userId', $userId)
            ->setParameter('notRevoked', false);

        return $qb->getQuery()->execute();
    }

    public function deleteExpired(): int
    {
        $qb = $this->em->createQueryBuilder()
            ->delete(RefreshToken::class, 'rt')
            ->where('rt.expiresAt < :now')
            ->setParameter('now', new \DateTimeImmutable());

        return $qb->getQuery()->execute();
    }
}
