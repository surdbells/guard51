<?php
declare(strict_types=1);
namespace Guard51\Entity;

enum ScanMethod: string
{
    case NFC = 'nfc';
    case QR = 'qr';
    case VIRTUAL_GPS = 'virtual_gps';

    public function label(): string { return match ($this) { self::NFC => 'NFC', self::QR => 'QR Code', self::VIRTUAL_GPS => 'Virtual GPS' }; }
}
