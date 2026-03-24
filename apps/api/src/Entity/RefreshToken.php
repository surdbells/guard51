<?php

declare(strict_types=1);

namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'refresh_tokens')]
#[ORM\Index(name: 'idx_rt_user', columns: ['user_id'])]
#[ORM\Index(name: 'idx_rt_token', columns: ['token_hash'])]
#[ORM\Index(name: 'idx_rt_expires', columns: ['expires_at'])]
class RefreshToken
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $userId;

    #[ORM\Column(type: 'string', length: 64)]
    private string $tokenHash;

    #[ORM\Column(type: 'string', length: 200, nullable: true)]
    private ?string $userAgent = null;

    #[ORM\Column(type: 'string', length: 45, nullable: true)]
    private ?string $ipAddress = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => false])]
    private bool $isRevoked = false;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $revokedAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->id = Uuid::uuid4()->toString();
        $this->createdAt = new \DateTimeImmutable();
    }

    // ── Getters ──────────────────────────────────────

    public function getId(): string { return $this->id; }
    public function getUserId(): string { return $this->userId; }
    public function getTokenHash(): string { return $this->tokenHash; }
    public function getUserAgent(): ?string { return $this->userAgent; }
    public function getIpAddress(): ?string { return $this->ipAddress; }
    public function getExpiresAt(): \DateTimeImmutable { return $this->expiresAt; }
    public function isRevoked(): bool { return $this->isRevoked; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    // ── Setters ──────────────────────────────────────

    public function setUserId(string $userId): static { $this->userId = $userId; return $this; }
    public function setUserAgent(?string $userAgent): static { $this->userAgent = $userAgent; return $this; }
    public function setIpAddress(?string $ipAddress): static { $this->ipAddress = $ipAddress; return $this; }
    public function setExpiresAt(\DateTimeImmutable $expiresAt): static { $this->expiresAt = $expiresAt; return $this; }

    /**
     * Set the token. Stores only the SHA-256 hash — raw token is never persisted.
     */
    public function setToken(string $rawToken): static
    {
        $this->tokenHash = hash('sha256', $rawToken);
        return $this;
    }

    // ── Business Logic ───────────────────────────────

    public function isExpired(): bool
    {
        return $this->expiresAt < new \DateTimeImmutable();
    }

    public function isValid(): bool
    {
        return !$this->isRevoked && !$this->isExpired();
    }

    public function matchesToken(string $rawToken): bool
    {
        return hash_equals($this->tokenHash, hash('sha256', $rawToken));
    }

    public function revoke(): static
    {
        $this->isRevoked = true;
        $this->revokedAt = new \DateTimeImmutable();
        return $this;
    }

    /**
     * Generate a new raw refresh token (returned to client) and store its hash.
     */
    public static function generate(string $userId, int $ttlSeconds, ?string $userAgent = null, ?string $ip = null): array
    {
        $rawToken = bin2hex(random_bytes(32));

        $entity = new self();
        $entity->setUserId($userId);
        $entity->setToken($rawToken);
        $entity->setExpiresAt(new \DateTimeImmutable("+{$ttlSeconds} seconds"));
        $entity->setUserAgent($userAgent);
        $entity->setIpAddress($ip);

        return ['entity' => $entity, 'raw_token' => $rawToken];
    }
}
