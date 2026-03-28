<?php
declare(strict_types=1);
namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'chat_conversations')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'idx_cc_tenant', columns: ['tenant_id'])]
class ChatConversation implements TenantAwareInterface
{
    use TenantAwareTrait;
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 20, enumType: ConversationType::class)]
    private ConversationType $type;

    #[ORM\Column(type: 'string', length: 200, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $siteId = null;

    #[ORM\Column(type: 'string', length: 36)]
    private string $createdBy;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastMessageAt = null;

    public function __construct() { $this->id = Uuid::uuid4()->toString(); }

    public function getId(): string { return $this->id; }
    public function getType(): ConversationType { return $this->type; }
    public function getName(): ?string { return $this->name; }
    public function getSiteId(): ?string { return $this->siteId; }

    public function setType(ConversationType $t): static { $this->type = $t; return $this; }
    public function setName(?string $n): static { $this->name = $n; return $this; }
    public function setSiteId(?string $id): static { $this->siteId = $id; return $this; }
    public function setCreatedBy(string $id): static { $this->createdBy = $id; return $this; }
    public function updateLastMessageAt(): static { $this->lastMessageAt = new \DateTimeImmutable(); return $this; }

    public function toArray(): array
    {
        return [
            'id' => $this->id, 'tenant_id' => $this->tenantId, 'type' => $this->type->value,
            'type_label' => $this->type->label(), 'name' => $this->name, 'site_id' => $this->siteId,
            'created_by' => $this->createdBy,
            'last_message_at' => $this->lastMessageAt?->format(\DateTimeInterface::ATOM),
            'created_at' => $this->createdAt?->format(\DateTimeInterface::ATOM),
        ];
    }
}
