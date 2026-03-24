<?php

declare(strict_types=1);

namespace Guard51\Entity;

enum TenantStatus: string
{
    case ACTIVE = 'active';
    case TRIAL = 'trial';
    case SUSPENDED = 'suspended';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::TRIAL => 'Trial',
            self::SUSPENDED => 'Suspended',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function isOperational(): bool
    {
        return $this === self::ACTIVE || $this === self::TRIAL;
    }
}
