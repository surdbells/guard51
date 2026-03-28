<?php
declare(strict_types=1);
namespace Guard51\Repository;
use Guard51\Entity\ChatParticipant;

/** @extends BaseRepository<ChatParticipant> */
class ChatParticipantRepository extends BaseRepository
{
    protected function getEntityClass(): string { return ChatParticipant::class; }
    public function findByConversation(string $convId): array { return $this->findBy(['conversationId' => $convId, 'leftAt' => null]); }
    public function findByUser(string $userId): array
    {
        return $this->createQueryBuilder('p')->where('p.userId = :uid')->andWhere('p.leftAt IS NULL')
            ->setParameter('uid', $userId)->getQuery()->getResult();
    }
}
