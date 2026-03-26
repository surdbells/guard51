<?php

declare(strict_types=1);

namespace Guard51\Entity;

enum PayType: string
{
    case HOURLY = 'hourly';
    case DAILY = 'daily';
    case MONTHLY = 'monthly';

    public function label(): string
    {
        return match ($this) {
            self::HOURLY => 'Hourly',
            self::DAILY => 'Daily',
            self::MONTHLY => 'Monthly',
        };
    }
}
