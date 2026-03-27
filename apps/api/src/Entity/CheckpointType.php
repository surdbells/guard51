<?php
declare(strict_types=1);
namespace Guard51\Entity;

enum CheckpointType: string
{
    case NFC = 'nfc';
    case QR = 'qr';
    case VIRTUAL = 'virtual';

    public function label(): string { return match ($this) { self::NFC => 'NFC Tag', self::QR => 'QR Code', self::VIRTUAL => 'Virtual (GPS)' }; }
}
