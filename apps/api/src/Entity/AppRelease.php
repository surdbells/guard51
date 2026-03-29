<?php

declare(strict_types=1);

namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

/**
 * Tracks uploaded app binaries (APK, IPA, .exe, .dmg, .AppImage).
 * Platform-level: NOT tenant-scoped. Managed by super admin.
 */
#[ORM\Entity]
#[ORM\Table(name: 'app_releases')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'idx_ar_app_platform', columns: ['app_key', 'platform'])]
#[ORM\Index(name: 'idx_ar_release_type', columns: ['release_type'])]
#[ORM\Index(name: 'idx_ar_active', columns: ['is_active'])]
#[ORM\UniqueConstraint(name: 'uq_ar_version', columns: ['app_key', 'platform', 'version'])]
class AppRelease
{
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'app_key', type: 'string', length: 30, enumType: AppKey::class)]
    private AppKey $appKey;

    #[ORM\Column(type: 'string', length: 20)]
    private string $version;

    #[ORM\Column(name: 'version_code', type: 'integer')]
    private int $versionCode;

    #[ORM\Column(type: 'string', length: 20, enumType: AppPlatform::class)]
    private AppPlatform $platform;

    #[ORM\Column(name: 'release_type', type: 'string', length: 20, enumType: ReleaseType::class)]
    private ReleaseType $releaseType = ReleaseType::STABLE;

    #[ORM\Column(name: 'min_api_version', type: 'string', length: 20, nullable: true)]
    private ?string $minApiVersion = null;

    #[ORM\Column(name: 'file_url', type: 'string', length: 500)]
    private string $fileUrl;

    #[ORM\Column(name: 'file_size_bytes', type: 'bigint')]
    private string $fileSizeBytes;

    #[ORM\Column(name: 'file_hash_sha256', type: 'string', length: 64)]
    private string $fileHashSha256;

    #[ORM\Column(name: 'release_notes', type: 'text', nullable: true)]
    private ?string $releaseNotes = null;

    #[ORM\Column(name: 'is_mandatory', type: 'boolean', nullable: false, options: ['default' => false])]
    private bool $isMandatory = false;

    #[ORM\Column(name: 'is_active', type: 'boolean', nullable: false, options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(name: 'uploaded_by', type: 'string', length: 36)]
    private string $uploadedBy;

    #[ORM\Column(name: 'uploaded_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $uploadedAt;

    #[ORM\Column(name: 'download_count', type: 'integer', nullable: false, options: ['default' => 0])]
    private int $downloadCount = 0;

    public function __construct()
    {
        $this->id = Uuid::uuid4()->toString();
        $this->uploadedAt = new \DateTimeImmutable();
    }

    // ── Getters ──────────────────────────────────────

    public function getId(): string { return $this->id; }
    public function getAppKey(): AppKey { return $this->appKey; }
    public function getVersion(): string { return $this->version; }
    public function getVersionCode(): int { return $this->versionCode; }
    public function getPlatform(): AppPlatform { return $this->platform; }
    public function getReleaseType(): ReleaseType { return $this->releaseType; }
    public function getMinApiVersion(): ?string { return $this->minApiVersion; }
    public function getFileUrl(): string { return $this->fileUrl; }
    public function getFileSizeBytes(): int { return (int) $this->fileSizeBytes; }
    public function getFileHashSha256(): string { return $this->fileHashSha256; }
    public function getReleaseNotes(): ?string { return $this->releaseNotes; }
    public function isMandatory(): bool { return $this->isMandatory; }
    public function isActive(): bool { return $this->isActive; }
    public function getUploadedBy(): string { return $this->uploadedBy; }
    public function getUploadedAt(): \DateTimeImmutable { return $this->uploadedAt; }
    public function getDownloadCount(): int { return $this->downloadCount; }

    // ── Setters ──────────────────────────────────────

    public function setAppKey(AppKey $appKey): static { $this->appKey = $appKey; return $this; }
    public function setVersion(string $version): static { $this->version = $version; return $this; }
    public function setVersionCode(int $versionCode): static { $this->versionCode = $versionCode; return $this; }
    public function setPlatform(AppPlatform $platform): static { $this->platform = $platform; return $this; }
    public function setReleaseType(ReleaseType $releaseType): static { $this->releaseType = $releaseType; return $this; }
    public function setMinApiVersion(?string $minApiVersion): static { $this->minApiVersion = $minApiVersion; return $this; }
    public function setFileUrl(string $fileUrl): static { $this->fileUrl = $fileUrl; return $this; }
    public function setFileSizeBytes(int $fileSizeBytes): static { $this->fileSizeBytes = (string) $fileSizeBytes; return $this; }
    public function setFileHashSha256(string $fileHashSha256): static { $this->fileHashSha256 = $fileHashSha256; return $this; }
    public function setReleaseNotes(?string $releaseNotes): static { $this->releaseNotes = $releaseNotes; return $this; }
    public function setIsMandatory(bool $isMandatory): static { $this->isMandatory = $isMandatory; return $this; }
    public function setIsActive(bool $isActive): static { $this->isActive = $isActive; return $this; }
    public function setUploadedBy(string $uploadedBy): static { $this->uploadedBy = $uploadedBy; return $this; }

    // ── Business Logic ───────────────────────────────

    public function incrementDownloads(): static
    {
        $this->downloadCount++;
        return $this;
    }

    public function deactivate(): static
    {
        $this->isActive = false;
        return $this;
    }

    public function reactivate(): static
    {
        $this->isActive = true;
        return $this;
    }

    /**
     * Compare versions using semantic versioning.
     * Returns true if this release is newer than the given version.
     */
    public function isNewerThan(string $otherVersion): bool
    {
        return version_compare($this->version, $otherVersion, '>');
    }

    /**
     * Get human-readable file size.
     */
    public function getFileSizeFormatted(): string
    {
        $bytes = $this->getFileSizeBytes();
        if ($bytes >= 1073741824) return round($bytes / 1073741824, 2) . ' GB';
        if ($bytes >= 1048576) return round($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024) return round($bytes / 1024, 2) . ' KB';
        return $bytes . ' bytes';
    }

    /**
     * Generate a signed download URL with HMAC and expiry.
     */
    public function generateSignedUrl(string $secret, int $expirySeconds = 3600): string
    {
        $expires = time() + $expirySeconds;
        $payload = "{$this->id}:{$expires}";
        $signature = hash_hmac('sha256', $payload, $secret);
        $baseUrl = $_ENV['APP_URL'] ?? 'http://localhost:8080';
        return "{$baseUrl}/api/v1/apps/download/{$this->id}?expires={$expires}&sig={$signature}";
    }

    /**
     * Verify a signed download URL.
     */
    public static function verifySignedUrl(string $releaseId, int $expires, string $signature, string $secret): bool
    {
        if ($expires < time()) {
            return false;
        }
        $payload = "{$releaseId}:{$expires}";
        $expected = hash_hmac('sha256', $payload, $secret);
        return hash_equals($expected, $signature);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'app_key' => $this->appKey->value,
            'app_name' => $this->appKey->label(),
            'version' => $this->version,
            'version_code' => $this->versionCode,
            'platform' => $this->platform->value,
            'platform_label' => $this->platform->label(),
            'release_type' => $this->releaseType->value,
            'min_api_version' => $this->minApiVersion,
            'file_size_bytes' => $this->getFileSizeBytes(),
            'file_size_formatted' => $this->getFileSizeFormatted(),
            'file_hash_sha256' => $this->fileHashSha256,
            'release_notes' => $this->releaseNotes,
            'is_mandatory' => $this->isMandatory,
            'is_active' => $this->isActive,
            'download_count' => $this->downloadCount,
            'uploaded_by' => $this->uploadedBy,
            'uploaded_at' => $this->uploadedAt->format(\DateTimeInterface::ATOM),
            'created_at' => $this->createdAt?->format(\DateTimeInterface::ATOM),
        ];
    }
}
