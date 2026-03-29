<?php
declare(strict_types=1);
namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'chat_participants')]
#[ORM\Index(name: 'idx_cp_conv', columns: ['conversation_id'])]
#[ORM\Index(name: 'idx_cp_user', columns: ['user_id'])]
class ChatParticipant
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'conversation_id', type: 'string', length: 36)]
    private string $conversationId;

    #[ORM\Column(name: 'user_id', type: 'string', length: 36)]
    private string $userId;

    #[ORM\Column(type: 'string', length: 10, enumType: ChatParticipantRole::class)]
    private ChatParticipantRole $role = ChatParticipantRole::MEMBER;

    #[ORM\Column(name: 'joined_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $joinedAt;

    #[ORM\Column(name: 'left_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $leftAt = null;

    #[ORM\Column(name: 'last_read_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastReadAt = null;

    public function __construct() { $this->id = Uuid::uuid4()->toString(); $this->joinedAt = new \DateTimeImmutable(); }

    public function getId(): string { return $this->id; }
    public function getConversationId(): string { return $this->conversationId; }
    public function getUserId(): string { return $this->userId; }

    public function setConversationId(string $id): static { $this->conversationId = $id; return $this; }
    public function setUserId(string $id): static { $this->userId = $id; return $this; }
    public function setRole(ChatParticipantRole $r): static { $this->role = $r; return $this; }
    public function markRead(): static { $this->lastReadAt = new \DateTimeImmutable(); return $this; }
    public function leave(): static { $this->leftAt = new \DateTimeImmutable(); return $this; }

    public function toArray(): array
    {
        return [
            'id' => $this->id, 'conversation_id' => $this->conversationId,
            'user_id' => $this->userId, 'role' => $this->role->value,
            'joined_at' => $this->joinedAt->format(\DateTimeInterface::ATOM),
            'left_at' => $this->leftAt?->format(\DateTimeInterface::ATOM),
            'last_read_at' => $this->lastReadAt?->format(\DateTimeInterface::ATOM),
        ];
    }
}
