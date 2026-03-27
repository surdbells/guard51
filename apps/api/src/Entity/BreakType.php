<?php

declare(strict_types=1);

namespace Guard51\Entity;

enum BreakType: string
{
    case PAID = 'paid';
    case UNPAID = 'unpaid';

    public function label(): string { return ucfirst($this->value); }
}
