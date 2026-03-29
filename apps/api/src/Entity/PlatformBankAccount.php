<?php

declare(strict_types=1);

namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

/**
 * Guard51 platform's own bank accounts.
 * Displayed to tenants who choose manual bank transfer for subscription payments.
 * NOT tenant-scoped — this is platform-level data managed by super admin.
 */
#[ORM\Entity]
#[ORM\Table(name: 'platform_bank_accounts')]
#[ORM\HasLifecycleCallbacks]
class PlatformBankAccount
{
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'bank_name', type: 'string', length: 100)]
    private string $bankName;

    #[ORM\Column(name: 'account_number', type: 'string', length: 20)]
    private string $accountNumber;

    #[ORM\Column(name: 'account_name', type: 'string', length: 200)]
    private string $accountName;

    #[ORM\Column(name: 'bank_code', type: 'string', length: 10, nullable: true)]
    private ?string $bankCode = null;

    #[ORM\Column(name: 'is_primary', type: 'boolean', nullable: false, options: ['default' => true])]
    private bool $isPrimary = true;

    #[ORM\Column(name: 'is_active', type: 'boolean', nullable: false, options: ['default' => true])]
    private bool $isActive = true;

    public function __construct()
    {
        $this->id = Uuid::uuid4()->toString();
    }

    public function getId(): string { return $this->id; }
    public function getBankName(): string { return $this->bankName; }
    public function getAccountNumber(): string { return $this->accountNumber; }
    public function getAccountName(): string { return $this->accountName; }
    public function getBankCode(): ?string { return $this->bankCode; }
    public function isPrimary(): bool { return $this->isPrimary; }
    public function isActive(): bool { return $this->isActive; }

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
