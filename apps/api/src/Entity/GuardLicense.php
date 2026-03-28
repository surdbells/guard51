<?php
declare(strict_types=1);
namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'guard_licenses')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'idx_gl_tenant', columns: ['tenant_id'])]
#[ORM\Index(name: 'idx_gl_guard', columns: ['guard_id'])]
class GuardLicense implements TenantAwareInterface
{
    use TenantAwareTrait;
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $guardId;

    #[ORM\Column(type: 'string', length: 30, enumType: LicenseType::class)]
    private LicenseType $licenseType;

    #[ORM\Column(type: 'string', length: 200)]
    private string $name;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $licenseNumber = null;

    #[ORM\Column(type: 'string', length: 200, nullable: true)]
    private ?string $issuingAuthority = null;

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $issueDate;

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $expiryDate;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $documentUrl = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $isValid = true;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $expiryAlertSent = false;

    public function __construct() { $this->id = Uuid::uuid4()->toString(); }

    public function getId(): string { return $this->id; }
    public function getGuardId(): string { return $this->guardId; }
    public function getExpiryDate(): \DateTimeImmutable { return $this->expiryDate; }
    public function isExpired(): bool { return $this->expiryDate < new \DateTimeImmutable(); }
    public function isExpiringSoon(int $days = 30): bool { return !$this->isExpired() && $this->expiryDate < new \DateTimeImmutable("+{$days} days"); }

    public function setGuardId(string $id): static { $this->guardId = $id; return $this; }
    public function setLicenseType(LicenseType $t): static { $this->licenseType = $t; return $this; }
    public function setName(string $n): static { $this->name = $n; return $this; }
    public function setLicenseNumber(?string $n): static { $this->licenseNumber = $n; return $this; }
    public function setIssuingAuthority(?string $a): static { $this->issuingAuthority = $a; return $this; }
    public function setIssueDate(\DateTimeImmutable $d): static { $this->issueDate = $d; return $this; }
    public function setExpiryDate(\DateTimeImmutable $d): static { $this->expiryDate = $d; return $this; }
    public function setDocumentUrl(?string $u): static { $this->documentUrl = $u; return $this; }
    public function markInvalid(): static { $this->isValid = false; return $this; }
    public function markAlertSent(): static { $this->expiryAlertSent = true; return $this; }

    public function toArray(): array
    {
        return [
            'id' => $this->id, 'tenant_id' => $this->tenantId, 'guard_id' => $this->guardId,
            'license_type' => $this->licenseType->value, 'license_type_label' => $this->licenseType->label(),
            'name' => $this->name, 'license_number' => $this->licenseNumber,
            'issuing_authority' => $this->issuingAuthority,
            'issue_date' => $this->issueDate->format('Y-m-d'), 'expiry_date' => $this->expiryDate->format('Y-m-d'),
            'document_url' => $this->documentUrl, 'is_valid' => $this->isValid,
            'is_expired' => $this->isExpired(), 'is_expiring_soon' => $this->isExpiringSoon(),
            'days_until_expiry' => max(0, (int) (new \DateTimeImmutable())->diff($this->expiryDate)->format('%r%a')),
            'created_at' => $this->createdAt?->format(\DateTimeInterface::ATOM),
        ];
    }
}
