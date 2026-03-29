<?php
declare(strict_types=1);
namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'dispatch_calls')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'idx_dc_tenant', columns: ['tenant_id'])]
#[ORM\Index(name: 'idx_dc_status', columns: ['status'])]
class DispatchCall implements TenantAwareInterface
{
    use TenantAwareTrait;
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'caller_name', type: 'string', length: 200)]
    private string $callerName;

    #[ORM\Column(name: 'caller_phone', type: 'string', length: 50, nullable: true)]
    private ?string $callerPhone = null;

    #[ORM\Column(name: 'client_id', type: 'string', length: 36, nullable: true)]
    private ?string $clientId = null;

    #[ORM\Column(name: 'site_id', type: 'string', length: 36, nullable: true)]
    private ?string $siteId = null;

    #[ORM\Column(name: 'call_type', type: 'string', length: 20, enumType: DispatchCallType::class)]
    private DispatchCallType $callType;

    #[ORM\Column(type: 'string', length: 10, enumType: Severity::class)]
    private Severity $priority;

    #[ORM\Column(type: 'text')]
    private string $description;

    #[ORM\Column(type: 'string', length: 20, enumType: DispatchStatus::class)]
    private DispatchStatus $status = DispatchStatus::RECEIVED;

    #[ORM\Column(name: 'received_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $receivedAt;

    #[ORM\Column(name: 'dispatched_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $dispatchedAt = null;

    #[ORM\Column(name: 'resolved_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $resolvedAt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $resolution = null;

    #[ORM\Column(name: 'created_by', type: 'string', length: 36)]
    private string $createdBy;

    public function __construct() { $this->id = Uuid::uuid4()->toString(); $this->receivedAt = new \DateTimeImmutable(); }

    public function getId(): string { return $this->id; }
    public function getStatus(): DispatchStatus { return $this->status; }
    public function getSiteId(): ?string { return $this->siteId; }
    public function getPriority(): Severity { return $this->priority; }

    public function setCallerName(string $n): static { $this->callerName = $n; return $this; }
    public function setCallerPhone(?string $p): static { $this->callerPhone = $p; return $this; }
    public function setClientId(?string $id): static { $this->clientId = $id; return $this; }
    public function setSiteId(?string $id): static { $this->siteId = $id; return $this; }
    public function setCallType(DispatchCallType $t): static { $this->callType = $t; return $this; }
    public function setPriority(Severity $p): static { $this->priority = $p; return $this; }
    public function setDescription(string $d): static { $this->description = $d; return $this; }
    public function setCreatedBy(string $id): static { $this->createdBy = $id; return $this; }

    public function dispatch(): static { $this->status = DispatchStatus::DISPATCHED; $this->dispatchedAt = new \DateTimeImmutable(); return $this; }
    public function markInProgress(): static { $this->status = DispatchStatus::IN_PROGRESS; return $this; }
    public function resolve(string $resolution): static { $this->status = DispatchStatus::RESOLVED; $this->resolvedAt = new \DateTimeImmutable(); $this->resolution = $resolution; return $this; }
    public function cancel(): static { $this->status = DispatchStatus::CANCELLED; return $this; }

    public function getResponseTimeMinutes(): ?int
    {
        if (!$this->dispatchedAt) return null;
        return (int) round(($this->dispatchedAt->getTimestamp() - $this->receivedAt->getTimestamp()) / 60);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id, 'tenant_id' => $this->tenantId,
            'caller_name' => $this->callerName, 'caller_phone' => $this->callerPhone,
            'client_id' => $this->clientId, 'site_id' => $this->siteId,
            'call_type' => $this->callType->value, 'call_type_label' => $this->callType->label(),
            'priority' => $this->priority->value, 'priority_label' => $this->priority->label(),
            'description' => $this->description, 'status' => $this->status->value,
            'status_label' => $this->status->label(), 'is_active' => $this->status->isActive(),
            'received_at' => $this->receivedAt->format(\DateTimeInterface::ATOM),
            'dispatched_at' => $this->dispatchedAt?->format(\DateTimeInterface::ATOM),
            'resolved_at' => $this->resolvedAt?->format(\DateTimeInterface::ATOM),
            'resolution' => $this->resolution, 'response_time_minutes' => $this->getResponseTimeMinutes(),
            'created_by' => $this->createdBy,
            'created_at' => $this->createdAt?->format(\DateTimeInterface::ATOM),
        ];
    }
}
