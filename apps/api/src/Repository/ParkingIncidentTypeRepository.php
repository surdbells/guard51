<?php
declare(strict_types=1);
namespace Guard51\Repository;
use Guard51\Entity\ParkingIncidentType;

/** @extends BaseRepository<ParkingIncidentType> */
class ParkingIncidentTypeRepository extends BaseRepository
{
    protected function getEntityClass(): string { return ParkingIncidentType::class; }
}
