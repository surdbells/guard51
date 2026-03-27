<?php
declare(strict_types=1);
namespace Guard51\Entity;

enum GeofenceAlertType: string
{
    case EXIT = 'exit';
    case ENTRY_UNAUTHORIZED = 'entry_unauthorized';
    case EXTENDED_ABSENCE = 'extended_absence';

    public function label(): string { return match ($this) { self::EXIT => 'Geofence Exit', self::ENTRY_UNAUTHORIZED => 'Unauthorized Entry', self::EXTENDED_ABSENCE => 'Extended Absence' }; }
    public function severity(): string { return match ($this) { self::EXIT => 'high', self::ENTRY_UNAUTHORIZED => 'critical', self::EXTENDED_ABSENCE => 'medium' }; }
}
