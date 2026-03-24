<?php

declare(strict_types=1);

namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'tenant_bank_accounts')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'idx_tba_tenant', columns: ['tenant_id'])]
class TenantBankAccount implements TenantAwareInterface
{
    use TenantAwareTrait;
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 100)]
    private string $bankName;

    #[ORM\Column(type: 'string', length: 20)]
    private string $accountNumber;

    #[ORM\Column(type: 'string', length: 200)]
    private string $accountName;

    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    private ?string $bankCode = null;

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => true])]
    private bool $isPrimary = true;

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => true])]
    private bool $isActive = true;

    public function __construct()
    {
        $this->id = Uuid::uuid4()->toString();
    }

    // ── Getters ──────────────────────────────────────

    public function getId(): string { return $this->id; }
    public function getBankName(): string { return $this->bankName; }
    public function getAccountNumber(): string { return $this->accountNumber; }
    public function getAccountName(): string { return $this->accountName; }
    public function getBankCode(): ?string { return $this->bankCode; }
    public function isPrimary(): bool { return $this->isPrimary; }
    public function isActive(): bool { return $this->isActive; }

    // ── Setters ──────────────────────────────────────

    public function setBankName(string $bankName): static { $this->bankName = $bankName; return $this; }
    public function setAccountNumber(string $accountNumber): static { $this->accountNumber = $accountNumber; return $this; }
    public function setAccountName(string $accountName): static { $this->accountName = $accountName; return $this; }
    public function setBankCode(?string $bankCode): static { $this->bankCode = $bankCode; return $this; }
    public function setIsPrimary(bool $isPrimary): static { $this->isPrimary = $isPrimary; return $this; }
    public function setIsActive(bool $isActive): static { $this->isActive = $isActive; return $this; }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenantId,
            'bank_name' => $this->bankName,
            'account_number' => $this->accountNumber,
            'account_name' => $this->accountName,
            'bank_code' => $this->bankCode,
            'is_primary' => $this->isPrimary,
            'is_active' => $this->isActive,
            'created_at' => $this->createdAt?->format(\DateTimeInterface::ATOM),
        ];
    }
}
