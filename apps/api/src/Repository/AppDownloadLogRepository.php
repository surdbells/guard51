<?php

declare(strict_types=1);

namespace Guard51\Repository;

use Guard51\Entity\AppDownloadLog;

/**
 * @extends BaseRepository<AppDownloadLog>
 */
class AppDownloadLogRepository extends BaseRepository
{
    protected function getEntityClass(): string
    {
        return AppDownloadLog::class;
    }

    public function countByRelease(string $releaseId): int
    {
        return $this->count(['releaseId' => $releaseId]);
    }

    public function countByTenant(string $tenantId): int
    {
        return $this->count([]);
    }

    /**
     * Download counts grouped by app_key for a time period.
     */
    public function getAnalytics(?\DateTimeImmutable $since = null): array
    {
        $conn = $this->em->getConnection();
        $params = [];
        $where = '';

        if ($since) {
            $where = 'WHERE adl.downloaded_at >= :since';
            $params['since'] = $since->format('Y-m-d H:i:s');
        }

        $sql = "
            SELECT ar.app_key, ar.platform, COUNT(adl.id) as download_count
            FROM app_download_logs adl
            JOIN app_releases ar ON ar.id = adl.release_id
            {$where}
            GROUP BY ar.app_key, ar.platform
            ORDER BY download_count DESC
        ";

        return $conn->fetchAllAssociative($sql, $params);
    }
}
