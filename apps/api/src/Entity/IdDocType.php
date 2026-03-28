<?php
declare(strict_types=1);
namespace Guard51\Entity;

enum IdDocType: string
{
    case NATIONAL_ID = 'national_id';
    case DRIVERS_LICENSE = 'drivers_license';
    case PASSPORT = 'passport';
    case COMPANY_ID = 'company_id';
    case OTHER = 'other';
    public function label(): string { return ucfirst(str_replace('_', ' ', $this->value)); }
}
