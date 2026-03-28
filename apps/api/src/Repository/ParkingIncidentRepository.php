<?php
declare(strict_types=1);
namespace Guard51\Repository;
use Guard51\Entity\ParkingIncident;

/** @extends BaseRepository<ParkingIncident> */
class ParkingIncidentRepository extends BaseRepository
{
    protected function getEntityClass(): string { return ParkingIncident::class; }
}
