<?php

declare(strict_types=1);

namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'tenants')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'idx_tenants_status', columns: ['status'])]
#[ORM\Index(name: 'idx_tenants_type', columns: ['tenant_type'])]
class Tenant
{
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 200)]
    private string $name;

    #[ORM\Column(name: 'tenant_type', type: 'string', length: 50, enumType: TenantType::class)]
    private TenantType $tenantType = TenantType::PRIVATE_SECURITY;

    #[ORM\Column(name: 'org_subtype', type: 'string', length: 100, nullable: true)]
    private ?string $orgSubtype = null;

    #[ORM\Column(name: 'rc_number', type: 'string', length: 100, nullable: true)]
    private ?string $rcNumber = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $address = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $state = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $country = null;

    #[ORM\Column(name: 'logo_url', type: 'string', length: 500, nullable: true)]
    private ?string $logoUrl = null;

    #[ORM\Column(type: 'json', nullable: false, options: ['default' => '{}'])]
    private array $branding = [];

    #[ORM\Column(name: 'custom_domain', type: 'string', length: 200, nullable: true)]
    private ?string $customDomain = null;

    #[ORM\Column(type: 'string', length: 50, enumType: TenantStatus::class)]
    private TenantStatus $status = TenantStatus::ACTIVE;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $timezone = 'Africa/Lagos';

    #[ORM\Column(type: 'string', length: 3, nullable: false, options: ['default' => 'NGN'])]
    private string $currency = 'NGN';

    #[ORM\Column(name: 'is_onboarded', type: 'boolean', nullable: false, options: ['default' => false])]
    private bool $isOnboarded = false;

    #[ORM\Column(name: 'onboarded_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $onboardedAt = null;

    #[ORM\Column(name: 'suspended_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $suspendedAt = null;

    #[ORM\Column(name: 'suspension_reason', type: 'string', length: 500, nullable: true)]
    private ?string $suspensionReason = null;

    public function __construct()
    {
        $this->id = Uuid::uuid4()->toString();
    }

    // ── Getters ──────────────────────────────────────

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTenantType(): TenantType
    {
        return $this->tenantType;
    }

    public function getOrgSubtype(): ?string
    {
        return $this->orgSubtype;
    }

    public function getRcNumber(): ?string
    {
        return $this->rcNumber;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function getLogoUrl(): ?string
    {
        return $this->logoUrl;
    }

    public function getBranding(): array
    {
        return $this->branding;
    }

    public function getCustomDomain(): ?string
    {
        return $this->customDomain;
    }

    public function getStatus(): TenantStatus
    {
        return $this->status;
    }

    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function isOnboarded(): bool
    {
        return $this->isOnboarded;
    }

    public function getOnboardedAt(): ?\DateTimeImmutable
    {
        return $this->onboardedAt;
    }

    public function getSuspendedAt(): ?\DateTimeImmutable
    {
        return $this->suspendedAt;
    }

    public function getSuspensionReason(): ?string
    {
        return $this->suspensionReason;
    }

    // ── Setters ──────────────────────────────────────

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function setTenantType(TenantType $tenantType): static
    {
        $this->tenantType = $tenantType;
        return $this;
    }

    public function setOrgSubtype(?string $orgSubtype): static
    {
        $this->orgSubtype = $orgSubtype;
        return $this;
    }

    public function setRcNumber(?string $rcNumber): static
    {
        $this->rcNumber = $rcNumber;
        return $this;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;
        return $this;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;
        return $this;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;
        return $this;
    }

    public function setState(?string $state): static
    {
        $this->state = $state;
        return $this;
    }

    public function setCountry(?string $country): static
    {
        $this->country = $country;
        return $this;
    }

    public function setLogoUrl(?string $logoUrl): static
    {
        $this->logoUrl = $logoUrl;
        return $this;
    }

    public function setBranding(array $branding): static
    {
        $this->branding = $branding;
        return $this;
    }

    public function setCustomDomain(?string $customDomain): static
    {
        $this->customDomain = $customDomain;
        return $this;
    }

    public function setStatus(TenantStatus $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function setTimezone(?string $timezone): static
    {
        $this->timezone = $timezone;
        return $this;
    }

    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;
        return $this;
    }

    // ── Business Logic ───────────────────────────────

    public function markOnboarded(): static
    {
        $this->isOnboarded = true;
        $this->onboardedAt = new \DateTimeImmutable();
        return $this;
    }

    public function suspend(string $reason): static
    {
        $this->status = TenantStatus::SUSPENDED;
        $this->suspendedAt = new \DateTimeImmutable();
        $this->suspensionReason = $reason;
        return $this;
    }

    public function reactivate(): static
    {
        $this->status = TenantStatus::ACTIVE;
        $this->suspendedAt = null;
        $this->suspensionReason = null;
        return $this;
    }

    public function isGovernment(): bool
    {
        return in_array($this->tenantType, [
            TenantType::STATE_POLICE,
            TenantType::NEIGHBORHOOD_WATCH,
            TenantType::LG_SECURITY,
            TenantType::NSCDC,
        ], true);
    }

    public function isPrivateSecurity(): bool
    {
        return $this->tenantType === TenantType::PRIVATE_SECURITY;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'tenant_type' => $this->tenantType->value,
            'org_subtype' => $this->orgSubtype,
            'rc_number' => $this->rcNumber,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'logo_url' => $this->logoUrl,
            'branding' => $this->branding,
            'custom_domain' => $this->customDomain,
            'status' => $this->status->value,
            'timezone' => $this->timezone,
            'currency' => $this->currency,
            'is_onboarded' => $this->isOnboarded,
            'onboarded_at' => $this->onboardedAt?->format(\DateTimeInterface::ATOM),
            'created_at' => $this->createdAt?->format(\DateTimeInterface::ATOM),
            'updated_at' => $this->updatedAt?->format(\DateTimeInterface::ATOM),
        ];
    }
}
