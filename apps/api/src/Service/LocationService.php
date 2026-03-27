<?php

declare(strict_types=1);

namespace Guard51\Service;

use Guard51\Entity\GeofenceAlert;
use Guard51\Entity\GeofenceAlertType;
use Guard51\Entity\GuardLocation;
use Guard51\Entity\IdleAlert;
use Guard51\Entity\LocationSource;
use Guard51\Exception\ApiException;
use Guard51\Repository\GeofenceAlertRepository;
use Guard51\Repository\GuardLocationRepository;
use Guard51\Repository\IdleAlertRepository;
use Psr\Log\LoggerInterface;

final class LocationService
{
    public function __construct(
        private readonly GuardLocationRepository $locationRepo,
        private readonly GeofenceAlertRepository $geofenceAlertRepo,
        private readonly IdleAlertRepository $idleAlertRepo,
        private readonly GeofenceService $geofenceService,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Ingest a GPS ping from a guard.
     */
    public function recordLocation(string $tenantId, array $data): GuardLocation
    {
        if (empty($data['guard_id']) || !isset($data['lat']) || !isset($data['lng'])) {
            throw ApiException::validation('guard_id, lat, lng are required.');
        }

        $loc = new GuardLocation();
        $loc->setTenantId($tenantId)
            ->setGuardId($data['guard_id'])
            ->setLatitude((float) $data['lat'])
            ->setLongitude((float) $data['lng'])
            ->setAccuracy((float) ($data['accuracy'] ?? 10))
            ->setSource(LocationSource::from($data['source'] ?? 'http_poll'))
            ->setRecordedAt(isset($data['recorded_at']) ? new \DateTimeImmutable($data['recorded_at']) : new \DateTimeImmutable());

        if (isset($data['site_id'])) $loc->setSiteId($data['site_id']);
        if (isset($data['speed'])) $loc->setSpeed((float) $data['speed']);
        if (isset($data['heading'])) $loc->setHeading((float) $data['heading']);
        if (isset($data['altitude'])) $loc->setAltitude((float) $data['altitude']);
        if (isset($data['battery_level'])) $loc->setBatteryLevel((int) $data['battery_level']);
        if (isset($data['is_moving'])) $loc->setIsMoving((bool) $data['is_moving']);

        $this->locationRepo->save($loc);

        // Check geofence violations asynchronously (simplified inline for now)
        if (!empty($data['site_id'])) {
            $this->checkGeofenceViolation($tenantId, $data['guard_id'], $data['site_id'], (float) $data['lat'], (float) $data['lng']);
        }

        return $loc;
    }

    /**
     * Batch ingest locations (for offline sync or bulk WebSocket batches).
     */
    public function recordBatch(string $tenantId, array $locations): int
    {
        $entities = [];
        foreach ($locations as $data) {
            $loc = new GuardLocation();
            $loc->setTenantId($tenantId)
                ->setGuardId($data['guard_id'])
                ->setLatitude((float) $data['lat'])
                ->setLongitude((float) $data['lng'])
                ->setAccuracy((float) ($data['accuracy'] ?? 10))
                ->setSource(LocationSource::from($data['source'] ?? 'offline_sync'))
                ->setRecordedAt(isset($data['recorded_at']) ? new \DateTimeImmutable($data['recorded_at']) : new \DateTimeImmutable());

            if (isset($data['site_id'])) $loc->setSiteId($data['site_id']);
            if (isset($data['speed'])) $loc->setSpeed((float) $data['speed']);
            if (isset($data['battery_level'])) $loc->setBatteryLevel((int) $data['battery_level']);
            if (isset($data['is_moving'])) $loc->setIsMoving((bool) $data['is_moving']);

            $entities[] = $loc;
        }
        return $this->locationRepo->bulkInsert($entities);
    }

    public function getActiveGuardLocations(string $tenantId): array
    {
        return $this->locationRepo->findActiveGuardLocations($tenantId);
    }

    public function getLatestLocation(string $guardId): ?GuardLocation
    {
        return $this->locationRepo->findLatestByGuard($guardId);
    }

    public function getPath(string $guardId, string $startTime, string $endTime): array
    {
        $locations = $this->locationRepo->findPath($guardId, new \DateTimeImmutable($startTime), new \DateTimeImmutable($endTime));
        return array_map(fn($l) => $l->toArray(), $locations);
    }

    // ── Geofence Alerts ──────────────────────────────

    public function getActiveGeofenceAlerts(string $tenantId): array
    {
        return $this->geofenceAlertRepo->findActiveByTenant($tenantId);
    }

    public function getRecentGeofenceAlerts(string $tenantId, int $hours = 24): array
    {
        return $this->geofenceAlertRepo->findByTenantRecent($tenantId, $hours);
    }

    public function acknowledgeGeofenceAlert(string $alertId, string $userId): GeofenceAlert
    {
        $alert = $this->geofenceAlertRepo->findOrFail($alertId);
        $alert->acknowledge($userId);
        $this->geofenceAlertRepo->save($alert);
        return $alert;
    }

    // ── Idle Alerts ──────────────────────────────────

    public function getActiveIdleAlerts(string $tenantId): array
    {
        return $this->idleAlertRepo->findActiveByTenant($tenantId);
    }

    public function acknowledgeIdleAlert(string $alertId, string $userId): IdleAlert
    {
        $alert = $this->idleAlertRepo->findOrFail($alertId);
        $alert->acknowledge($userId);
        $this->idleAlertRepo->save($alert);
        return $alert;
    }

    // ── Private ──────────────────────────────────────

    private function checkGeofenceViolation(string $tenantId, string $guardId, string $siteId, float $lat, float $lng): void
    {
        try {
            $inside = $this->geofenceService->isInsideGeofence($siteId, $lat, $lng);
            if (!$inside) {
                $alert = new GeofenceAlert();
                $alert->setTenantId($tenantId)
                    ->setGuardId($guardId)
                    ->setSiteId($siteId)
                    ->setAlertType(GeofenceAlertType::EXIT)
                    ->setLatitude($lat)
                    ->setLongitude($lng)
                    ->setMessage("Guard exited geofence for assigned site.");
                $this->geofenceAlertRepo->save($alert);
                $this->logger->warning('Geofence violation detected.', ['guard_id' => $guardId, 'site_id' => $siteId]);
            }
        } catch (\Throwable $e) {
            $this->logger->error('Geofence check failed.', ['error' => $e->getMessage()]);
        }
    }
}
