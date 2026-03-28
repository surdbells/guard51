<?php
declare(strict_types=1);
namespace Guard51\Entity;

enum NotificationChannel: string
{
    case PUSH = 'push';
    case SMS = 'sms';
    case EMAIL = 'email';
    case IN_APP = 'in_app';
    public function label(): string { return match($this) { self::IN_APP => 'In-App', default => strtoupper($this->value) }; }
}
