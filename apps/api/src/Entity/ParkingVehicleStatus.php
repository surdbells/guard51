<?php
declare(strict_types=1);
namespace Guard51\Entity;

enum ParkingVehicleStatus: string
{
    case PARKED = 'parked';
    case DEPARTED = 'departed';
    case VIOLATION = 'violation';
    public function label(): string { return ucfirst($this->value); }
}
