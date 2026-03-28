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

    public function sendMessage(string $conversationId, string $senderId, array $data, bool $enforceCheckIn = false): ChatMessage
    {
        if (empty($data['content'])) throw ApiException::validation('content required.');

        // Guard focus enforcement: guards can only chat when clocked in
        if ($enforceCheckIn) {
            $this->enforceGuardCheckedIn($senderId);
        }

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

    /**
     * Guards can only chat when checked in to a site (focus enforcement).
     * Checks the time_clocks table for an active (no clock_out) record.
     */
    private function enforceGuardCheckedIn(string $userId): void
    {
        $conn = $this->convRepo->getEntityManager()->getConnection();
        $sql = "SELECT COUNT(*) FROM time_clocks WHERE guard_id = ? AND clock_out_time IS NULL";
        $count = (int) $conn->fetchOne($sql, [$userId]);
        if ($count === 0) {
            throw ApiException::validation('Guards can only send messages while clocked in to a site.');
        }
    }

    /**
     * Get unread message count for a user in a specific conversation.
     */
    public function getUnreadCount(string $conversationId, string $userId): int
    {
        $parts = $this->partRepo->findByConversation($conversationId);
        $lastRead = null;
        foreach ($parts as $p) {
            if ($p->getUserId() === $userId) {
                $lastRead = $p->toArray()['last_read_at'];
                break;
            }
        }
        if (!$lastRead) {
            // Never read — count all messages not from this user
            $msgs = $this->msgRepo->findByConversation($conversationId, 999);
            return count(array_filter($msgs, fn($m) => $m->getSenderId() !== $userId));
        }
        $conn = $this->convRepo->getEntityManager()->getConnection();
        $sql = "SELECT COUNT(*) FROM chat_messages WHERE conversation_id = ? AND sender_id != ? AND created_at > ?";
        return (int) $conn->fetchOne($sql, [$conversationId, $userId, $lastRead]);
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

    /**
     * Get conversations with unread counts for the user.
     */
    public function getUserConversationsWithUnread(string $userId): array
    {
        $convs = $this->getUserConversations($userId);
        $result = [];
        foreach ($convs as $conv) {
            $arr = $conv->toArray();
            $arr['unread_count'] = $this->getUnreadCount($conv->getId(), $userId);
            $result[] = $arr;
        }
        return $result;
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
