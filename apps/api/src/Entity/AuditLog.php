<?php

declare(strict_types=1);

namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

/**
 * Immutable audit log. Records every significant action in the system.
 * Append-only: no updates, no deletes.
 */
#[ORM\Entity]
#[ORM\Table(name: 'audit_logs')]
#[ORM\Index(name: 'idx_audit_tenant', columns: ['tenant_id'])]
#[ORM\Index(name: 'idx_audit_user', columns: ['user_id'])]
#[ORM\Index(name: 'idx_audit_entity', columns: ['entity_type', 'entity_id'])]
#[ORM\Index(name: 'idx_audit_action', columns: ['action'])]
#[ORM\Index(name: 'idx_audit_created', columns: ['created_at'])]
class AuditLog
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $tenantId = null;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $userId = null;

    #[ORM\Column(type: 'string', length: 200, nullable: true)]
    private ?string $userName = null;

    #[ORM\Column(type: 'string', length: 50)]
    private string $action;

    #[ORM\Column(type: 'string', length: 100)]
    private string $entityType;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $entityId = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $oldValues = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $newValues = null;

    #[ORM\Column(type: 'string', length: 45, nullable: true)]
    private ?string $ipAddress = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $userAgent = null;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $requestId = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->id = Uuid::uuid4()->toString();
        $this->createdAt = new \DateTimeImmutable();
    }

    // ── Getters ──────────────────────────────────────

    public function getId(): string { return $this->id; }
    public function getTenantId(): ?string { return $this->tenantId; }
    public function getUserId(): ?string { return $this->userId; }
    public function getUserName(): ?string { return $this->userName; }
    public function getAction(): string { return $this->action; }
    public function getEntityType(): string { return $this->entityType; }
    public function getEntityId(): ?string { return $this->entityId; }
    public function getDescription(): ?string { return $this->description; }
    public function getOldValues(): ?array { return $this->oldValues; }
    public function getNewValues(): ?array { return $this->newValues; }
    public function getIpAddress(): ?string { return $this->ipAddress; }
    public function getUserAgent(): ?string { return $this->userAgent; }
    public function getRequestId(): ?string { return $this->requestId; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    // ── Setters ──────────────────────────────────────

    public function setTenantId(?string $tenantId): static { $this->tenantId = $tenantId; return $this; }
    public function setUserId(?string $userId): static { $this->userId = $userId; return $this; }
    public function setUserName(?string $userName): static { $this->userName = $userName; return $this; }
    public function setAction(string $action): static { $this->action = $action; return $this; }
    public function setEntityType(string $entityType): static { $this->entityType = $entityType; return $this; }
    public function setEntityId(?string $entityId): static { $this->entityId = $entityId; return $this; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }
    public function setOldValues(?array $oldValues): static { $this->oldValues = $oldValues; return $this; }
    public function setNewValues(?array $newValues): static { $this->newValues = $newValues; return $this; }
    public function setIpAddress(?string $ipAddress): static { $this->ipAddress = $ipAddress; return $this; }
    public function setUserAgent(?string $userAgent): static { $this->userAgent = $userAgent; return $this; }
    public function setRequestId(?string $requestId): static { $this->requestId = $requestId; return $this; }

    // ── Factory Methods ──────────────────────────────

    public static function create(
        string $action,
        string $entityType,
        ?string $entityId = null,
        ?string $tenantId = null,
        ?string $userId = null,
        ?string $userName = null,
        ?string $description = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?string $requestId = null,
    ): self {
        $log = new self();
        $log->setAction($action);
        $log->setEntityType($entityType);
        $log->setEntityId($entityId);
        $log->setTenantId($tenantId);
        $log->setUserId($userId);
        $log->setUserName($userName);
        $log->setDescription($description);
        $log->setOldValues($oldValues);
        $log->setNewValues($newValues);
        $log->setIpAddress($ipAddress);
        $log->setUserAgent($userAgent);
        $log->setRequestId($requestId);
        return $log;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenantId,
            'user_id' => $this->userId,
            'user_name' => $this->userName,
            'action' => $this->action,
            'entity_type' => $this->entityType,
            'entity_id' => $this->entityId,
            'description' => $this->description,
            'old_values' => $this->oldValues,
            'new_values' => $this->newValues,
            'ip_address' => $this->ipAddress,
            'request_id' => $this->requestId,
            'created_at' => $this->createdAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
