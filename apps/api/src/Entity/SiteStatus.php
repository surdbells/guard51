<?php

declare(strict_types=1);

namespace Guard51\Entity;

enum SiteStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case SUSPENDED = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::SUSPENDED => 'Suspended',
        };
    }

    public function isOperational(): bool
    {
        return $this === self::ACTIVE;
    }
}
