<?php

declare(strict_types=1);

namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

/**
 * Defines a feature module in the Guard51 platform.
 * Platform-level: NOT tenant-scoped. Managed by super admin.
 * 52 modules seeded on deployment.
 */
#[ORM\Entity]
#[ORM\Table(name: 'feature_modules')]
#[ORM\HasLifecycleCallbacks]
#[ORM\UniqueConstraint(name: 'uq_fm_key', columns: ['module_key'])]
#[ORM\Index(name: 'idx_fm_category', columns: ['category'])]
#[ORM\Index(name: 'idx_fm_tier', columns: ['minimum_tier'])]
class FeatureModule
{
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 100, unique: true)]
    private string $moduleKey;

    #[ORM\Column(type: 'string', length: 200)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 50)]
    private string $category;

    #[ORM\Column(type: 'string', length: 30, enumType: SubscriptionTier::class)]
    private SubscriptionTier $minimumTier = SubscriptionTier::ALL;

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => false])]
    private bool $isCore = false;

    #[ORM\Column(type: 'json', nullable: false, options: ['default' => '[]'])]
    private array $dependencies = [];

    #[ORM\Column(type: 'json', nullable: false, options: ['default' => '["private_security","state_police","neighborhood_watch","lg_security","nscdc"]'])]
    private array $tenantTypes = ['private_security', 'state_police', 'neighborhood_watch', 'lg_security', 'nscdc'];

    #[ORM\Column(type: 'integer', nullable: false, options: ['default' => 0])]
    private int $sortOrder = 0;

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => true])]
    private bool $isActive = true;

    public function __construct()
    {
        $this->id = Uuid::uuid4()->toString();
    }

    // ── Getters ──────────────────────────────────────

    public function getId(): string { return $this->id; }
    public function getModuleKey(): string { return $this->moduleKey; }
    public function getName(): string { return $this->name; }
    public function getDescription(): ?string { return $this->description; }
    public function getCategory(): string { return $this->category; }
    public function getMinimumTier(): SubscriptionTier { return $this->minimumTier; }
    public function isCore(): bool { return $this->isCore; }
    public function getDependencies(): array { return $this->dependencies; }
    public function getTenantTypes(): array { return $this->tenantTypes; }
    public function getSortOrder(): int { return $this->sortOrder; }
    public function isActive(): bool { return $this->isActive; }

    // ── Setters ──────────────────────────────────────

    public function setModuleKey(string $moduleKey): static { $this->moduleKey = $moduleKey; return $this; }
    public function setName(string $name): static { $this->name = $name; return $this; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }
    public function setCategory(string $category): static { $this->category = $category; return $this; }
    public function setMinimumTier(SubscriptionTier $minimumTier): static { $this->minimumTier = $minimumTier; return $this; }
    public function setIsCore(bool $isCore): static { $this->isCore = $isCore; return $this; }
    public function setDependencies(array $dependencies): static { $this->dependencies = $dependencies; return $this; }
    public function setTenantTypes(array $tenantTypes): static { $this->tenantTypes = $tenantTypes; return $this; }
    public function setSortOrder(int $sortOrder): static { $this->sortOrder = $sortOrder; return $this; }
    public function setIsActive(bool $isActive): static { $this->isActive = $isActive; return $this; }

    // ── Business Logic ───────────────────────────────

    public function isAvailableForTenantType(TenantType $type): bool
    {
        return in_array($type->value, $this->tenantTypes, true);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'module_key' => $this->moduleKey,
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category,
            'minimum_tier' => $this->minimumTier->value,
            'is_core' => $this->isCore,
            'dependencies' => $this->dependencies,
            'tenant_types' => $this->tenantTypes,
            'sort_order' => $this->sortOrder,
            'is_active' => $this->isActive,
        ];
    }
}
