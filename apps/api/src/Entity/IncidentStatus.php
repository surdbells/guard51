<?php
declare(strict_types=1);
namespace Guard51\Entity;

enum IncidentStatus: string
{
    case REPORTED = 'reported';
    case ACKNOWLEDGED = 'acknowledged';
    case INVESTIGATING = 'investigating';
    case ESCALATED = 'escalated';
    case RESOLVED = 'resolved';
    case CLOSED = 'closed';

    public function label(): string { return ucfirst($this->value); }
    public function isActive(): bool { return in_array($this, [self::REPORTED, self::ACKNOWLEDGED, self::INVESTIGATING, self::ESCALATED]); }
    public function isTerminal(): bool { return in_array($this, [self::RESOLVED, self::CLOSED]); }
}
