<?php

declare(strict_types=1);

namespace Guard51\Entity;

enum GuardStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case SUSPENDED = 'suspended';
    case TERMINATED = 'terminated';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::SUSPENDED => 'Suspended',
            self::TERMINATED => 'Terminated',
        };
    }

    public function isOperational(): bool { return $this === self::ACTIVE; }
    public function canBeAssigned(): bool { return $this === self::ACTIVE; }
}
