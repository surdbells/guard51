<?php
declare(strict_types=1);
namespace Guard51\Entity;

enum OwnerType: string
{
    case RESIDENT = 'resident';
    case VISITOR = 'visitor';
    case STAFF = 'staff';
    case UNKNOWN = 'unknown';
    public function label(): string { return ucfirst($this->value); }
}
