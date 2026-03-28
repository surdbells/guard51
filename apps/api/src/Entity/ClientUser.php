<?php
declare(strict_types=1);
namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'client_users')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'idx_cu_tenant', columns: ['tenant_id'])]
#[ORM\Index(name: 'idx_cu_client', columns: ['client_id'])]
#[ORM\UniqueConstraint(name: 'uq_cu_user', columns: ['user_id'])]
class ClientUser implements TenantAwareInterface
{
    use TenantAwareTrait;
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $clientId;

    #[ORM\Column(type: 'string', length: 36)]
    private string $userId;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $canViewReports = true;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $canViewTracking = true;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $canViewInvoices = true;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $canViewIncidents = true;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $canMessage = true;

    public function __construct() { $this->id = Uuid::uuid4()->toString(); }

    public function getId(): string { return $this->id; }
    public function getClientId(): string { return $this->clientId; }
    public function getUserId(): string { return $this->userId; }

    public function setClientId(string $id): static { $this->clientId = $id; return $this; }
    public function setUserId(string $id): static { $this->userId = $id; return $this; }
    public function setCanViewReports(bool $v): static { $this->canViewReports = $v; return $this; }
    public function setCanViewTracking(bool $v): static { $this->canViewTracking = $v; return $this; }
    public function setCanViewInvoices(bool $v): static { $this->canViewInvoices = $v; return $this; }
    public function setCanViewIncidents(bool $v): static { $this->canViewIncidents = $v; return $this; }
    public function setCanMessage(bool $v): static { $this->canMessage = $v; return $this; }

    public function toArray(): array
    {
        return [
            'id' => $this->id, 'tenant_id' => $this->tenantId, 'client_id' => $this->clientId,
            'user_id' => $this->userId,
            'can_view_reports' => $this->canViewReports, 'can_view_tracking' => $this->canViewTracking,
            'can_view_invoices' => $this->canViewInvoices, 'can_view_incidents' => $this->canViewIncidents,
            'can_message' => $this->canMessage,
            'created_at' => $this->createdAt?->format(\DateTimeInterface::ATOM),
        ];
    }
}
