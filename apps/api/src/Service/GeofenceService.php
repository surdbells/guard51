<?php

declare(strict_types=1);

namespace Guard51\Service;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

final class GeofenceService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Check if a GPS coordinate is inside a site's geofence.
     * Uses PostGIS ST_DWithin for circular geofences and ST_Contains for polygons.
     */
    public function isInsideGeofence(string $siteId, float $lat, float $lng): bool
    {
        $conn = $this->em->getConnection();

        $sql = "
            SELECT 
                CASE 
                    WHEN s.geofence_type = 'circle' THEN
                        ST_DWithin(
                            ST_MakePoint(s.longitude, s.latitude)::geography,
                            ST_MakePoint(:lng, :lat)::geography,
                            s.geofence_radius
                        )
                    WHEN s.geofence_type = 'polygon' AND s.geofence_polygon IS NOT NULL THEN
                        ST_Contains(
                            s.geofence_polygon,
                            ST_SetSRID(ST_MakePoint(:lng, :lat), 4326)
                        )
                    ELSE FALSE
                END AS inside
            FROM sites s
            WHERE s.id = :site_id
        ";

        $result = $conn->fetchOne($sql, [
            'site_id' => $siteId,
            'lat' => $lat,
            'lng' => $lng,
        ]);

        return (bool) $result;
    }

    /**
     * Check all active guards against their assigned site geofences.
     * Returns array of violations.
     */
    public function detectViolations(string $tenantId): array
    {
        $conn = $this->em->getConnection();

        $sql = "
            SELECT 
                gl.guard_id,
                gl.latitude AS guard_lat,
                gl.longitude AS guard_lng,
                s.id AS site_id,
                s.name AS site_name,
                CASE 
                    WHEN s.geofence_type = 'circle' THEN
                        NOT ST_DWithin(
                            ST_MakePoint(s.longitude, s.latitude)::geography,
                            ST_MakePoint(gl.longitude, gl.latitude)::geography,
                            s.geofence_radius
                        )
                    WHEN s.geofence_type = 'polygon' AND s.geofence_polygon IS NOT NULL THEN
                        NOT ST_Contains(
                            s.geofence_polygon,
                            ST_SetSRID(ST_MakePoint(gl.longitude, gl.latitude), 4326)
                        )
                    ELSE FALSE
                END AS is_violation
            FROM guard_locations gl
            INNER JOIN shifts sh ON sh.guard_id = gl.guard_id 
                AND sh.status = 'in_progress'
                AND sh.tenant_id = :tenant_id
            INNER JOIN sites s ON s.id = sh.site_id
            WHERE gl.tenant_id = :tenant_id
                AND gl.received_at > NOW() - INTERVAL '5 minutes'
            ORDER BY gl.received_at DESC
        ";

        $results = $conn->fetchAllAssociative($sql, ['tenant_id' => $tenantId]);

        $violations = array_filter($results, fn(array $row) => (bool) $row['is_violation']);

        if (!empty($violations)) {
            $this->logger->warning('Geofence violations detected.', [
                'tenant_id' => $tenantId,
                'count' => count($violations),
            ]);
        }

        return $violations;
    }

    /**
     * Calculate distance between two GPS points in meters.
     */
    public function distanceInMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $conn = $this->em->getConnection();

        $sql = "
            SELECT ST_Distance(
                ST_MakePoint(:lng1, :lat1)::geography,
                ST_MakePoint(:lng2, :lat2)::geography
            ) AS distance_meters
        ";

        return (float) $conn->fetchOne($sql, [
            'lat1' => $lat1,
            'lng1' => $lng1,
            'lat2' => $lat2,
            'lng2' => $lng2,
        ]);
    }

    /**
     * Find the nearest guard to a given GPS position.
     * Used by dispatcher to suggest nearest available guard.
     */
    public function findNearestGuards(string $tenantId, float $lat, float $lng, int $limit = 5): array
    {
        $conn = $this->em->getConnection();

        $sql = "
            SELECT DISTINCT ON (gl.guard_id)
                gl.guard_id,
                g.first_name,
                g.last_name,
                gl.latitude,
                gl.longitude,
                ST_Distance(
                    ST_MakePoint(gl.longitude, gl.latitude)::geography,
                    ST_MakePoint(:lng, :lat)::geography
                ) AS distance_meters,
                gl.received_at
            FROM guard_locations gl
            INNER JOIN guards g ON g.id = gl.guard_id
            WHERE gl.tenant_id = :tenant_id
                AND gl.received_at > NOW() - INTERVAL '10 minutes'
                AND g.status = 'active'
            ORDER BY gl.guard_id, gl.received_at DESC
        ";

        // Wrap to sort by distance and limit
        $wrappedSql = "
            SELECT * FROM ({$sql}) AS latest
            ORDER BY distance_meters ASC
            LIMIT :lim
        ";

        return $conn->fetchAllAssociative($wrappedSql, [
            'tenant_id' => $tenantId,
            'lat' => $lat,
            'lng' => $lng,
            'lim' => $limit,
        ]);
    }
}
