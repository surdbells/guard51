<?php

declare(strict_types=1);

namespace Guard51\Entity;

enum SubscriptionTier: string
{
    case ALL = 'all';
    case STARTER = 'starter';
    case PROFESSIONAL = 'professional';
    case BUSINESS = 'business';
    case ENTERPRISE = 'enterprise';

    public function label(): string
    {
        return match ($this) {
            self::ALL => 'All Plans',
            self::STARTER => 'Starter',
            self::PROFESSIONAL => 'Professional',
            self::BUSINESS => 'Business',
            self::ENTERPRISE => 'Enterprise',
        };
    }

    /**
     * Hierarchy level: lower = more basic.
     */
    public function level(): int
    {
        return match ($this) {
            self::ALL => 0,
            self::STARTER => 1,
            self::PROFESSIONAL => 2,
            self::BUSINESS => 3,
            self::ENTERPRISE => 4,
        };
    }

    /**
     * Check if this tier includes access to a module at the given minimum tier.
     */
    public function satisfies(self $requiredTier): bool
    {
        if ($requiredTier === self::ALL) {
            return true;
        }
        return $this->level() >= $requiredTier->level();
    }
}
