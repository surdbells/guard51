<?php
declare(strict_types=1);
namespace Guard51\Service;

use Guard51\Entity\IdDocType;
use Guard51\Entity\Visitor;
use Guard51\Entity\VisitorVehicle;
use Guard51\Exception\ApiException;
use Guard51\Repository\VisitorRepository;
use Guard51\Repository\VisitorVehicleRepository;
use Psr\Log\LoggerInterface;

final class VisitorService
{
    public function __construct(
        private readonly VisitorRepository $visitorRepo,
        private readonly VisitorVehicleRepository $vehicleRepo,
        private readonly LoggerInterface $logger,
    ) {}

    public function checkIn(string $tenantId, array $data, string $guardId): Visitor
    {
        if (empty($data['site_id']) || empty($data['first_name']) || empty($data['last_name']) || empty($data['purpose'])) {
            throw ApiException::validation('site_id, first_name, last_name, purpose required.');
        }
        $v = new Visitor();
        $v->setTenantId($tenantId)->setSiteId($data['site_id'])->setFirstName($data['first_name'])
            ->setLastName($data['last_name'])->setPurpose($data['purpose'])->setCheckedInBy($guardId);
        if (isset($data['phone'])) $v->setPhone($data['phone']);
        if (isset($data['email'])) $v->setEmail($data['email']);
        if (isset($data['company'])) $v->setCompany($data['company']);
        if (isset($data['host_name'])) $v->setHostName($data['host_name']);
        if (isset($data['id_type'])) $v->setIdType(IdDocType::from($data['id_type']));
        if (isset($data['id_number'])) $v->setIdNumber($data['id_number']);
        if (isset($data['photo_url'])) $v->setPhotoUrl($data['photo_url']);
        if (isset($data['vehicle_plate'])) $v->setVehiclePlate($data['vehicle_plate']);
        if (isset($data['notes'])) $v->setNotes($data['notes']);
        $this->visitorRepo->save($v);

        // Log vehicle if plate provided
        if (!empty($data['vehicle_plate'])) {
            $vv = new VisitorVehicle();
            $vv->setVisitorId($v->getId())->setPlateNumber($data['vehicle_plate']);
            if (isset($data['vehicle_make'])) $vv->setMake($data['vehicle_make']);
            if (isset($data['vehicle_model'])) $vv->setModel($data['vehicle_model']);
            if (isset($data['vehicle_color'])) $vv->setColor($data['vehicle_color']);
            $this->vehicleRepo->save($vv);
        }
        return $v;
    }

    public function checkOut(string $visitorId, string $guardId): Visitor
    {
        $v = $this->visitorRepo->findOrFail($visitorId);
        $v->checkOut($guardId);
        $this->visitorRepo->save($v);
        return $v;
    }

    public function listBySite(string $siteId, ?string $date = null): array { return $this->visitorRepo->findBySite($siteId, $date); }
    public function listCheckedIn(string $siteId): array { return $this->visitorRepo->findCheckedIn($siteId); }
    public function search(string $tenantId, string $name): array { return $this->visitorRepo->searchByName($tenantId, $name); }
}
