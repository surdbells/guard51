<?php

declare(strict_types=1);

namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

/**
 * Links a tenant to the feature modules enabled for them.
 * Core modules are auto-enabled. Others are enabled based on subscription plan.
 */
#[ORM\Entity]
#[ORM\Table(name: 'tenant_feature_modules')]
#[ORM\HasLifecycleCallbacks]
#[ORM\UniqueConstraint(name: 'uq_tfm_tenant_module', columns: ['tenant_id', 'module_key'])]
#[ORM\Index(name: 'idx_tfm_tenant', columns: ['tenant_id'])]
class TenantFeatureModule implements TenantAwareInterface
{
    use TenantAwareTrait;
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'module_key', type: 'string', length: 100)]
    private string $moduleKey;

    #[ORM\Column(name: 'is_enabled', type: 'boolean', nullable: false, options: ['default' => true])]
    private bool $isEnabled = true;

    #[ORM\Column(name: 'enabled_by', type: 'string', length: 50, nullable: true)]
    private ?string $enabledBy = null;

    #[ORM\Column(name: 'enabled_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $enabledAt = null;

    #[ORM\Column(name: 'disabled_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $disabledAt = null;

    public function __construct()
    {
        $this->id = Uuid::uuid4()->toString();
    }

    public function getId(): string { return $this->id; }
    public function getModuleKey(): string { return $this->moduleKey; }
    public function isEnabled(): bool { return $this->isEnabled; }
    public function getEnabledBy(): ?string { return $this->enabledBy; }
    public function getEnabledAt(): ?\DateTimeImmutable { return $this->enabledAt; }

    public function setModuleKey(string $moduleKey): static { $this->moduleKey = $moduleKey; return $this; }

    public function enable(string $enabledBy = 'system'): static
    {
        $this->isEnabled = true;
        $this->enabledBy = $enabledBy;
        $this->enabledAt = new \DateTimeImmutable();
        $this->disabledAt = null;
        return $this;
    }

    public function disable(): static
    {
        $this->isEnabled = false;
        $this->disabledAt = new \DateTimeImmutable();
        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenantId,
            'module_key' => $this->moduleKey,
            'is_enabled' => $this->isEnabled,
            'enabled_by' => $this->enabledBy,
            'enabled_at' => $this->enabledAt?->format(\DateTimeInterface::ATOM),
        ];
    }
}
