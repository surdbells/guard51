<?php
declare(strict_types=1);
namespace Guard51\Repository;
use Guard51\Entity\ParkingLot;

/** @extends BaseRepository<ParkingLot> */
class ParkingLotRepository extends BaseRepository
{
    protected function getEntityClass(): string { return ParkingLot::class; }
}
