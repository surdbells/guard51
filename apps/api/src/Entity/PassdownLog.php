<?php

declare(strict_types=1);

namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'passdown_logs')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'idx_pl_tenant', columns: ['tenant_id'])]
#[ORM\Index(name: 'idx_pl_site', columns: ['site_id'])]
class PassdownLog implements TenantAwareInterface
{
    use TenantAwareTrait;
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'site_id', type: 'string', length: 36)]
    private string $siteId;

    #[ORM\Column(name: 'guard_id', type: 'string', length: 36)]
    private string $guardId; // outgoing guard

    #[ORM\Column(name: 'shift_id', type: 'string', length: 36, nullable: true)]
    private ?string $shiftId = null;

    #[ORM\Column(name: 'incoming_guard_id', type: 'string', length: 36, nullable: true)]
    private ?string $incomingGuardId = null;

    #[ORM\Column(type: 'text')]
    private string $content;

    #[ORM\Column(type: 'string', length: 20, enumType: PassdownPriority::class)]
    private PassdownPriority $priority = PassdownPriority::NORMAL;

    #[ORM\Column(type: 'json')]
    private array $attachments = [];

    #[ORM\Column(name: 'acknowledged_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $acknowledgedAt = null;

    #[ORM\Column(name: 'acknowledged_by', type: 'string', length: 36, nullable: true)]
    private ?string $acknowledgedBy = null;

    public function __construct() { $this->id = Uuid::uuid4()->toString(); }

    public function getId(): string { return $this->id; }
    public function getSiteId(): string { return $this->siteId; }
    public function getGuardId(): string { return $this->guardId; }
    public function getShiftId(): ?string { return $this->shiftId; }
    public function getIncomingGuardId(): ?string { return $this->incomingGuardId; }
    public function getContent(): string { return $this->content; }
    public function getPriority(): PassdownPriority { return $this->priority; }
    public function getAttachments(): array { return $this->attachments; }
    public function isAcknowledged(): bool { return $this->acknowledgedAt !== null; }

    public function setSiteId(string $id): static { $this->siteId = $id; return $this; }
    public function setGuardId(string $id): static { $this->guardId = $id; return $this; }
    public function setShiftId(?string $id): static { $this->shiftId = $id; return $this; }
    public function setIncomingGuardId(?string $id): static { $this->incomingGuardId = $id; return $this; }
    public function setContent(string $c): static { $this->content = $c; return $this; }
    public function setPriority(PassdownPriority $p): static { $this->priority = $p; return $this; }
    public function setAttachments(array $a): static { $this->attachments = $a; return $this; }

    public function acknowledge(string $guardId): static
    {
        $this->acknowledgedAt = new \DateTimeImmutable();
        $this->acknowledgedBy = $guardId;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id, 'tenant_id' => $this->tenantId, 'site_id' => $this->siteId,
            'guard_id' => $this->guardId, 'shift_id' => $this->shiftId,
            'incoming_guard_id' => $this->incomingGuardId, 'content' => $this->content,
            'priority' => $this->priority->value, 'priority_label' => $this->priority->label(),
            'attachments' => $this->attachments, 'attachment_count' => count($this->attachments),
            'is_acknowledged' => $this->isAcknowledged(),
            'acknowledged_at' => $this->acknowledgedAt?->format(\DateTimeInterface::ATOM),
            'acknowledged_by' => $this->acknowledgedBy,
            'created_at' => $this->createdAt?->format(\DateTimeInterface::ATOM),
        ];
    }
}
