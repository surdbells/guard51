<?php
declare(strict_types=1);
namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'permissions')]
#[ORM\Index(name: 'idx_perm_tenant', columns: ['tenant_id'])]
#[ORM\Index(name: 'idx_perm_user', columns: ['user_id'])]
#[ORM\UniqueConstraint(name: 'uq_perm_user_module', columns: ['user_id', 'module_key'])]
class Permission implements TenantAwareInterface
{
    use TenantAwareTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $userId;

    #[ORM\Column(type: 'string', length: 100)]
    private string $moduleKey;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $canView = false;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $canCreate = false;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $canEdit = false;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $canDelete = false;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $canExport = false;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $canApprove = false;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct() { $this->id = Uuid::uuid4()->toString(); $this->createdAt = new \DateTimeImmutable(); }

    public function getId(): string { return $this->id; }
    public function getUserId(): string { return $this->userId; }
    public function getModuleKey(): string { return $this->moduleKey; }

    public function setUserId(string $id): static { $this->userId = $id; return $this; }
    public function setModuleKey(string $k): static { $this->moduleKey = $k; return $this; }
    public function setCanView(bool $v): static { $this->canView = $v; return $this; }
    public function setCanCreate(bool $v): static { $this->canCreate = $v; return $this; }
    public function setCanEdit(bool $v): static { $this->canEdit = $v; return $this; }
    public function setCanDelete(bool $v): static { $this->canDelete = $v; return $this; }
    public function setCanExport(bool $v): static { $this->canExport = $v; return $this; }
    public function setCanApprove(bool $v): static { $this->canApprove = $v; return $this; }

    public function grantAll(): static { $this->canView = $this->canCreate = $this->canEdit = $this->canDelete = $this->canExport = $this->canApprove = true; return $this; }

    public function toArray(): array
    {
        return [
            'id' => $this->id, 'tenant_id' => $this->tenantId, 'user_id' => $this->userId,
            'module_key' => $this->moduleKey,
            'can_view' => $this->canView, 'can_create' => $this->canCreate,
            'can_edit' => $this->canEdit, 'can_delete' => $this->canDelete,
            'can_export' => $this->canExport, 'can_approve' => $this->canApprove,
        ];
    }
}
