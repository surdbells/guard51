<?php

declare(strict_types=1);

namespace Guard51\Entity;

enum ReleaseType: string
{
    case STABLE = 'stable';
    case BETA = 'beta';
    case ALPHA = 'alpha';

    public function label(): string
    {
        return match ($this) {
            self::STABLE => 'Stable',
            self::BETA => 'Beta',
            self::ALPHA => 'Alpha',
        };
    }

    public function isProduction(): bool
    {
        return $this === self::STABLE;
    }
}
