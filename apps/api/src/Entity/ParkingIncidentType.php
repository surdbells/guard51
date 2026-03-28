<?php
declare(strict_types=1);
namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'parking_incident_types')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'idx_pit_tenant', columns: ['tenant_id'])]
class ParkingIncidentType implements TenantAwareInterface
{
    use TenantAwareTrait;
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 100)]
    private string $name;

    #[ORM\Column(type: 'json')]
    private array $formFields = [];

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $isActive = true;

    public function __construct() { $this->id = Uuid::uuid4()->toString(); }

    public function getId(): string { return $this->id; }
    public function setName(string $n): static { $this->name = $n; return $this; }
    public function setFormFields(array $f): static { $this->formFields = $f; return $this; }
    public function setIsActive(bool $v): static { $this->isActive = $v; return $this; }

    public function toArray(): array
    {
        return [
            'id' => $this->id, 'tenant_id' => $this->tenantId, 'name' => $this->name,
            'form_fields' => $this->formFields, 'is_active' => $this->isActive,
            'created_at' => $this->createdAt?->format(\DateTimeInterface::ATOM),
        ];
    }
}
