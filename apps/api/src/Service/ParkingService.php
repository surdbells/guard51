<?php
declare(strict_types=1);
namespace Guard51\Service;

use Guard51\Entity\AreaStatus;
use Guard51\Entity\OwnerType;
use Guard51\Entity\ParkingArea;
use Guard51\Entity\ParkingIncident;
use Guard51\Entity\ParkingIncidentType;
use Guard51\Entity\ParkingLot;
use Guard51\Entity\ParkingLotType;
use Guard51\Entity\ParkingVehicle;
use Guard51\Exception\ApiException;
use Guard51\Repository\ParkingAreaRepository;
use Guard51\Repository\ParkingIncidentRepository;
use Guard51\Repository\ParkingIncidentTypeRepository;
use Guard51\Repository\ParkingLotRepository;
use Guard51\Repository\ParkingVehicleRepository;
use Psr\Log\LoggerInterface;

final class ParkingService
{
    public function __construct(
        private readonly ParkingAreaRepository $areaRepo,
        private readonly ParkingLotRepository $lotRepo,
        private readonly ParkingVehicleRepository $vehicleRepo,
        private readonly ParkingIncidentTypeRepository $typeRepo,
        private readonly ParkingIncidentRepository $incidentRepo,
        private readonly LoggerInterface $logger,
    ) {}

    // Areas
    public function createArea(string $tenantId, array $data): ParkingArea
    {
        if (empty($data['site_id']) || empty($data['name']) || !isset($data['total_spaces'])) throw ApiException::validation('site_id, name, total_spaces required.');
        $a = new ParkingArea();
        $a->setTenantId($tenantId)->setSiteId($data['site_id'])->setName($data['name'])->setTotalSpaces((int) $data['total_spaces']);
        $this->areaRepo->save($a);
        return $a;
    }
    public function listAreas(string $tenantId): array { return $this->areaRepo->findBy(['tenantId' => $tenantId]); }

    // Lots
    public function createLot(string $areaId, array $data): ParkingLot
    {
        if (empty($data['name']) || !isset($data['capacity'])) throw ApiException::validation('name, capacity required.');
        $l = new ParkingLot();
        $l->setParkingAreaId($areaId)->setName($data['name'])->setCapacity((int) $data['capacity']);
        if (!empty($data['lot_type'])) $l->setLotType(ParkingLotType::from($data['lot_type']));
        $this->lotRepo->save($l);
        return $l;
    }

    // Vehicles
    public function logEntry(string $tenantId, array $data, string $guardId): ParkingVehicle
    {
        if (empty($data['site_id']) || empty($data['plate_number'])) throw ApiException::validation('site_id, plate_number required.');
        $v = new ParkingVehicle();
        $v->setTenantId($tenantId)->setSiteId($data['site_id'])->setPlateNumber($data['plate_number'])->setLoggedBy($guardId);
        if (isset($data['parking_lot_id'])) $v->setParkingLotId($data['parking_lot_id']);
        if (isset($data['make'])) $v->setMake($data['make']);
        if (isset($data['model'])) $v->setModel($data['model']);
        if (isset($data['color'])) $v->setColor($data['color']);
        if (isset($data['owner_name'])) $v->setOwnerName($data['owner_name']);
        if (isset($data['owner_phone'])) $v->setOwnerPhone($data['owner_phone']);
        if (!empty($data['owner_type'])) $v->setOwnerType(OwnerType::from($data['owner_type']));
        $this->vehicleRepo->save($v);
        return $v;
    }

    public function logExit(string $vehicleId): ParkingVehicle
    {
        $v = $this->vehicleRepo->findOrFail($vehicleId);
        $v->markDeparted();
        $this->vehicleRepo->save($v);
        return $v;
    }

    public function listParked(string $siteId): array { return $this->vehicleRepo->findParkedBySite($siteId); }
    public function listParkedByTenant(string $tenantId): array { return $this->vehicleRepo->findBy(['tenantId' => $tenantId, 'exitTime' => null], ['entryTime' => 'DESC']); }
    public function countParked(string $siteId): int { return $this->vehicleRepo->countParked($siteId); }

    // Incidents
    public function reportIncident(string $tenantId, array $data, string $guardId): ParkingIncident
    {
        if (empty($data['site_id']) || empty($data['incident_type_id']) || empty($data['description'])) throw ApiException::validation('site_id, incident_type_id, description required.');
        $i = new ParkingIncident();
        $i->setTenantId($tenantId)->setSiteId($data['site_id'])->setIncidentTypeId($data['incident_type_id'])
            ->setDescription($data['description'])->setReportedBy($guardId);
        if (isset($data['vehicle_id'])) $i->setVehicleId($data['vehicle_id']);
        if (isset($data['attachments'])) $i->setAttachments($data['attachments']);
        $this->incidentRepo->save($i);
        return $i;
    }

    // Incident types
    public function createIncidentType(string $tenantId, array $data): ParkingIncidentType
    {
        if (empty($data['name'])) throw ApiException::validation('name required.');
        $t = new ParkingIncidentType();
        $t->setTenantId($tenantId)->setName($data['name']);
        if (isset($data['form_fields'])) $t->setFormFields($data['form_fields']);
        $this->typeRepo->save($t);
        return $t;
    }
    public function listIncidentTypes(string $tenantId): array { return $this->typeRepo->findBy(['tenantId' => $tenantId]); }
}
