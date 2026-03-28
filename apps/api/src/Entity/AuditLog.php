<?php
declare(strict_types=1);
namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'audit_logs')]
#[ORM\Index(name: 'idx_al_tenant', columns: ['tenant_id'])]
#[ORM\Index(name: 'idx_al_user', columns: ['user_id'])]
#[ORM\Index(name: 'idx_al_action', columns: ['action'])]
#[ORM\Index(name: 'idx_al_created', columns: ['created_at'])]
class AuditLog implements TenantAwareInterface
{
    use TenantAwareTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $userId = null;

    #[ORM\Column(type: 'string', length: 30, enumType: AuditAction::class)]
    private AuditAction $action;

    #[ORM\Column(type: 'string', length: 100)]
    private string $resourceType;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $resourceId = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'json')]
    private array $metadata = [];

    #[ORM\Column(type: 'string', length: 45, nullable: true)]
    private ?string $ipAddress = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $userAgent = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct() { $this->id = Uuid::uuid4()->toString(); $this->createdAt = new \DateTimeImmutable(); }

    public function getId(): string { return $this->id; }
    public function setUserId(?string $id): static { $this->userId = $id; return $this; }
    public function setAction(AuditAction $a): static { $this->action = $a; return $this; }
    public function setResourceType(string $t): static { $this->resourceType = $t; return $this; }
    public function setResourceId(?string $id): static { $this->resourceId = $id; return $this; }
    public function setDescription(?string $d): static { $this->description = $d; return $this; }
    public function setMetadata(array $m): static { $this->metadata = $m; return $this; }
    public function setIpAddress(?string $ip): static { $this->ipAddress = $ip; return $this; }
    public function setUserAgent(?string $ua): static { $this->userAgent = $ua; return $this; }

    public function toArray(): array
    {
        return [
            'id' => $this->id, 'tenant_id' => $this->tenantId, 'user_id' => $this->userId,
            'action' => $this->action->value, 'action_label' => $this->action->label(),
            'resource_type' => $this->resourceType, 'resource_id' => $this->resourceId,
            'description' => $this->description, 'metadata' => $this->metadata,
            'ip_address' => $this->ipAddress, 'user_agent' => $this->userAgent,
            'created_at' => $this->createdAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
