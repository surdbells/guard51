<?php

declare(strict_types=1);

namespace Guard51\Entity;

enum TenantType: string
{
    case PRIVATE_SECURITY = 'private_security';
    case STATE_POLICE = 'state_police';
    case NEIGHBORHOOD_WATCH = 'neighborhood_watch';
    case LG_SECURITY = 'lg_security';
    case NSCDC = 'nscdc';

    public function label(): string
    {
        return match ($this) {
            self::PRIVATE_SECURITY => 'Private Security Company',
            self::STATE_POLICE => 'State Police',
            self::NEIGHBORHOOD_WATCH => 'Neighborhood Watch',
            self::LG_SECURITY => 'Local Government Security Committee',
            self::NSCDC => 'Nigeria Security and Civil Defence Corps',
        };
    }

    public function isGovernment(): bool
    {
        return $this !== self::PRIVATE_SECURITY;
    }

    /**
     * Return all government tenant types.
     * @return self[]
     */
    public static function governmentTypes(): array
    {
        return [
            self::STATE_POLICE,
            self::NEIGHBORHOOD_WATCH,
            self::LG_SECURITY,
            self::NSCDC,
        ];
    }
}
