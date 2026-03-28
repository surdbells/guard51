<?php
declare(strict_types=1);
namespace Guard51\Entity;

enum LicenseType: string
{
    case SECURITY_LICENSE = 'security_license';
    case FIREARMS_PERMIT = 'firearms_permit';
    case FIRST_AID = 'first_aid';
    case CPR = 'cpr';
    case FIRE_SAFETY = 'fire_safety';
    case DRIVERS_LICENSE = 'drivers_license';
    case CUSTOM = 'custom';
    public function label(): string { return ucfirst(str_replace('_', ' ', $this->value)); }
}
