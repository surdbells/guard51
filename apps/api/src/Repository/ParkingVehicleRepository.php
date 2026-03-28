<?php
declare(strict_types=1);
namespace Guard51\Repository;
use Guard51\Entity\ParkingVehicle;

/** @extends BaseRepository<ParkingVehicle> */
class ParkingVehicleRepository extends BaseRepository
{
    protected function getEntityClass(): string { return ParkingVehicle::class; }

    public function findParkedBySite(string $siteId): array
    {
        return $this->findBy(['siteId' => $siteId, 'status' => 'parked'], ['entryTime' => 'DESC']);
    }

    public function countParked(string $siteId): int { return $this->count(['siteId' => $siteId, 'status' => 'parked']); }
}
