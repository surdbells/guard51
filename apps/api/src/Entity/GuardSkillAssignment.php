<?php

declare(strict_types=1);

namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'guard_skill_assignments')]
#[ORM\UniqueConstraint(name: 'uq_gsa', columns: ['guard_id', 'skill_id'])]
#[ORM\Index(name: 'idx_gsa_guard', columns: ['guard_id'])]
class GuardSkillAssignment
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'guard_id', type: 'string', length: 36)]
    private string $guardId;

    #[ORM\Column(name: 'skill_id', type: 'string', length: 36)]
    private string $skillId;

    #[ORM\Column(name: 'certified_at', type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $certifiedAt = null;

    #[ORM\Column(name: 'expires_at', type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $expiresAt = null;

    public function __construct() { $this->id = Uuid::uuid4()->toString(); }

    public function getId(): string { return $this->id; }
    public function getGuardId(): string { return $this->guardId; }
    public function getSkillId(): string { return $this->skillId; }
    public function getCertifiedAt(): ?\DateTimeImmutable { return $this->certifiedAt; }
    public function getExpiresAt(): ?\DateTimeImmutable { return $this->expiresAt; }

    public function setGuardId(string $id): static { $this->guardId = $id; return $this; }
    public function setSkillId(string $id): static { $this->skillId = $id; return $this; }
    public function setCertifiedAt(?\DateTimeImmutable $d): static { $this->certifiedAt = $d; return $this; }
    public function setExpiresAt(?\DateTimeImmutable $d): static { $this->expiresAt = $d; return $this; }

    public function isExpired(): bool { return $this->expiresAt !== null && $this->expiresAt < new \DateTimeImmutable(); }

    public function toArray(): array
    {
        return ['id' => $this->id, 'guard_id' => $this->guardId, 'skill_id' => $this->skillId, 'certified_at' => $this->certifiedAt?->format('Y-m-d'), 'expires_at' => $this->expiresAt?->format('Y-m-d'), 'is_expired' => $this->isExpired()];
    }
}
