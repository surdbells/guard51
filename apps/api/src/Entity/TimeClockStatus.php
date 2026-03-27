<?php

declare(strict_types=1);

namespace Guard51\Entity;

enum TimeClockStatus: string
{
    case CLOCKED_IN = 'clocked_in';
    case CLOCKED_OUT = 'clocked_out';
    case AUTO_CLOCKED_OUT = 'auto_clocked_out';
    case RECONCILED = 'reconciled';

    public function label(): string
    {
        return match ($this) {
            self::CLOCKED_IN => 'Clocked In',
            self::CLOCKED_OUT => 'Clocked Out',
            self::AUTO_CLOCKED_OUT => 'Auto Clocked Out',
            self::RECONCILED => 'Reconciled',
        };
    }

    public function isActive(): bool { return $this === self::CLOCKED_IN; }
}
