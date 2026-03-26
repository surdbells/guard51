<?php

declare(strict_types=1);

namespace Guard51\Entity;

enum GeofenceType: string
{
    case CIRCLE = 'circle';
    case POLYGON = 'polygon';

    public function label(): string
    {
        return match ($this) {
            self::CIRCLE => 'Circle',
            self::POLYGON => 'Polygon',
        };
    }
}
