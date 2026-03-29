<?php

declare(strict_types=1);

namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'client_contacts')]
#[ORM\Index(name: 'idx_cc_client', columns: ['client_id'])]
class ClientContact
{
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'client_id', type: 'string', length: 36)]
    private string $clientId;

    #[ORM\Column(type: 'string', length: 200)]
    private string $name;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $role = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $email;

    #[ORM\Column(type: 'string', length: 50)]
    private string $phone;

    #[ORM\Column(name: 'is_primary', type: 'boolean', options: ['default' => false])]
    private bool $isPrimary = false;

    public function __construct() { $this->id = Uuid::uuid4()->toString(); }

    public function getId(): string { return $this->id; }
    public function getClientId(): string { return $this->clientId; }
    public function getName(): string { return $this->name; }
    public function getRole(): ?string { return $this->role; }
    public function getEmail(): string { return $this->email; }
    public function getPhone(): string { return $this->phone; }
    public function isPrimary(): bool { return $this->isPrimary; }

    public function setClientId(string $id): static { $this->clientId = $id; return $this; }
    public function setName(string $n): static { $this->name = $n; return $this; }
    public function setRole(?string $r): static { $this->role = $r; return $this; }
    public function setEmail(string $e): static { $this->email = $e; return $this; }
    public function setPhone(string $p): static { $this->phone = $p; return $this; }
    public function setIsPrimary(bool $p): static { $this->isPrimary = $p; return $this; }

    public function toArray(): array
    {
        return [
            'id' => $this->id, 'client_id' => $this->clientId, 'name' => $this->name,
            'role' => $this->role, 'email' => $this->email, 'phone' => $this->phone,
            'is_primary' => $this->isPrimary, 'created_at' => $this->createdAt?->format(\DateTimeInterface::ATOM),
        ];
    }
}
