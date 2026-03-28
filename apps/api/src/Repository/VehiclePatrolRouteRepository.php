<?php
declare(strict_types=1);
namespace Guard51\Repository;
use Guard51\Entity\VehiclePatrolRoute;

/** @extends BaseRepository<VehiclePatrolRoute> */
class VehiclePatrolRouteRepository extends BaseRepository
{
    protected function getEntityClass(): string { return VehiclePatrolRoute::class; }
}
