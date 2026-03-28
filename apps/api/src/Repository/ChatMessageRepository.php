<?php
declare(strict_types=1);
namespace Guard51\Repository;
use Guard51\Entity\ChatMessage;

/** @extends BaseRepository<ChatMessage> */
class ChatMessageRepository extends BaseRepository
{
    protected function getEntityClass(): string { return ChatMessage::class; }
    public function findByConversation(string $convId, int $limit = 50, int $offset = 0): array
    {
        return $this->createQueryBuilder('m')->where('m.conversationId = :cid')->setParameter('cid', $convId)
            ->orderBy('m.createdAt', 'DESC')->setMaxResults($limit)->setFirstResult($offset)->getQuery()->getResult();
    }
}
