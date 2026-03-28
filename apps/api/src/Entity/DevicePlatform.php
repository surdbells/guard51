<?php
declare(strict_types=1);
namespace Guard51\Entity;

enum DevicePlatform: string
{
    case ANDROID = 'android';
    case IOS = 'ios';
    case WEB = 'web';
    public function label(): string { return match($this) { self::IOS => 'iOS', default => ucfirst($this->value) }; }
}
