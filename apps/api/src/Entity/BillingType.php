<?php

declare(strict_types=1);

namespace Guard51\Entity;

enum BillingType: string
{
    case HOURLY = 'hourly';
    case DAILY = 'daily';
    case MONTHLY = 'monthly';
    case CONTRACT = 'contract';

    public function label(): string { return ucfirst($this->value); }
}
