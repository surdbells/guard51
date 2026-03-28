<?php
declare(strict_types=1);
namespace Guard51\Repository;
use Guard51\Entity\Notification;

/** @extends BaseRepository<Notification> */
class NotificationRepository extends BaseRepository
{
    protected function getEntityClass(): string { return Notification::class; }
    public function findByUser(string $userId, int $limit = 50): array
    {
        return $this->createQueryBuilder('n')->where('n.userId = :uid')->setParameter('uid', $userId)
            ->orderBy('n.createdAt', 'DESC')->setMaxResults($limit)->getQuery()->getResult();
    }
    public function countUnread(string $userId): int { return $this->count(['userId' => $userId, 'isRead' => false]); }
}
