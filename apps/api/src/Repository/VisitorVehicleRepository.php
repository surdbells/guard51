<?php
declare(strict_types=1);
namespace Guard51\Repository;
use Guard51\Entity\VisitorVehicle;

/** @extends BaseRepository<VisitorVehicle> */
class VisitorVehicleRepository extends BaseRepository
{
    protected function getEntityClass(): string { return VisitorVehicle::class; }
}
