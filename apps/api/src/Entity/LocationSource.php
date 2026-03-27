<?php
declare(strict_types=1);
namespace Guard51\Entity;

enum LocationSource: string
{
    case WEBSOCKET = 'websocket';
    case HTTP_POLL = 'http_poll';
    case OFFLINE_SYNC = 'offline_sync';

    public function label(): string { return match ($this) { self::WEBSOCKET => 'WebSocket', self::HTTP_POLL => 'HTTP Poll', self::OFFLINE_SYNC => 'Offline Sync' }; }
}
