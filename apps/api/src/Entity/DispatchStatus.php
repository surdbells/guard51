<?php
declare(strict_types=1);
namespace Guard51\Entity;

enum DispatchStatus: string
{
    case RECEIVED = 'received';
    case DISPATCHED = 'dispatched';
    case IN_PROGRESS = 'in_progress';
    case RESOLVED = 'resolved';
    case CANCELLED = 'cancelled';

    public function label(): string { return ucfirst(str_replace('_', ' ', $this->value)); }
    public function isActive(): bool { return in_array($this, [self::RECEIVED, self::DISPATCHED, self::IN_PROGRESS]); }
}
