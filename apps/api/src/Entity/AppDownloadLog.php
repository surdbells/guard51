<?php

declare(strict_types=1);

namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

/**
 * Tracks every app download for analytics.
 * Append-only — no updates or deletes.
 */
#[ORM\Entity]
#[ORM\Table(name: 'app_download_logs')]
#[ORM\Index(name: 'idx_adl_release', columns: ['release_id'])]
#[ORM\Index(name: 'idx_adl_tenant', columns: ['tenant_id'])]
#[ORM\Index(name: 'idx_adl_downloaded', columns: ['downloaded_at'])]
class AppDownloadLog
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $releaseId;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $tenantId = null;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $downloadedBy = null;

    #[ORM\Column(type: 'string', length: 45)]
    private string $ipAddress;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $userAgent = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $downloadedAt;

    public function __construct()
    {
        $this->id = Uuid::uuid4()->toString();
        $this->downloadedAt = new \DateTimeImmutable();
    }

    public function getId(): string { return $this->id; }
    public function getReleaseId(): string { return $this->releaseId; }
    public function getTenantId(): ?string { return $this->tenantId; }
    public function getDownloadedBy(): ?string { return $this->downloadedBy; }
    public function getIpAddress(): string { return $this->ipAddress; }
    public function getDownloadedAt(): \DateTimeImmutable { return $this->downloadedAt; }

    public function setReleaseId(string $releaseId): static { $this->releaseId = $releaseId; return $this; }
    public function setTenantId(?string $tenantId): static { $this->tenantId = $tenantId; return $this; }
    public function setDownloadedBy(?string $downloadedBy): static { $this->downloadedBy = $downloadedBy; return $this; }
    public function setIpAddress(string $ipAddress): static { $this->ipAddress = $ipAddress; return $this; }
    public function setUserAgent(?string $userAgent): static { $this->userAgent = $userAgent; return $this; }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'release_id' => $this->releaseId,
            'tenant_id' => $this->tenantId,
            'downloaded_by' => $this->downloadedBy,
            'ip_address' => $this->ipAddress,
            'downloaded_at' => $this->downloadedAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
