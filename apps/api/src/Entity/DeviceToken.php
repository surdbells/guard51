<?php
declare(strict_types=1);
namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'device_tokens')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'idx_dt_user', columns: ['user_id'])]
class DeviceToken
{
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $userId;

    #[ORM\Column(type: 'string', length: 500)]
    private string $token;

    #[ORM\Column(type: 'string', length: 10, enumType: DevicePlatform::class)]
    private DevicePlatform $platform;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $isActive = true;

    public function __construct() { $this->id = Uuid::uuid4()->toString(); }

    public function getId(): string { return $this->id; }
    public function getUserId(): string { return $this->userId; }
    public function getToken(): string { return $this->token; }
    public function getPlatform(): DevicePlatform { return $this->platform; }

    public function setUserId(string $id): static { $this->userId = $id; return $this; }
    public function setToken(string $t): static { $this->token = $t; return $this; }
    public function setPlatform(DevicePlatform $p): static { $this->platform = $p; return $this; }
    public function deactivate(): static { $this->isActive = false; return $this; }

    public function toArray(): array
    {
        return [
            'id' => $this->id, 'user_id' => $this->userId, 'token' => substr($this->token, 0, 20) . '...',
            'platform' => $this->platform->value, 'platform_label' => $this->platform->label(),
            'is_active' => $this->isActive,
            'created_at' => $this->createdAt?->format(\DateTimeInterface::ATOM),
        ];
    }
}
