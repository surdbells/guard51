<?php

declare(strict_types=1);

namespace Guard51\Entity;

enum SubscriptionStatus: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case PAST_DUE = 'past_due';
    case CANCELLED = 'cancelled';
    case EXPIRED = 'expired';
    case SUSPENDED = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending Payment',
            self::ACTIVE => 'Active',
            self::PAST_DUE => 'Past Due',
            self::CANCELLED => 'Cancelled',
            self::EXPIRED => 'Expired',
            self::SUSPENDED => 'Suspended',
        };
    }

    public function isOperational(): bool
    {
        return $this === self::ACTIVE;
    }

    public function canRenew(): bool
    {
        return in_array($this, [self::ACTIVE, self::PAST_DUE, self::EXPIRED], true);
    }
}
