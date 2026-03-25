<?php

declare(strict_types=1);

namespace Guard51\Service;

use Guard51\Entity\AppDownloadLog;
use Guard51\Entity\AppKey;
use Guard51\Entity\AppPlatform;
use Guard51\Entity\AppRelease;
use Guard51\Entity\ReleaseType;
use Guard51\Exception\ApiException;
use Guard51\Repository\AppDownloadLogRepository;
use Guard51\Repository\AppReleaseRepository;
use Guard51\Repository\TenantAppConfigRepository;
use Psr\Log\LoggerInterface;

final class AppDistributionService
{
    public function __construct(
        private readonly AppReleaseRepository $releaseRepo,
        private readonly AppDownloadLogRepository $downloadLogRepo,
        private readonly TenantAppConfigRepository $configRepo,
        private readonly FileStorageService $fileStorage,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Upload a new app release binary.
     */
    public function uploadRelease(
        AppKey $appKey,
        AppPlatform $platform,
        string $version,
        int $versionCode,
        string $fileContents,
        string $uploadedBy,
        ReleaseType $releaseType = ReleaseType::STABLE,
        ?string $releaseNotes = null,
        ?string $minApiVersion = null,
        bool $isMandatory = false,
    ): AppRelease {
        // Validate version uniqueness
        if ($this->releaseRepo->versionExists($appKey, $platform, $version)) {
            throw ApiException::conflict("Version {$version} already exists for {$appKey->value}/{$platform->value}.");
        }

        // Validate semver format
        if (!preg_match('/^\d+\.\d+\.\d+(-[\w.]+)?$/', $version)) {
            throw ApiException::validation('Version must be in semver format (e.g., 1.2.3 or 1.2.3-beta.1).');
        }

        // Store file
        $ext = $platform->fileExtension();
        $path = "apps/{$appKey->value}/{$platform->value}/{$appKey->value}-{$version}.{$ext}";
        $stored = $this->fileStorage->upload($path, $fileContents);

        if (!$stored) {
            throw ApiException::validation('Failed to store app binary. Please try again.');
        }

        // Calculate hash
        $hash = hash('sha256', $fileContents);

        // Create release record
        $release = new AppRelease();
        $release->setAppKey($appKey)
            ->setPlatform($platform)
            ->setVersion($version)
            ->setVersionCode($versionCode)
            ->setReleaseType($releaseType)
            ->setMinApiVersion($minApiVersion)
            ->setFileUrl($path)
            ->setFileSizeBytes(strlen($fileContents))
            ->setFileHashSha256($hash)
            ->setReleaseNotes($releaseNotes)
            ->setIsMandatory($isMandatory)
            ->setUploadedBy($uploadedBy)
            ->setIsActive(true);

        $this->releaseRepo->save($release);

        $this->logger->info('App release uploaded.', [
            'app' => $appKey->value,
            'platform' => $platform->value,
            'version' => $version,
            'size' => $release->getFileSizeFormatted(),
        ]);

        return $release;
    }

    /**
     * Check if an update is available for a given app.
     * Called by installed apps on launch (public, no auth).
     */
    public function checkForUpdate(
        string $appKeyStr,
        string $platformStr,
        string $currentVersion,
        ?string $tenantId = null,
    ): array {
        $appKey = AppKey::tryFrom($appKeyStr);
        $platform = AppPlatform::tryFrom($platformStr);

        if (!$appKey || !$platform) {
            return ['update_available' => false, 'error' => 'Invalid app or platform.'];
        }

        // Check if tenant has pinned version
        if ($tenantId) {
            $config = $this->configRepo->findByTenantAndApp($tenantId, $appKeyStr);
            if ($config && $config->isPinned()) {
                $pinnedVersion = $config->getPinnedVersion();
                if (version_compare($pinnedVersion, $currentVersion, '>')) {
                    // Find the pinned release
                    $releases = $this->releaseRepo->findByAppAndPlatform($appKey, $platform);
                    foreach ($releases as $release) {
                        if ($release->getVersion() === $pinnedVersion && $release->isActive()) {
                            return $this->formatUpdateResponse($release, $currentVersion);
                        }
                    }
                }
                return ['update_available' => false];
            }

            // Check auto-update preference
            if ($config && !$config->isAutoUpdate()) {
                return ['update_available' => false];
            }
        }

        // Find latest stable release
        $latest = $this->releaseRepo->findLatestStable($appKey, $platform);

        if (!$latest || !$latest->isNewerThan($currentVersion)) {
            return ['update_available' => false];
        }

        return $this->formatUpdateResponse($latest, $currentVersion);
    }

    /**
     * Generate a signed, time-limited download URL for a release.
     */
    public function getSignedDownloadUrl(string $releaseId): string
    {
        $release = $this->releaseRepo->findOrFail($releaseId);

        if (!$release->isActive()) {
            throw ApiException::notFound('This release has been deactivated.');
        }

        $secret = $_ENV['JWT_SECRET'] ?? 'change_me';
        return $release->generateSignedUrl($secret, 3600); // 1 hour expiry
    }

    /**
     * Process a download request (called when signed URL is accessed).
     * Validates signature, logs download, returns file path.
     */
    public function processDownload(
        string $releaseId,
        int $expires,
        string $signature,
        ?string $tenantId = null,
        ?string $userId = null,
        string $ipAddress = '127.0.0.1',
        ?string $userAgent = null,
    ): AppRelease {
        $secret = $_ENV['JWT_SECRET'] ?? 'change_me';

        if (!AppRelease::verifySignedUrl($releaseId, $expires, $signature, $secret)) {
            throw ApiException::unauthorized('Invalid or expired download link.');
        }

        $release = $this->releaseRepo->findOrFail($releaseId);

        if (!$release->isActive()) {
            throw ApiException::notFound('This release has been deactivated.');
        }

        // Log download
        $log = new AppDownloadLog();
        $log->setReleaseId($releaseId)
            ->setTenantId($tenantId)
            ->setDownloadedBy($userId)
            ->setIpAddress($ipAddress)
            ->setUserAgent($userAgent);
        $this->downloadLogRepo->save($log);

        // Increment counter
        $release->incrementDownloads();
        $this->releaseRepo->save($release);

        return $release;
    }

    /**
     * App heartbeat — installed apps report their version and device info.
     */
    public function recordHeartbeat(array $data): void
    {
        $this->logger->debug('App heartbeat received.', [
            'app' => $data['app'] ?? 'unknown',
            'platform' => $data['platform'] ?? 'unknown',
            'version' => $data['version'] ?? 'unknown',
            'tenant_id' => $data['tenant_id'] ?? null,
            'device_model' => $data['device_model'] ?? null,
            'os_version' => $data['os_version'] ?? null,
        ]);

        // Future: store in Redis for active-versions-in-the-wild reporting
    }

    /**
     * Get dashboard summary for super admin: all apps with latest versions.
     */
    public function getDashboard(): array
    {
        $summary = $this->releaseRepo->getReleaseSummary();
        $totalDownloads = $this->releaseRepo->getTotalDownloads();
        $totalReleases = $this->releaseRepo->count([]);

        return [
            'apps' => $summary,
            'total_releases' => $totalReleases,
            'total_downloads' => $totalDownloads,
        ];
    }

    /**
     * Get download analytics.
     */
    public function getAnalytics(?\DateTimeImmutable $since = null): array
    {
        return $this->downloadLogRepo->getAnalytics($since);
    }

    /**
     * Deactivate a release (pull a broken build).
     */
    public function deactivateRelease(string $releaseId): AppRelease
    {
        $release = $this->releaseRepo->findOrFail($releaseId);
        $release->deactivate();
        $this->releaseRepo->save($release);

        $this->logger->warning('App release deactivated.', [
            'release_id' => $releaseId,
            'app' => $release->getAppKey()->value,
            'version' => $release->getVersion(),
        ]);

        return $release;
    }

    /**
     * Get available apps for a tenant (based on their plan/features).
     */
    public function getAvailableAppsForTenant(string $tenantId): array
    {
        $available = [];

        foreach (AppKey::cases() as $appKey) {
            $platforms = $appKey->isMobile()
                ? [AppPlatform::ANDROID, AppPlatform::IOS]
                : [$appKey->defaultPlatform()];

            foreach ($platforms as $platform) {
                $latest = $this->releaseRepo->findLatestStable($appKey, $platform);
                if ($latest) {
                    $config = $this->configRepo->findByTenantAndApp($tenantId, $appKey->value);
                    $available[] = [
                        'app' => $appKey->value,
                        'app_name' => $appKey->label(),
                        'platform' => $platform->value,
                        'platform_label' => $platform->label(),
                        'latest_version' => $latest->getVersion(),
                        'release_notes' => $latest->getReleaseNotes(),
                        'file_size' => $latest->getFileSizeFormatted(),
                        'uploaded_at' => $latest->getUploadedAt()->format(\DateTimeInterface::ATOM),
                        'download_url' => $latest->generateSignedUrl($_ENV['JWT_SECRET'] ?? 'change_me'),
                        'is_mandatory' => $latest->isMandatory(),
                        'auto_update' => $config?->isAutoUpdate() ?? true,
                        'pinned_version' => $config?->getPinnedVersion(),
                    ];
                }
            }
        }

        return $available;
    }

    private function formatUpdateResponse(AppRelease $release, string $currentVersion): array
    {
        return [
            'update_available' => true,
            'latest_version' => $release->getVersion(),
            'current_version' => $currentVersion,
            'is_mandatory' => $release->isMandatory(),
            'download_url' => $release->generateSignedUrl($_ENV['JWT_SECRET'] ?? 'change_me'),
            'release_notes' => $release->getReleaseNotes(),
            'file_size' => $release->getFileSizeBytes(),
            'file_size_formatted' => $release->getFileSizeFormatted(),
            'file_hash' => $release->getFileHashSha256(),
            'min_api_version' => $release->getMinApiVersion(),
        ];
    }
}
