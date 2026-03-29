<?php
declare(strict_types=1);
namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'properties')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'idx_prop_tenant', columns: ['tenant_id'])]
class Property implements TenantAwareInterface
{
    use TenantAwareTrait;
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 200)]
    private string $name;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $state = null;

    #[ORM\Column(name: 'manager_id', type: 'string', length: 36, nullable: true)]
    private ?string $managerId = null;

    #[ORM\Column(name: 'is_active', type: 'boolean', options: ['default' => true])]
    private bool $isActive = true;

    public function __construct() { $this->id = Uuid::uuid4()->toString(); }

    public function getId(): string { return $this->id; }
    public function setName(string $n): static { $this->name = $n; return $this; }
    public function setAddress(?string $a): static { $this->address = $a; return $this; }
    public function setCity(?string $c): static { $this->city = $c; return $this; }
    public function setState(?string $s): static { $this->state = $s; return $this; }
    public function setManagerId(?string $id): static { $this->managerId = $id; return $this; }

    public function toArray(): array
    {
        return [
            'id' => $this->id, 'tenant_id' => $this->tenantId, 'name' => $this->name,
            'address' => $this->address, 'city' => $this->city, 'state' => $this->state,
            'manager_id' => $this->managerId, 'is_active' => $this->isActive,
            'created_at' => $this->createdAt?->format(\DateTimeInterface::ATOM),
        ];
    }
}
