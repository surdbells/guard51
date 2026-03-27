<?php
declare(strict_types=1);
namespace Guard51\Entity;

enum TaskStatus: string
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case OVERDUE = 'overdue';

    public function label(): string { return ucfirst(str_replace('_', ' ', $this->value)); }
    public function isActive(): bool { return in_array($this, [self::PENDING, self::IN_PROGRESS, self::OVERDUE]); }
}
