<?php
declare(strict_types=1);
namespace Guard51\Service;

use Guard51\Entity\PatrolVehicle;
use Guard51\Entity\VehiclePatrolHit;
use Guard51\Entity\VehiclePatrolRoute;
use Guard51\Entity\VehicleStatus;
use Guard51\Entity\VehicleType;
use Guard51\Exception\ApiException;
use Guard51\Repository\PatrolVehicleRepository;
use Guard51\Repository\VehiclePatrolHitRepository;
use Guard51\Repository\VehiclePatrolRouteRepository;
use Psr\Log\LoggerInterface;

final class VehiclePatrolService
{
    public function __construct(
        private readonly PatrolVehicleRepository $vehicleRepo,
        private readonly VehiclePatrolRouteRepository $routeRepo,
        private readonly VehiclePatrolHitRepository $hitRepo,
        private readonly LoggerInterface $logger,
    ) {}

    // Vehicles
    public function createVehicle(string $tenantId, array $data): PatrolVehicle
    {
        if (empty($data['vehicle_name']) || empty($data['plate_number']) || empty($data['vehicle_type'])) throw ApiException::validation('vehicle_name, plate_number, vehicle_type required.');
        $v = new PatrolVehicle();
        $v->setTenantId($tenantId)->setVehicleName($data['vehicle_name'])->setPlateNumber($data['plate_number'])
            ->setVehicleType(VehicleType::from($data['vehicle_type']));
        if (isset($data['assigned_guard_id'])) $v->setAssignedGuardId($data['assigned_guard_id']);
        if (isset($data['notes'])) $v->setNotes($data['notes']);
        $this->vehicleRepo->save($v);
        return $v;
    }

    public function listVehicles(string $tenantId): array { return $this->vehicleRepo->findBy(['tenantId' => $tenantId]); }

    // Routes
    public function createRoute(string $tenantId, array $data): VehiclePatrolRoute
    {
        if (empty($data['name']) || empty($data['sites'])) throw ApiException::validation('name, sites required.');
        $r = new VehiclePatrolRoute();
        $r->setTenantId($tenantId)->setName($data['name'])->setSites($data['sites']);
        if (isset($data['description'])) $r->setDescription($data['description']);
        if (isset($data['expected_hits_per_day'])) $r->setExpectedHitsPerDay((int) $data['expected_hits_per_day']);
        if (isset($data['reset_time'])) $r->setResetTime($data['reset_time']);
        $this->routeRepo->save($r);
        return $r;
    }

    public function listRoutes(string $tenantId): array { return $this->routeRepo->findBy(['tenantId' => $tenantId]); }

    // Hits
    public function recordHit(string $tenantId, array $data): VehiclePatrolHit
    {
        if (empty($data['route_id']) || empty($data['vehicle_id']) || empty($data['guard_id']) || empty($data['site_id'])) throw ApiException::validation('route_id, vehicle_id, guard_id, site_id required.');
        $todayCount = $this->hitRepo->countTodayHits($data['route_id']);
        $hit = new VehiclePatrolHit();
        $hit->setTenantId($tenantId)->setRouteId($data['route_id'])->setVehicleId($data['vehicle_id'])
            ->setGuardId($data['guard_id'])->setSiteId($data['site_id'])->setHitNumber($todayCount + 1)
            ->setLatitude((float) ($data['lat'] ?? 0))->setLongitude((float) ($data['lng'] ?? 0));
        if (isset($data['notes'])) $hit->setNotes($data['notes']);
        if (isset($data['photo_url'])) $hit->setPhotoUrl($data['photo_url']);
        $this->hitRepo->save($hit);
        return $hit;
    }

    public function getRouteHits(string $routeId, ?string $date = null): array { return $this->hitRepo->findByRoute($routeId, $date); }
}
