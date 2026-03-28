<?php
declare(strict_types=1);
namespace Guard51\Entity;

enum ParkingLotType: string
{
    case REGULAR = 'regular';
    case VIP = 'vip';
    case RESERVED = 'reserved';
    case DISABLED = 'disabled';
    public function label(): string { return strtoupper($this->value === 'vip' ? 'VIP' : ucfirst($this->value)); }
}
