<?php

declare(strict_types=1);

namespace Guard51\Entity;

enum ShiftStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case CONFIRMED = 'confirmed';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case MISSED = 'missed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PUBLISHED => 'Published',
            self::CONFIRMED => 'Confirmed',
            self::IN_PROGRESS => 'In Progress',
            self::COMPLETED => 'Completed',
            self::MISSED => 'Missed',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [self::PUBLISHED, self::CONFIRMED, self::IN_PROGRESS]);
    }

    public function canBeConfirmed(): bool
    {
        return $this === self::PUBLISHED;
    }

    public function canStart(): bool
    {
        return $this === self::CONFIRMED;
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::COMPLETED, self::MISSED, self::CANCELLED]);
    }
}
