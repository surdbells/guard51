<?php

declare(strict_types=1);

namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'break_configs')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'idx_bc_tenant', columns: ['tenant_id'])]
class BreakConfig implements TenantAwareInterface
{
    use TenantAwareTrait;
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 100)]
    private string $name;

    #[ORM\Column(type: 'string', length: 10, enumType: BreakType::class)]
    private BreakType $breakType = BreakType::PAID;

    #[ORM\Column(type: 'integer')]
    private int $durationMinutes;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $autoStart = false;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $autoStartAfterMinutes = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $canEndEarly = true;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $isActive = true;

    public function __construct() { $this->id = Uuid::uuid4()->toString(); }

    public function getId(): string { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getBreakType(): BreakType { return $this->breakType; }
    public function getDurationMinutes(): int { return $this->durationMinutes; }
    public function isAutoStart(): bool { return $this->autoStart; }
    public function isActive(): bool { return $this->isActive; }

    public function setName(string $n): static { $this->name = $n; return $this; }
    public function setBreakType(BreakType $t): static { $this->breakType = $t; return $this; }
    public function setDurationMinutes(int $m): static { $this->durationMinutes = $m; return $this; }
    public function setAutoStart(bool $v): static { $this->autoStart = $v; return $this; }
    public function setAutoStartAfterMinutes(?int $m): static { $this->autoStartAfterMinutes = $m; return $this; }
    public function setCanEndEarly(bool $v): static { $this->canEndEarly = $v; return $this; }
    public function setIsActive(bool $v): static { $this->isActive = $v; return $this; }

    public function toArray(): array
    {
        return [
            'id' => $this->id, 'tenant_id' => $this->tenantId, 'name' => $this->name,
            'break_type' => $this->breakType->value, 'duration_minutes' => $this->durationMinutes,
            'auto_start' => $this->autoStart, 'auto_start_after_minutes' => $this->autoStartAfterMinutes,
            'can_end_early' => $this->canEndEarly, 'is_active' => $this->isActive,
            'created_at' => $this->createdAt?->format(\DateTimeInterface::ATOM),
        ];
    }
}
