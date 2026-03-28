<?php
declare(strict_types=1);
namespace Guard51\Repository;
use Guard51\Entity\ParkingArea;

/** @extends BaseRepository<ParkingArea> */
class ParkingAreaRepository extends BaseRepository
{
    protected function getEntityClass(): string { return ParkingArea::class; }
}
