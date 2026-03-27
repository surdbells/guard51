<?php

declare(strict_types=1);

namespace Guard51\Entity;

enum ClockMethod: string
{
    case APP_GPS = 'app_gps';
    case APP_QR = 'app_qr';
    case WEB_PORTAL = 'web_portal';
    case MANUAL_ADMIN = 'manual_admin';

    public function label(): string
    {
        return match ($this) {
            self::APP_GPS => 'App (GPS)',
            self::APP_QR => 'App (QR Code)',
            self::WEB_PORTAL => 'Web Portal',
            self::MANUAL_ADMIN => 'Manual (Admin)',
        };
    }
}
