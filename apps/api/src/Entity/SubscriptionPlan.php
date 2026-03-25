<?php

declare(strict_types=1);

namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

/**
 * Defines a subscription plan. Can be default (seeded) or custom (created by super admin).
 * Platform-level: NOT tenant-scoped.
 */
#[ORM\Entity]
#[ORM\Table(name: 'subscription_plans')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'idx_sp_tier', columns: ['tier'])]
#[ORM\Index(name: 'idx_sp_active', columns: ['is_active'])]
class SubscriptionPlan
{
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 200)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 30, enumType: SubscriptionTier::class)]
    private SubscriptionTier $tier;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private string $monthlyPrice;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2, nullable: true)]
    private ?string $annualPrice = null;

    #[ORM\Column(type: 'string', length: 3, options: ['default' => 'NGN'])]
    private string $currency = 'NGN';

    #[ORM\Column(type: 'integer', options: ['default' => 25])]
    private int $maxGuards = 25;

    #[ORM\Column(type: 'integer', options: ['default' => 5])]
    private int $maxSites = 5;

    #[ORM\Column(type: 'integer', options: ['default' => 5])]
    private int $maxClients = 5;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $maxStaff = null;

    /** @var string[] List of module_keys included in this plan */
    #[ORM\Column(type: 'json', nullable: false, options: ['default' => '[]'])]
    private array $includedModules = [];

    /** @var string[] Tenant types this plan is available for */
    #[ORM\Column(type: 'json', nullable: false, options: ['default' => '["private_security"]'])]
    private array $tenantTypes = ['private_security'];

    #[ORM\Column(type: 'json', nullable: false, options: ['default' => '{}'])]
    private array $featureFlags = [];

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => false])]
    private bool $isCustom = false;

    /** If set, plan is only visible to this specific tenant (private/enterprise deal) */
    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $privateTenantId = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $paystackPlanCode = null;

    #[ORM\Column(type: 'integer', options: ['default' => 14])]
    private int $trialDays = 14;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $sortOrder = 0;

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => true])]
    private bool $isActive = true;

    public function __construct()
    {
        $this->id = Uuid::uuid4()->toString();
    }

    // ── Getters ──────────────────────────────────────

    public function getId(): string { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getDescription(): ?string { return $this->description; }
    public function getTier(): SubscriptionTier { return $this->tier; }
    public function getMonthlyPrice(): string { return $this->monthlyPrice; }
    public function getAnnualPrice(): ?string { return $this->annualPrice; }
    public function getCurrency(): string { return $this->currency; }
    public function getMaxGuards(): int { return $this->maxGuards; }
    public function getMaxSites(): int { return $this->maxSites; }
    public function getMaxClients(): int { return $this->maxClients; }
    public function getMaxStaff(): ?int { return $this->maxStaff; }
    public function getIncludedModules(): array { return $this->includedModules; }
    public function getTenantTypes(): array { return $this->tenantTypes; }
    public function getFeatureFlags(): array { return $this->featureFlags; }
    public function isCustom(): bool { return $this->isCustom; }
    public function getPrivateTenantId(): ?string { return $this->privateTenantId; }
    public function getPaystackPlanCode(): ?string { return $this->paystackPlanCode; }
    public function getTrialDays(): int { return $this->trialDays; }
    public function getSortOrder(): int { return $this->sortOrder; }
    public function isActive(): bool { return $this->isActive; }

    // ── Setters ──────────────────────────────────────

    public function setName(string $name): static { $this->name = $name; return $this; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }
    public function setTier(SubscriptionTier $tier): static { $this->tier = $tier; return $this; }
    public function setMonthlyPrice(string $monthlyPrice): static { $this->monthlyPrice = $monthlyPrice; return $this; }
    public function setAnnualPrice(?string $annualPrice): static { $this->annualPrice = $annualPrice; return $this; }
    public function setCurrency(string $currency): static { $this->currency = $currency; return $this; }
    public function setMaxGuards(int $maxGuards): static { $this->maxGuards = $maxGuards; return $this; }
    public function setMaxSites(int $maxSites): static { $this->maxSites = $maxSites; return $this; }
    public function setMaxClients(int $maxClients): static { $this->maxClients = $maxClients; return $this; }
    public function setMaxStaff(?int $maxStaff): static { $this->maxStaff = $maxStaff; return $this; }
    public function setIncludedModules(array $includedModules): static { $this->includedModules = $includedModules; return $this; }
    public function setTenantTypes(array $tenantTypes): static { $this->tenantTypes = $tenantTypes; return $this; }
    public function setFeatureFlags(array $featureFlags): static { $this->featureFlags = $featureFlags; return $this; }
    public function setIsCustom(bool $isCustom): static { $this->isCustom = $isCustom; return $this; }
    public function setPrivateTenantId(?string $privateTenantId): static { $this->privateTenantId = $privateTenantId; return $this; }
    public function setPaystackPlanCode(?string $paystackPlanCode): static { $this->paystackPlanCode = $paystackPlanCode; return $this; }
    public function setTrialDays(int $trialDays): static { $this->trialDays = $trialDays; return $this; }
    public function setSortOrder(int $sortOrder): static { $this->sortOrder = $sortOrder; return $this; }
    public function setIsActive(bool $isActive): static { $this->isActive = $isActive; return $this; }

    // ── Business Logic ───────────────────────────────

    public function includesModule(string $moduleKey): bool
    {
        return in_array($moduleKey, $this->includedModules, true);
    }

    public function isUnlimitedGuards(): bool
    {
        return $this->maxGuards >= 999999;
    }

    public function isAvailableForTenantType(TenantType $type): bool
    {
        return in_array($type->value, $this->tenantTypes, true);
    }

    public function isPrivatePlan(): bool
    {
        return $this->privateTenantId !== null;
    }

    public function getMonthlyPriceKobo(): int
    {
        return (int) round((float) $this->monthlyPrice * 100);
    }

    public function getAnnualPriceKobo(): ?int
    {
        if ($this->annualPrice === null) return null;
        return (int) round((float) $this->annualPrice * 100);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'tier' => $this->tier->value,
            'monthly_price' => $this->monthlyPrice,
            'annual_price' => $this->annualPrice,
            'currency' => $this->currency,
            'max_guards' => $this->maxGuards,
            'max_sites' => $this->maxSites,
            'max_clients' => $this->maxClients,
            'max_staff' => $this->maxStaff,
            'included_modules' => $this->includedModules,
            'tenant_types' => $this->tenantTypes,
            'feature_flags' => $this->featureFlags,
            'is_custom' => $this->isCustom,
            'trial_days' => $this->trialDays,
            'sort_order' => $this->sortOrder,
            'is_active' => $this->isActive,
            'created_at' => $this->createdAt?->format(\DateTimeInterface::ATOM),
        ];
    }
}
