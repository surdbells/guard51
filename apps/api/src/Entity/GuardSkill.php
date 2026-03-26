<?php

declare(strict_types=1);

namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'guard_skills')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'idx_gs_tenant', columns: ['tenant_id'])]
#[ORM\UniqueConstraint(name: 'uq_gs_name', columns: ['tenant_id', 'name'])]
class GuardSkill implements TenantAwareInterface
{
    use TenantAwareTrait;
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 100)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    public function __construct() { $this->id = Uuid::uuid4()->toString(); }

    public function getId(): string { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getDescription(): ?string { return $this->description; }
    public function setName(string $name): static { $this->name = $name; return $this; }
    public function setDescription(?string $desc): static { $this->description = $desc; return $this; }

    public function toArray(): array
    {
        return ['id' => $this->id, 'tenant_id' => $this->tenantId, 'name' => $this->name, 'description' => $this->description, 'created_at' => $this->createdAt?->format(\DateTimeInterface::ATOM)];
    }
}
