<?php

declare(strict_types=1);

namespace Guard51\Entity;

enum UserRole: string
{
    case SUPER_ADMIN = 'super_admin';
    case COMPANY_ADMIN = 'company_admin';
    case SUPERVISOR = 'supervisor';
    case GUARD = 'guard';
    case CLIENT = 'client';
    case DISPATCHER = 'dispatcher';
    case CITIZEN = 'citizen';

    public function label(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'Super Admin',
            self::COMPANY_ADMIN => 'Company Admin',
            self::SUPERVISOR => 'Supervisor',
            self::GUARD => 'Guard',
            self::CLIENT => 'Client',
            self::DISPATCHER => 'Dispatcher',
            self::CITIZEN => 'Citizen',
        };
    }

    /**
     * Hierarchy level: lower number = higher authority.
     */
    public function level(): int
    {
        return match ($this) {
            self::SUPER_ADMIN => 0,
            self::COMPANY_ADMIN => 1,
            self::SUPERVISOR => 2,
            self::DISPATCHER => 3,
            self::GUARD => 4,
            self::CLIENT => 5,
            self::CITIZEN => 6,
        };
    }

    public function isTenantScoped(): bool
    {
        return $this !== self::SUPER_ADMIN && $this !== self::CITIZEN;
    }

    public function isAdminLevel(): bool
    {
        return $this === self::SUPER_ADMIN || $this === self::COMPANY_ADMIN;
    }

    /**
     * Roles that can log into the back-office dashboard.
     * @return self[]
     */
    public static function backOfficeRoles(): array
    {
        return [
            self::SUPER_ADMIN,
            self::COMPANY_ADMIN,
            self::SUPERVISOR,
            self::DISPATCHER,
        ];
    }
}
