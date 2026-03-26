<?php

declare(strict_types=1);

namespace Guard51\Entity;

enum ClientStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case SUSPENDED = 'suspended';

    public function label(): string { return ucfirst($this->value); }
    public function isOperational(): bool { return $this === self::ACTIVE; }
}
