<?php
declare(strict_types=1);
namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'chat_messages')]
#[ORM\Index(name: 'idx_cm_conv', columns: ['conversation_id'])]
#[ORM\Index(name: 'idx_cm_sender', columns: ['sender_id'])]
class ChatMessage
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'conversation_id', type: 'string', length: 36)]
    private string $conversationId;

    #[ORM\Column(name: 'sender_id', type: 'string', length: 36)]
    private string $senderId;

    #[ORM\Column(type: 'text')]
    private string $content;

    #[ORM\Column(name: 'message_type', type: 'string', length: 10, enumType: MessageType::class)]
    private MessageType $messageType = MessageType::TEXT;

    #[ORM\Column(name: 'media_url', type: 'string', length: 500, nullable: true)]
    private ?string $mediaUrl = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 8, nullable: true)]
    private ?string $latitude = null;

    #[ORM\Column(type: 'decimal', precision: 11, scale: 8, nullable: true)]
    private ?string $longitude = null;

    #[ORM\Column(name: 'is_deleted', type: 'boolean', options: ['default' => false])]
    private bool $isDeleted = false;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct() { $this->id = Uuid::uuid4()->toString(); $this->createdAt = new \DateTimeImmutable(); }

    public function getId(): string { return $this->id; }
    public function getConversationId(): string { return $this->conversationId; }
    public function getSenderId(): string { return $this->senderId; }

    public function setConversationId(string $id): static { $this->conversationId = $id; return $this; }
    public function setSenderId(string $id): static { $this->senderId = $id; return $this; }
    public function setContent(string $c): static { $this->content = $c; return $this; }
    public function setMessageType(MessageType $t): static { $this->messageType = $t; return $this; }
    public function setMediaUrl(?string $u): static { $this->mediaUrl = $u; return $this; }
    public function setLatitude(?float $v): static { $this->latitude = $v !== null ? (string) $v : null; return $this; }
    public function setLongitude(?float $v): static { $this->longitude = $v !== null ? (string) $v : null; return $this; }
    public function softDelete(): static { $this->isDeleted = true; return $this; }

    public function toArray(): array
    {
        return [
            'id' => $this->id, 'conversation_id' => $this->conversationId, 'sender_id' => $this->senderId,
            'content' => $this->isDeleted ? '[Message deleted]' : $this->content,
            'message_type' => $this->messageType->value, 'message_type_label' => $this->messageType->label(),
            'media_url' => $this->isDeleted ? null : $this->mediaUrl,
            'lat' => $this->latitude ? (float) $this->latitude : null,
            'lng' => $this->longitude ? (float) $this->longitude : null,
            'is_deleted' => $this->isDeleted,
            'created_at' => $this->createdAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
