<?php
declare(strict_types=1);
namespace Guard51\Entity;

enum PanicAlertStatus: string
{
    case TRIGGERED = 'triggered';
    case ACKNOWLEDGED = 'acknowledged';
    case RESPONDING = 'responding';
    case RESOLVED = 'resolved';
    case FALSE_ALARM = 'false_alarm';

    public function label(): string { return ucfirst(str_replace('_', ' ', $this->value)); }
    public function isActive(): bool { return in_array($this, [self::TRIGGERED, self::ACKNOWLEDGED, self::RESPONDING]); }
    public function isTerminal(): bool { return in_array($this, [self::RESOLVED, self::FALSE_ALARM]); }
}
