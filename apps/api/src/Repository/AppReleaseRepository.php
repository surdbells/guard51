<?php

declare(strict_types=1);

namespace Guard51\Repository;

use Guard51\Entity\AppKey;
use Guard51\Entity\AppPlatform;
use Guard51\Entity\AppRelease;
use Guard51\Entity\ReleaseType;

/**
 * @extends BaseRepository<AppRelease>
 */
class AppReleaseRepository extends BaseRepository
{
    protected function getEntityClass(): string
    {
        return AppRelease::class;
    }

    /**
     * Get the latest stable release for a given app + platform.
     */
    public function findLatestStable(AppKey $appKey, AppPlatform $platform): ?AppRelease
    {
        $qb = $this->createQueryBuilder('r')
            ->where('r.appKey = :appKey')
            ->andWhere('r.platform = :platform')
            ->andWhere('r.releaseType = :type')
            ->andWhere('r.isActive = :active')
            ->setParameter('appKey', $appKey)
            ->setParameter('platform', $platform)
            ->setParameter('type', ReleaseType::STABLE)
            ->setParameter('active', true)
            ->orderBy('r.versionCode', 'DESC')
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Get all releases for a given app + platform (version history).
     * @return AppRelease[]
     */
    public function findByAppAndPlatform(AppKey $appKey, AppPlatform $platform): array
    {
        return $this->findBy(
            ['appKey' => $appKey, 'platform' => $platform],
            ['versionCode' => 'DESC']
        );
    }

    /**
     * Get all active releases grouped summary (for dashboard grid).
     * Returns latest stable for each app_key + platform combo.
     */
    public function getReleaseSummary(): array
    {
        $conn = $this->em->getConnection();
        $sql = "
            SELECT DISTINCT ON (app_key, platform)
                id, app_key, version, version_code, platform, release_type,
                file_size_bytes, download_count, is_active, uploaded_at
            FROM app_releases
            WHERE is_active = true AND release_type = 'stable'
            ORDER BY app_key, platform, version_code DESC
        ";
        return $conn->fetchAllAssociative($sql);
    }

    /**
     * Get total downloads across all releases.
     */
    public function getTotalDownloads(): int
    {
        $qb = $this->createQueryBuilder('r')
            ->select('SUM(r.downloadCount)');
        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Check if a version already exists for app + platform.
     */
    public function versionExists(AppKey $appKey, AppPlatform $platform, string $version): bool
    {
        return $this->count([
            'appKey' => $appKey,
            'platform' => $platform,
            'version' => $version,
        ]) > 0;
    }
}
