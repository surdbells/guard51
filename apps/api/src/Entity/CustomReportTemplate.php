<?php
declare(strict_types=1);
namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'custom_report_templates')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'idx_crt_tenant', columns: ['tenant_id'])]
class CustomReportTemplate implements TenantAwareInterface
{
    use TenantAwareTrait;
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 200)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    /** @var array Dynamic field definitions [{name, type, required, options}] */
    #[ORM\Column(type: 'json')]
    private array $fields = [];

    #[ORM\Column(name: 'is_active', type: 'boolean', options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(name: 'created_by', type: 'string', length: 36)]
    private string $createdBy;

    public function __construct() { $this->id = Uuid::uuid4()->toString(); }

    public function getId(): string { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getFields(): array { return $this->fields; }
    public function isActive(): bool { return $this->isActive; }

    public function setName(string $n): static { $this->name = $n; return $this; }
    public function setDescription(?string $d): static { $this->description = $d; return $this; }
    public function setFields(array $f): static { $this->fields = $f; return $this; }
    public function setIsActive(bool $v): static { $this->isActive = $v; return $this; }
    public function setCreatedBy(string $id): static { $this->createdBy = $id; return $this; }

    public function toArray(): array
    {
        return [
            'id' => $this->id, 'tenant_id' => $this->tenantId, 'name' => $this->name,
            'description' => $this->description, 'fields' => $this->fields,
            'field_count' => count($this->fields), 'is_active' => $this->isActive,
            'created_by' => $this->createdBy,
            'created_at' => $this->createdAt?->format(\DateTimeInterface::ATOM),
        ];
    }
}
