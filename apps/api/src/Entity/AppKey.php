<?php

declare(strict_types=1);

namespace Guard51\Entity;

enum AppKey: string
{
    case GUARD = 'guard';
    case CLIENT = 'client';
    case SUPERVISOR = 'supervisor';
    case DISPATCHER = 'dispatcher';
    case DESKTOP_WINDOWS = 'desktop_windows';
    case DESKTOP_MAC = 'desktop_mac';
    case DESKTOP_LINUX = 'desktop_linux';

    public function label(): string
    {
        return match ($this) {
            self::GUARD => 'Guard Mobile App',
            self::CLIENT => 'Client Mobile App',
            self::SUPERVISOR => 'Supervisor Mobile App',
            self::DISPATCHER => 'Dispatcher App',
            self::DESKTOP_WINDOWS => 'Desktop App (Windows)',
            self::DESKTOP_MAC => 'Desktop App (macOS)',
            self::DESKTOP_LINUX => 'Desktop App (Linux)',
        };
    }

    public function defaultPlatform(): AppPlatform
    {
        return match ($this) {
            self::GUARD, self::CLIENT, self::SUPERVISOR, self::DISPATCHER => AppPlatform::ANDROID,
            self::DESKTOP_WINDOWS => AppPlatform::WINDOWS,
            self::DESKTOP_MAC => AppPlatform::MACOS,
            self::DESKTOP_LINUX => AppPlatform::LINUX,
        };
    }

    public function isMobile(): bool
    {
        return in_array($this, [self::GUARD, self::CLIENT, self::SUPERVISOR, self::DISPATCHER], true);
    }

    public function isDesktop(): bool
    {
        return in_array($this, [self::DESKTOP_WINDOWS, self::DESKTOP_MAC, self::DESKTOP_LINUX], true);
    }

    /** @return self[] */
    public static function mobileApps(): array
    {
        return [self::GUARD, self::CLIENT, self::SUPERVISOR, self::DISPATCHER];
    }

    /** @return self[] */
    public static function desktopApps(): array
    {
        return [self::DESKTOP_WINDOWS, self::DESKTOP_MAC, self::DESKTOP_LINUX];
    }
}
