<?php
declare(strict_types=1);
namespace Guard51\Repository;
use Guard51\Entity\ChatConversation;

/** @extends BaseRepository<ChatConversation> */
class ChatConversationRepository extends BaseRepository
{
    protected function getEntityClass(): string { return ChatConversation::class; }
    public function findByTenant(string $tenantId): array { return $this->findBy(['tenantId' => $tenantId], ['lastMessageAt' => 'DESC']); }
    public function findSiteChannel(string $tenantId, string $siteId): ?ChatConversation { return $this->findOneBy(['siteId' => $siteId, 'type' => 'site_channel']); }
}
