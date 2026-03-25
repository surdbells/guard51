<?php

declare(strict_types=1);

namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

/**
 * Per-tenant app configuration: auto-update, version pinning, app-specific settings.
 */
#[ORM\Entity]
#[ORM\Table(name: 'tenant_app_configs')]
#[ORM\HasLifecycleCallbacks]
#[ORM\UniqueConstraint(name: 'uq_tac_tenant_app', columns: ['tenant_id', 'app_key'])]
#[ORM\Index(name: 'idx_tac_tenant', columns: ['tenant_id'])]
class TenantAppConfig implements TenantAwareInterface
{
    use TenantAwareTrait;
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 50)]
    private string $appKey;

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => true])]
    private bool $autoUpdate = true;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $pinnedVersion = null;

    #[ORM\Column(type: 'json', nullable: false, options: ['default' => '{}'])]
    private array $settings = [];

    public function __construct()
    {
        $this->id = Uuid::uuid4()->toString();
    }

    public function getId(): string { return $this->id; }
    public function getAppKey(): string { return $this->appKey; }
    public function isAutoUpdate(): bool { return $this->autoUpdate; }
    public function getPinnedVersion(): ?string { return $this->pinnedVersion; }
    public function getSettings(): array { return $this->settings; }

    public function setAppKey(string $appKey): static { $this->appKey = $appKey; return $this; }
    public function setAutoUpdate(bool $autoUpdate): static { $this->autoUpdate = $autoUpdate; return $this; }
    public function setPinnedVersion(?string $pinnedVersion): static { $this->pinnedVersion = $pinnedVersion; return $this; }
    public function setSettings(array $settings): static { $this->settings = $settings; return $this; }

    public function isPinned(): bool
    {
        return $this->pinnedVersion !== null;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenantId,
            'app_key' => $this->appKey,
            'auto_update' => $this->autoUpdate,
            'pinned_version' => $this->pinnedVersion,
            'is_pinned' => $this->isPinned(),
            'settings' => $this->settings,
            'created_at' => $this->createdAt?->format(\DateTimeInterface::ATOM),
        ];
    }
}
