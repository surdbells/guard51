<?php

declare(strict_types=1);

namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

/**
 * Tracks staff invitations sent by tenant admins.
 * Invited user receives an email with a unique token to create their account.
 */
#[ORM\Entity]
#[ORM\Table(name: 'tenant_invitations')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'idx_ti_tenant', columns: ['tenant_id'])]
#[ORM\Index(name: 'idx_ti_email', columns: ['email'])]
#[ORM\Index(name: 'idx_ti_token', columns: ['token_hash'])]
#[ORM\Index(name: 'idx_ti_status', columns: ['status'])]
class TenantInvitation implements TenantAwareInterface
{
    use TenantAwareTrait;
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $email;

    #[ORM\Column(name: 'first_name', type: 'string', length: 100, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(name: 'last_name', type: 'string', length: 100, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column(type: 'string', length: 30, enumType: UserRole::class)]
    private UserRole $role = UserRole::GUARD;

    #[ORM\Column(name: 'token_hash', type: 'string', length: 64)]
    private string $tokenHash;

    #[ORM\Column(type: 'string', length: 30, enumType: InvitationStatus::class)]
    private InvitationStatus $status = InvitationStatus::PENDING;

    #[ORM\Column(name: 'invited_by', type: 'string', length: 36)]
    private string $invitedBy;

    #[ORM\Column(name: 'expires_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column(name: 'accepted_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $acceptedAt = null;

    #[ORM\Column(name: 'accepted_user_id', type: 'string', length: 36, nullable: true)]
    private ?string $acceptedUserId = null;

    #[ORM\Column(name: 'revoked_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $revokedAt = null;

    #[ORM\Column(name: 'personal_message', type: 'text', nullable: true)]
    private ?string $personalMessage = null;

    #[ORM\Column(name: 'resend_count', type: 'integer', nullable: false, options: ['default' => 0])]
    private int $resendCount = 0;

    #[ORM\Column(name: 'last_resent_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastResentAt = null;

    public function __construct()
    {
        $this->id = Uuid::uuid4()->toString();
    }

    // ── Getters ──────────────────────────────────────

    public function getId(): string { return $this->id; }
    public function getEmail(): string { return $this->email; }
    public function getFirstName(): ?string { return $this->firstName; }
    public function getLastName(): ?string { return $this->lastName; }
    public function getRole(): UserRole { return $this->role; }
    public function getTokenHash(): string { return $this->tokenHash; }
    public function getStatus(): InvitationStatus { return $this->status; }
    public function getInvitedBy(): string { return $this->invitedBy; }
    public function getExpiresAt(): \DateTimeImmutable { return $this->expiresAt; }
    public function getAcceptedAt(): ?\DateTimeImmutable { return $this->acceptedAt; }
    public function getAcceptedUserId(): ?string { return $this->acceptedUserId; }
    public function getPersonalMessage(): ?string { return $this->personalMessage; }
    public function getResendCount(): int { return $this->resendCount; }

    // ── Setters ──────────────────────────────────────

    public function setEmail(string $email): static { $this->email = strtolower(trim($email)); return $this; }
    public function setFirstName(?string $firstName): static { $this->firstName = $firstName; return $this; }
    public function setLastName(?string $lastName): static { $this->lastName = $lastName; return $this; }
    public function setRole(UserRole $role): static { $this->role = $role; return $this; }
    public function setInvitedBy(string $invitedBy): static { $this->invitedBy = $invitedBy; return $this; }
    public function setPersonalMessage(?string $msg): static { $this->personalMessage = $msg; return $this; }

    /**
     * Set the invitation token. Stores only SHA-256 hash.
     */
    public function setToken(string $rawToken): static
    {
        $this->tokenHash = hash('sha256', $rawToken);
        return $this;
    }

    public function setExpiresAt(\DateTimeImmutable $expiresAt): static
    {
        $this->expiresAt = $expiresAt;
        return $this;
    }

    // ── Business Logic ───────────────────────────────

    public function matchesToken(string $rawToken): bool
    {
        return hash_equals($this->tokenHash, hash('sha256', $rawToken));
    }

    public function isExpired(): bool
    {
        return $this->expiresAt < new \DateTimeImmutable();
    }

    public function isPending(): bool
    {
        return $this->status === InvitationStatus::PENDING && !$this->isExpired();
    }

    public function canAccept(): bool
    {
        return $this->status === InvitationStatus::PENDING && !$this->isExpired();
    }

    public function accept(string $userId): static
    {
        $this->status = InvitationStatus::ACCEPTED;
        $this->acceptedAt = new \DateTimeImmutable();
        $this->acceptedUserId = $userId;
        return $this;
    }

    public function revoke(): static
    {
        $this->status = InvitationStatus::REVOKED;
        $this->revokedAt = new \DateTimeImmutable();
        return $this;
    }

    public function markExpired(): static
    {
        $this->status = InvitationStatus::EXPIRED;
        return $this;
    }

    public function recordResend(): static
    {
        $this->resendCount++;
        $this->lastResentAt = new \DateTimeImmutable();
        return $this;
    }

    /**
     * Generate a new invitation with token.
     * @return array{entity: self, raw_token: string}
     */
    public static function create(
        string $tenantId,
        string $email,
        UserRole $role,
        string $invitedBy,
        ?string $firstName = null,
        ?string $lastName = null,
        ?string $personalMessage = null,
        int $expiryDays = 7,
    ): array {
        $rawToken = bin2hex(random_bytes(32));

        $invitation = new self();
        $invitation->setTenantId($tenantId)
            ->setEmail($email)
            ->setFirstName($firstName)
            ->setLastName($lastName)
            ->setRole($role)
            ->setToken($rawToken)
            ->setInvitedBy($invitedBy)
            ->setExpiresAt(new \DateTimeImmutable("+{$expiryDays} days"))
            ->setPersonalMessage($personalMessage);

        return ['entity' => $invitation, 'raw_token' => $rawToken];
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenantId,
            'email' => $this->email,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'role' => $this->role->value,
            'status' => $this->status->value,
            'invited_by' => $this->invitedBy,
            'expires_at' => $this->expiresAt->format(\DateTimeInterface::ATOM),
            'is_expired' => $this->isExpired(),
            'accepted_at' => $this->acceptedAt?->format(\DateTimeInterface::ATOM),
            'resend_count' => $this->resendCount,
            'personal_message' => $this->personalMessage,
            'created_at' => $this->createdAt?->format(\DateTimeInterface::ATOM),
        ];
    }
}
