<?php

declare(strict_types=1);

namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
#[ORM\HasLifecycleCallbacks]
#[ORM\UniqueConstraint(name: 'uq_users_email', columns: ['email'])]
#[ORM\Index(name: 'idx_users_tenant', columns: ['tenant_id'])]
#[ORM\Index(name: 'idx_users_role', columns: ['role'])]
#[ORM\Index(name: 'idx_users_status', columns: ['is_active'])]
class User implements TenantAwareInterface
{
    use TenantAwareTrait;
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $email;

    #[ORM\Column(name: 'password_hash', type: 'string', length: 255)]
    private string $passwordHash;

    #[ORM\Column(name: 'first_name', type: 'string', length: 100)]
    private string $firstName;

    #[ORM\Column(name: 'last_name', type: 'string', length: 100)]
    private string $lastName;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(name: 'photo_url', type: 'string', length: 500, nullable: true)]
    private ?string $photoUrl = null;

    #[ORM\Column(type: 'string', length: 30, enumType: UserRole::class)]
    private UserRole $role = UserRole::GUARD;

    #[ORM\Column(name: 'is_active', type: 'boolean', nullable: false, options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(name: 'is_email_verified', type: 'boolean', nullable: false, options: ['default' => false])]
    private bool $isEmailVerified = false;

    #[ORM\Column(name: 'email_verified_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $emailVerifiedAt = null;

    #[ORM\Column(name: 'last_login_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastLoginAt = null;

    #[ORM\Column(name: 'last_login_ip', type: 'string', length: 45, nullable: true)]
    private ?string $lastLoginIp = null;

    #[ORM\Column(name: 'password_reset_token', type: 'string', length: 255, nullable: true)]
    private ?string $passwordResetToken = null;

    #[ORM\Column(name: 'password_reset_expires_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $passwordResetExpiresAt = null;

    #[ORM\Column(name: 'failed_login_attempts', type: 'integer', nullable: false, options: ['default' => 0])]
    private int $failedLoginAttempts = 0;

    #[ORM\Column(name: 'locked_until', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lockedUntil = null;

    public function __construct()
    {
        $this->id = Uuid::uuid4()->toString();
    }

    // ── Getters ──────────────────────────────────────

    public function getId(): string
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getFullName(): string
    {
        return "{$this->firstName} {$this->lastName}";
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getPhotoUrl(): ?string
    {
        return $this->photoUrl;
    }

    public function getRole(): UserRole
    {
        return $this->role;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function isEmailVerified(): bool
    {
        return $this->isEmailVerified;
    }

    public function getLastLoginAt(): ?\DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    public function getLastLoginIp(): ?string
    {
        return $this->lastLoginIp;
    }

    public function getFailedLoginAttempts(): int
    {
        return $this->failedLoginAttempts;
    }

    public function getLockedUntil(): ?\DateTimeImmutable
    {
        return $this->lockedUntil;
    }

    public function getPasswordResetToken(): ?string
    {
        return $this->passwordResetToken;
    }

    public function getPasswordResetExpiresAt(): ?\DateTimeImmutable
    {
        return $this->passwordResetExpiresAt;
    }

    // ── Setters ──────────────────────────────────────

    public function setEmail(string $email): static
    {
        $this->email = strtolower(trim($email));
        return $this;
    }

    public function setPassword(string $plainPassword): static
    {
        // Use BCRYPT (universally supported) or Argon2id if available without threads
        if (defined('PASSWORD_ARGON2ID')) {
            $this->passwordHash = password_hash($plainPassword, PASSWORD_ARGON2ID, [
                'memory_cost' => 65536,
                'time_cost' => 4,
                'threads' => 1,
            ]);
        } else {
            $this->passwordHash = password_hash($plainPassword, PASSWORD_BCRYPT, [
                'cost' => 12,
            ]);
        }
        return $this;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;
        return $this;
    }

    public function setPhotoUrl(?string $photoUrl): static
    {
        $this->photoUrl = $photoUrl;
        return $this;
    }

    public function setRole(UserRole $role): static
    {
        $this->role = $role;
        return $this;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    // ── Business Logic ───────────────────────────────

    public function verifyPassword(string $plainPassword): bool
    {
        return password_verify($plainPassword, $this->passwordHash);
    }

    public function recordLogin(string $ip): static
    {
        $this->lastLoginAt = new \DateTimeImmutable();
        $this->lastLoginIp = $ip;
        $this->failedLoginAttempts = 0;
        $this->lockedUntil = null;
        return $this;
    }

    public function recordFailedLogin(): static
    {
        $this->failedLoginAttempts++;

        // Lock account after 5 consecutive failures for 15 minutes
        if ($this->failedLoginAttempts >= 5) {
            $this->lockedUntil = new \DateTimeImmutable('+15 minutes');
        }

        return $this;
    }

    public function isLocked(): bool
    {
        if ($this->lockedUntil === null) {
            return false;
        }
        return $this->lockedUntil > new \DateTimeImmutable();
    }

    public function verifyEmail(): static
    {
        $this->isEmailVerified = true;
        $this->emailVerifiedAt = new \DateTimeImmutable();
        return $this;
    }

    public function generatePasswordResetToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $this->passwordResetToken = hash('sha256', $token);
        $this->passwordResetExpiresAt = new \DateTimeImmutable('+1 hour');
        return $token;
    }

    public function isPasswordResetTokenValid(string $token): bool
    {
        if ($this->passwordResetToken === null || $this->passwordResetExpiresAt === null) {
            return false;
        }
        if ($this->passwordResetExpiresAt < new \DateTimeImmutable()) {
            return false;
        }
        return hash_equals($this->passwordResetToken, hash('sha256', $token));
    }

    public function clearPasswordResetToken(): static
    {
        $this->passwordResetToken = null;
        $this->passwordResetExpiresAt = null;
        return $this;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SUPER_ADMIN;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'full_name' => $this->getFullName(),
            'phone' => $this->phone,
            'photo_url' => $this->photoUrl,
            'role' => $this->role->value,
            'tenant_id' => $this->tenantId,
            'is_active' => $this->isActive,
            'is_email_verified' => $this->isEmailVerified,
            'last_login_at' => $this->lastLoginAt?->format(\DateTimeInterface::ATOM),
            'created_at' => $this->createdAt?->format(\DateTimeInterface::ATOM),
            'updated_at' => $this->updatedAt?->format(\DateTimeInterface::ATOM),
        ];
    }
}
