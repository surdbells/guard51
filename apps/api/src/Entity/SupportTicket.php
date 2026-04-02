<?php
declare(strict_types=1);
namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'support_tickets')]
#[ORM\Index(name: 'idx_st_tenant', columns: ['tenant_id'])]
class SupportTicket implements TenantAwareInterface
{
    use TenantAwareTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'user_id', type: 'string', length: 36)]
    private string $userId;

    #[ORM\Column(type: 'string', length: 200)]
    private string $subject;

    #[ORM\Column(type: 'text')]
    private string $description;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'open'; // open, in_progress, resolved, closed

    #[ORM\Column(type: 'string', length: 20)]
    private string $priority = 'medium'; // low, medium, high, critical

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $category = null;

    #[ORM\Column(name: 'assigned_to', type: 'string', length: 36, nullable: true)]
    private ?string $assignedTo = null;

    #[ORM\Column(name: 'resolved_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $resolvedAt = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->id = Uuid::uuid4()->toString();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): string { return $this->id; }
    public function getUserId(): string { return $this->userId; }
    public function setUserId(string $v): static { $this->userId = $v; return $this; }
    public function getSubject(): string { return $this->subject; }
    public function setSubject(string $v): static { $this->subject = $v; return $this; }
    public function getDescription(): string { return $this->description; }
    public function setDescription(string $v): static { $this->description = $v; return $this; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $v): static { $this->status = $v; $this->updatedAt = new \DateTimeImmutable(); return $this; }
    public function getPriority(): string { return $this->priority; }
    public function setPriority(string $v): static { $this->priority = $v; return $this; }
    public function getCategory(): ?string { return $this->category; }
    public function setCategory(?string $v): static { $this->category = $v; return $this; }
    public function getAssignedTo(): ?string { return $this->assignedTo; }
    public function setAssignedTo(?string $v): static { $this->assignedTo = $v; return $this; }
    public function getResolvedAt(): ?\DateTimeImmutable { return $this->resolvedAt; }

    public function resolve(): static { $this->status = 'resolved'; $this->resolvedAt = new \DateTimeImmutable(); $this->updatedAt = new \DateTimeImmutable(); return $this; }
    public function close(): static { $this->status = 'closed'; $this->updatedAt = new \DateTimeImmutable(); return $this; }

    public function toArray(): array
    {
        return [
            'id' => $this->id, 'tenant_id' => $this->tenantId, 'user_id' => $this->userId,
            'subject' => $this->subject, 'description' => $this->description,
            'status' => $this->status, 'priority' => $this->priority, 'category' => $this->category,
            'assigned_to' => $this->assignedTo,
            'resolved_at' => $this->resolvedAt?->format(\DateTimeInterface::ATOM),
            'created_at' => $this->createdAt->format(\DateTimeInterface::ATOM),
            'updated_at' => $this->updatedAt?->format(\DateTimeInterface::ATOM),
        ];
    }
}
