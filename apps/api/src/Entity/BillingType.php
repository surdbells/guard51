<?php
declare(strict_types=1);
namespace Guard51\Entity;

enum BillingType: string
{
    case HOURLY = 'hourly';
    case DAILY = 'daily';
    case MONTHLY = 'monthly';
    case CONTRACT = 'contract';
    case PER_GUARD = 'per_guard';
    case FIXED = 'fixed';
    case CUSTOM = 'custom';

    public function label(): string { return match($this) {
        self::PER_GUARD => 'Per Guard',
        default => ucfirst(str_replace('_', ' ', $this->value)),
    }; }
}
