<?php
declare(strict_types=1);
namespace Guard51\Entity;

enum AreaStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    public function label(): string { return ucfirst($this->value); }
}
