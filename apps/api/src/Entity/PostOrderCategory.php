<?php

declare(strict_types=1);

namespace Guard51\Entity;

enum PostOrderCategory: string
{
    case GENERAL = 'general';
    case ACCESS_CONTROL = 'access_control';
    case PATROL = 'patrol';
    case EMERGENCY = 'emergency';
    case VISITOR = 'visitor';
    case PARKING = 'parking';

    public function label(): string
    {
        return match ($this) {
            self::GENERAL => 'General',
            self::ACCESS_CONTROL => 'Access Control',
            self::PATROL => 'Patrol',
            self::EMERGENCY => 'Emergency',
            self::VISITOR => 'Visitor Management',
            self::PARKING => 'Parking',
        };
    }
}
