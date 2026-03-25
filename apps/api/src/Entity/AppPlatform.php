<?php

declare(strict_types=1);

namespace Guard51\Entity;

enum AppPlatform: string
{
    case ANDROID = 'android';
    case IOS = 'ios';
    case WINDOWS = 'windows';
    case MACOS = 'macos';
    case LINUX = 'linux';

    public function label(): string
    {
        return match ($this) {
            self::ANDROID => 'Android',
            self::IOS => 'iOS',
            self::WINDOWS => 'Windows',
            self::MACOS => 'macOS',
            self::LINUX => 'Linux',
        };
    }

    public function fileExtension(): string
    {
        return match ($this) {
            self::ANDROID => 'apk',
            self::IOS => 'ipa',
            self::WINDOWS => 'exe',
            self::MACOS => 'dmg',
            self::LINUX => 'AppImage',
        };
    }

    public function mimeType(): string
    {
        return match ($this) {
            self::ANDROID => 'application/vnd.android.package-archive',
            self::IOS => 'application/octet-stream',
            self::WINDOWS => 'application/x-msdownload',
            self::MACOS => 'application/x-apple-diskimage',
            self::LINUX => 'application/x-executable',
        };
    }

    public function isMobile(): bool
    {
        return $this === self::ANDROID || $this === self::IOS;
    }
}
