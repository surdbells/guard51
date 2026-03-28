<?php
declare(strict_types=1);
namespace Guard51\Entity;

enum VehicleType: string
{
    case CAR = 'car';
    case SUV = 'suv';
    case MOTORCYCLE = 'motorcycle';
    case VAN = 'van';
    public function label(): string { return strtoupper($this->value === 'suv' ? 'SUV' : ucfirst($this->value)); }
}
