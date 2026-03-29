<?php
declare(strict_types=1);
namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'vehicle_patrol_routes')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'idx_vpr_tenant', columns: ['tenant_id'])]
class VehiclePatrolRoute implements TenantAwareInterface
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

    #[ORM\Column(type: 'json')]
    private array $sites = [];

    #[ORM\Column(name: 'expected_hits_per_day', type: 'integer', options: ['default' => 1])]
    private int $expectedHitsPerDay = 1;

    #[ORM\Column(name: 'reset_time', type: 'string', length: 5, options: ['default' => '00:00'])]
    private string $resetTime = '00:00';

    #[ORM\Column(name: 'is_active', type: 'boolean', options: ['default' => true])]
    private bool $isActive = true;

    public function __construct() { $this->id = Uuid::uuid4()->toString(); }

    public function getId(): string { return $this->id; }
    public function getName(): string { return $this->name; }
    public function setName(string $n): static { $this->name = $n; return $this; }
    public function setDescription(?string $d): static { $this->description = $d; return $this; }
    public function setSites(array $s): static { $this->sites = $s; return $this; }
    public function setExpectedHitsPerDay(int $h): static { $this->expectedHitsPerDay = $h; return $this; }
    public function setResetTime(string $t): static { $this->resetTime = $t; return $this; }
    public function setIsActive(bool $v): static { $this->isActive = $v; return $this; }

    public function toArray(): array
    {
        return [
            'id' => $this->id, 'tenant_id' => $this->tenantId, 'name' => $this->name,
            'description' => $this->description, 'sites' => $this->sites, 'site_count' => count($this->sites),
            'expected_hits_per_day' => $this->expectedHitsPerDay, 'reset_time' => $this->resetTime,
            'is_active' => $this->isActive, 'created_at' => $this->createdAt?->format(\DateTimeInterface::ATOM),
        ];
    }
}
