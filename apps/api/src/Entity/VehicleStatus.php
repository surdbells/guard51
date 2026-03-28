<?php
declare(strict_types=1);
namespace Guard51\Entity;

enum VehicleStatus: string
{
    case ACTIVE = 'active';
    case MAINTENANCE = 'maintenance';
    case RETIRED = 'retired';
    public function label(): string { return ucfirst($this->value); }
}
