<?php
declare(strict_types=1);
namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'notifications')]
#[ORM\Index(name: 'idx_notif_user', columns: ['user_id'])]
#[ORM\Index(name: 'idx_notif_tenant', columns: ['tenant_id'])]
class Notification implements TenantAwareInterface
{
    use TenantAwareTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'user_id', type: 'string', length: 36)]
    private string $userId;

    #[ORM\Column(type: 'string', length: 20, enumType: NotificationType::class)]
    private NotificationType $type;

    #[ORM\Column(type: 'string', length: 300)]
    private string $title;

    #[ORM\Column(type: 'text')]
    private string $body;

    #[ORM\Column(type: 'json')]
    private array $data = [];

    #[ORM\Column(type: 'string', length: 10, enumType: NotificationChannel::class)]
    private NotificationChannel $channel = NotificationChannel::IN_APP;

    #[ORM\Column(name: 'is_read', type: 'boolean', options: ['default' => false])]
    private bool $isRead = false;

    #[ORM\Column(name: 'read_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $readAt = null;

    #[ORM\Column(name: 'sent_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $sentAt = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct() { $this->id = Uuid::uuid4()->toString(); $this->createdAt = new \DateTimeImmutable(); }

    public function getId(): string { return $this->id; }
    public function getUserId(): string { return $this->userId; }
    public function getType(): NotificationType { return $this->type; }
    public function isRead(): bool { return $this->isRead; }

    public function setUserId(string $id): static { $this->userId = $id; return $this; }
    public function setType(NotificationType $t): static { $this->type = $t; return $this; }
    public function setTitle(string $t): static { $this->title = $t; return $this; }
    public function setBody(string $b): static { $this->body = $b; return $this; }
    public function setData(array $d): static { $this->data = $d; return $this; }
    public function setChannel(NotificationChannel $c): static { $this->channel = $c; return $this; }
    public function markRead(): static { $this->isRead = true; $this->readAt = new \DateTimeImmutable(); return $this; }
    public function markSent(): static { $this->sentAt = new \DateTimeImmutable(); return $this; }

    public function toArray(): array
    {
        return [
            'id' => $this->id, 'tenant_id' => $this->tenantId, 'user_id' => $this->userId,
            'type' => $this->type->value, 'type_label' => $this->type->label(),
            'title' => $this->title, 'body' => $this->body, 'data' => $this->data,
            'channel' => $this->channel->value, 'channel_label' => $this->channel->label(),
            'is_read' => $this->isRead, 'read_at' => $this->readAt?->format(\DateTimeInterface::ATOM),
            'sent_at' => $this->sentAt?->format(\DateTimeInterface::ATOM),
            'created_at' => $this->createdAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
