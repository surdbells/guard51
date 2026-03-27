<?php
declare(strict_types=1);
namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'watch_mode_logs')]
#[ORM\Index(name: 'idx_wml_tenant', columns: ['tenant_id'])]
#[ORM\Index(name: 'idx_wml_site', columns: ['site_id'])]
class WatchModeLog implements TenantAwareInterface
{
    use TenantAwareTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $guardId;

    #[ORM\Column(type: 'string', length: 36)]
    private string $siteId;

    #[ORM\Column(type: 'string', length: 10, enumType: MediaType::class)]
    private MediaType $mediaType;

    #[ORM\Column(type: 'string', length: 500)]
    private string $mediaUrl;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $caption = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 8, nullable: true)]
    private ?string $latitude = null;

    #[ORM\Column(type: 'decimal', precision: 11, scale: 8, nullable: true)]
    private ?string $longitude = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $recordedAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct() { $this->id = Uuid::uuid4()->toString(); $this->recordedAt = new \DateTimeImmutable(); $this->createdAt = new \DateTimeImmutable(); }

    public function getId(): string { return $this->id; }
    public function setGuardId(string $id): static { $this->guardId = $id; return $this; }
    public function setSiteId(string $id): static { $this->siteId = $id; return $this; }
    public function setMediaType(MediaType $t): static { $this->mediaType = $t; return $this; }
    public function setMediaUrl(string $u): static { $this->mediaUrl = $u; return $this; }
    public function setCaption(?string $c): static { $this->caption = $c; return $this; }
    public function setLatitude(?float $v): static { $this->latitude = $v !== null ? (string) $v : null; return $this; }
    public function setLongitude(?float $v): static { $this->longitude = $v !== null ? (string) $v : null; return $this; }
    public function setRecordedAt(\DateTimeImmutable $t): static { $this->recordedAt = $t; return $this; }

    public function toArray(): array
    {
        return [
            'id' => $this->id, 'tenant_id' => $this->tenantId, 'guard_id' => $this->guardId,
            'site_id' => $this->siteId, 'media_type' => $this->mediaType->value,
            'media_url' => $this->mediaUrl, 'caption' => $this->caption,
            'lat' => $this->latitude ? (float) $this->latitude : null,
            'lng' => $this->longitude ? (float) $this->longitude : null,
            'recorded_at' => $this->recordedAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
