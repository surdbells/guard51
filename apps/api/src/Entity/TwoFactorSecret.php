<?php
declare(strict_types=1);
namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'two_factor_secrets')]
#[ORM\UniqueConstraint(name: 'uq_2fa_user', columns: ['user_id'])]
class TwoFactorSecret
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'user_id', type: 'string', length: 36)]
    private string $userId;

    #[ORM\Column(type: 'string', length: 64)]
    private string $secret;

    #[ORM\Column(name: 'is_enabled', type: 'boolean', options: ['default' => false])]
    private bool $isEnabled = false;

    #[ORM\Column(name: 'backup_codes', type: 'json')]
    private array $backupCodes = [];

    #[ORM\Column(name: 'verified_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $verifiedAt = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct() { $this->id = Uuid::uuid4()->toString(); $this->createdAt = new \DateTimeImmutable(); }

    public function getId(): string { return $this->id; }
    public function getUserId(): string { return $this->userId; }
    public function getSecret(): string { return $this->secret; }
    public function isEnabled(): bool { return $this->isEnabled; }
    public function getBackupCodes(): array { return $this->backupCodes; }

    public function setUserId(string $id): static { $this->userId = $id; return $this; }
    public function setSecret(string $s): static { $this->secret = $s; return $this; }
    public function enable(): static { $this->isEnabled = true; $this->verifiedAt = new \DateTimeImmutable(); return $this; }
    public function disable(): static { $this->isEnabled = false; return $this; }
    public function setBackupCodes(array $codes): static { $this->backupCodes = $codes; return $this; }

    public function useBackupCode(string $code): bool
    {
        $idx = array_search($code, $this->backupCodes);
        if ($idx === false) return false;
        unset($this->backupCodes[$idx]);
        $this->backupCodes = array_values($this->backupCodes);
        return true;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id, 'user_id' => $this->userId,
            'is_enabled' => $this->isEnabled,
            'backup_codes_remaining' => count($this->backupCodes),
            'verified_at' => $this->verifiedAt?->format(\DateTimeInterface::ATOM),
        ];
    }
}
