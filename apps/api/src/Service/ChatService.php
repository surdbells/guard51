<?php
declare(strict_types=1);
namespace Guard51\Service;

use Guard51\Entity\ChatConversation;
use Guard51\Entity\ChatMessage;
use Guard51\Entity\ChatParticipant;
use Guard51\Entity\ChatParticipantRole;
use Guard51\Entity\ConversationType;
use Guard51\Entity\MessageType;
use Guard51\Exception\ApiException;
use Guard51\Repository\ChatConversationRepository;
use Guard51\Repository\ChatMessageRepository;
use Guard51\Repository\ChatParticipantRepository;
use Psr\Log\LoggerInterface;

final class ChatService
{
    public function __construct(
        private readonly ChatConversationRepository $convRepo,
        private readonly ChatParticipantRepository $partRepo,
        private readonly ChatMessageRepository $msgRepo,
        private readonly LoggerInterface $logger,
    ) {}

    public function createConversation(string $tenantId, array $data, string $createdBy): ChatConversation
    {
        if (empty($data['type'])) throw ApiException::validation('type required.');
        $conv = new ChatConversation();
        $conv->setTenantId($tenantId)->setType(ConversationType::from($data['type']))->setCreatedBy($createdBy);
        if (isset($data['name'])) $conv->setName($data['name']);
        if (isset($data['site_id'])) $conv->setSiteId($data['site_id']);
        $this->convRepo->save($conv);

        // Add creator as admin
        $p = new ChatParticipant();
        $p->setConversationId($conv->getId())->setUserId($createdBy)->setRole(ChatParticipantRole::ADMIN);
        $this->partRepo->save($p);

        // Add other participants
        foreach ($data['participants'] ?? [] as $uid) {
            $pp = new ChatParticipant();
            $pp->setConversationId($conv->getId())->setUserId($uid);
            $this->partRepo->save($pp);
        }
        return $conv;
    }

    public function getOrCreateSiteChannel(string $tenantId, string $siteId, string $siteName): ChatConversation
    {
        $existing = $this->convRepo->findSiteChannel($tenantId, $siteId);
        if ($existing) return $existing;
        return $this->createConversation($tenantId, [
            'type' => 'site_channel', 'name' => "#{$siteName}", 'site_id' => $siteId,
        ], 'system');
    }

    public function addParticipant(string $conversationId, string $userId, ChatParticipantRole $role = ChatParticipantRole::MEMBER): ChatParticipant
    {
        $p = new ChatParticipant();
        $p->setConversationId($conversationId)->setUserId($userId)->setRole($role);
        $this->partRepo->save($p);
        return $p;
    }

    public function sendMessage(string $conversationId, string $senderId, array $data): ChatMessage
    {
        if (empty($data['content'])) throw ApiException::validation('content required.');
        $msg = new ChatMessage();
        $msg->setConversationId($conversationId)->setSenderId($senderId)->setContent($data['content']);
        if (isset($data['message_type'])) $msg->setMessageType(MessageType::from($data['message_type']));
        if (isset($data['media_url'])) $msg->setMediaUrl($data['media_url']);
        if (isset($data['lat'])) $msg->setLatitude((float) $data['lat']);
        if (isset($data['lng'])) $msg->setLongitude((float) $data['lng']);
        $this->msgRepo->save($msg);

        // Update conversation last_message_at
        $conv = $this->convRepo->findOrFail($conversationId);
        $conv->updateLastMessageAt();
        $this->convRepo->save($conv);
        return $msg;
    }

    public function getMessages(string $conversationId, int $limit = 50, int $offset = 0): array
    {
        return $this->msgRepo->findByConversation($conversationId, $limit, $offset);
    }

    public function getUserConversations(string $userId): array
    {
        $participations = $this->partRepo->findByUser($userId);
        $convIds = array_map(fn($p) => $p->getConversationId(), $participations);
        $convs = [];
        foreach ($convIds as $cid) {
            $conv = $this->convRepo->find($cid);
            if ($conv) $convs[] = $conv;
        }
        usort($convs, fn($a, $b) => ($b->toArray()['last_message_at'] ?? '') <=> ($a->toArray()['last_message_at'] ?? ''));
        return $convs;
    }

    public function markRead(string $conversationId, string $userId): void
    {
        $parts = $this->partRepo->findByConversation($conversationId);
        foreach ($parts as $p) {
            if ($p->getUserId() === $userId) { $p->markRead(); $this->partRepo->save($p); break; }
        }
    }

    public function listTenantConversations(string $tenantId): array { return $this->convRepo->findByTenant($tenantId); }
}
