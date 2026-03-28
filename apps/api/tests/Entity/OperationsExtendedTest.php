<?php
declare(strict_types=1);
namespace Guard51\Tests\Entity;

use Guard51\Entity\AreaStatus;
use Guard51\Entity\IdDocType;
use Guard51\Entity\OwnerType;
use Guard51\Entity\ParkingArea;
use Guard51\Entity\ParkingIncident;
use Guard51\Entity\ParkingIncidentStatus;
use Guard51\Entity\ParkingLot;
use Guard51\Entity\ParkingLotType;
use Guard51\Entity\ParkingVehicle;
use Guard51\Entity\ParkingVehicleStatus;
use Guard51\Entity\PatrolVehicle;
use Guard51\Entity\VehiclePatrolHit;
use Guard51\Entity\VehiclePatrolRoute;
use Guard51\Entity\VehicleStatus;
use Guard51\Entity\VehicleType;
use Guard51\Entity\Visitor;
use Guard51\Entity\VisitorStatus;
use Guard51\Entity\VisitorVehicle;
use PHPUnit\Framework\TestCase;

class OperationsExtendedTest extends TestCase
{
    // ── Vehicle Patrol ───────────────────────────────

    public function testPatrolVehicle(): void
    {
        $v = new PatrolVehicle();
        $v->setTenantId('t-1')->setVehicleName('Hilux Patrol 1')->setPlateNumber('LG-234-KJA')
            ->setVehicleType(VehicleType::SUV)->setAssignedGuardId('g-1');
        $arr = $v->toArray();
        $this->assertEquals('Hilux Patrol 1', $arr['vehicle_name']);
        $this->assertEquals('suv', $arr['vehicle_type']);
        $this->assertEquals('SUV', $arr['vehicle_type_label']);
        $this->assertEquals('active', $arr['status']);
    }

    public function testPatrolRoute(): void
    {
        $r = new VehiclePatrolRoute();
        $r->setTenantId('t-1')->setName('Lekki Loop')->setSites(['s-1', 's-2', 's-3'])
            ->setExpectedHitsPerDay(3)->setResetTime('06:00');
        $arr = $r->toArray();
        $this->assertEquals('Lekki Loop', $arr['name']);
        $this->assertEquals(3, $arr['site_count']);
        $this->assertEquals(3, $arr['expected_hits_per_day']);
        $this->assertEquals('06:00', $arr['reset_time']);
    }

    public function testPatrolHit(): void
    {
        $h = new VehiclePatrolHit();
        $h->setTenantId('t-1')->setRouteId('r-1')->setVehicleId('v-1')->setGuardId('g-1')
            ->setSiteId('s-1')->setHitNumber(2)->setLatitude(6.4541)->setLongitude(3.3947)
            ->setNotes('All clear');
        $arr = $h->toArray();
        $this->assertEquals(2, $arr['hit_number']);
        $this->assertEquals(6.4541, $arr['lat']);
    }

    // ── Visitor ──────────────────────────────────────

    public function testVisitorCheckIn(): void
    {
        $v = new Visitor();
        $v->setTenantId('t-1')->setSiteId('s-1')->setFirstName('Ade')->setLastName('Ogundimu')
            ->setPhone('+2348012345678')->setCompany('ABC Corp')->setPurpose('Meeting with CEO')
            ->setHostName('Mr. Johnson')->setIdType(IdDocType::NATIONAL_ID)->setIdNumber('NIN123456')
            ->setVehiclePlate('AB-123-CD')->setCheckedInBy('g-1');
        $arr = $v->toArray();
        $this->assertEquals('Ade Ogundimu', $arr['full_name']);
        $this->assertEquals('checked_in', $arr['status']);
        $this->assertEquals('national_id', $arr['id_type']);
        $this->assertNotNull($arr['check_in_at']);
        $this->assertNull($arr['check_out_at']);
    }

    public function testVisitorCheckOut(): void
    {
        $v = new Visitor();
        $v->setTenantId('t-1')->setSiteId('s-1')->setFirstName('Test')->setLastName('User')
            ->setPurpose('Delivery')->setCheckedInBy('g-1');
        $v->checkOut('g-2');
        $arr = $v->toArray();
        $this->assertEquals('checked_out', $arr['status']);
        $this->assertNotNull($arr['check_out_at']);
        $this->assertEquals('g-2', $arr['checked_out_by']);
    }

    public function testVisitorVehicle(): void
    {
        $vv = new VisitorVehicle();
        $vv->setVisitorId('vis-1')->setPlateNumber('AB-123-CD')->setMake('Toyota')
            ->setModel('Camry')->setColor('Silver');
        $arr = $vv->toArray();
        $this->assertEquals('AB-123-CD', $arr['plate_number']);
        $this->assertEquals('Toyota', $arr['make']);
    }

    // ── Parking ──────────────────────────────────────

    public function testParkingArea(): void
    {
        $a = new ParkingArea();
        $a->setTenantId('t-1')->setSiteId('s-1')->setName('Main Parking')->setTotalSpaces(200);
        $arr = $a->toArray();
        $this->assertEquals('Main Parking', $arr['name']);
        $this->assertEquals(200, $arr['total_spaces']);
        $this->assertEquals('active', $arr['status']);
    }

    public function testParkingLot(): void
    {
        $l = new ParkingLot();
        $l->setParkingAreaId('pa-1')->setName('VIP Section')->setCapacity(20)
            ->setLotType(ParkingLotType::VIP);
        $arr = $l->toArray();
        $this->assertEquals('VIP Section', $arr['name']);
        $this->assertEquals('VIP', $arr['lot_type_label']);
    }

    public function testParkingVehicleEntry(): void
    {
        $v = new ParkingVehicle();
        $v->setTenantId('t-1')->setSiteId('s-1')->setPlateNumber('LG-567-XYZ')
            ->setMake('Honda')->setModel('Civic')->setColor('Blue')
            ->setOwnerName('Mrs. Ade')->setOwnerType(OwnerType::RESIDENT)->setLoggedBy('g-1');
        $arr = $v->toArray();
        $this->assertEquals('parked', $arr['status']);
        $this->assertEquals('resident', $arr['owner_type']);
        $this->assertNotNull($arr['entry_time']);
        $this->assertNull($arr['exit_time']);
    }

    public function testParkingVehicleExit(): void
    {
        $v = new ParkingVehicle();
        $v->setTenantId('t-1')->setSiteId('s-1')->setPlateNumber('TEST-123')
            ->setLoggedBy('g-1');
        $v->markDeparted();
        $this->assertEquals(ParkingVehicleStatus::DEPARTED, $v->toArray()['status'] === 'departed' ? ParkingVehicleStatus::DEPARTED : ParkingVehicleStatus::PARKED);
    }

    public function testParkingViolation(): void
    {
        $v = new ParkingVehicle();
        $v->setTenantId('t-1')->setSiteId('s-1')->setPlateNumber('BAD-001')
            ->setLoggedBy('g-1');
        $v->markViolation();
        $this->assertEquals('violation', $v->toArray()['status']);
    }

    public function testParkingIncident(): void
    {
        $i = new ParkingIncident();
        $i->setTenantId('t-1')->setSiteId('s-1')->setIncidentTypeId('pit-1')
            ->setDescription('Unauthorized parking in disabled spot')->setReportedBy('g-1')
            ->setVehicleId('pv-1');
        $arr = $i->toArray();
        $this->assertEquals('reported', $arr['status']);
        $i->resolve();
        $this->assertEquals('resolved', $i->toArray()['status']);
    }
}
