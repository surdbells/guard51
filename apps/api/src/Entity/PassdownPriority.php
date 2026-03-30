<?php
declare(strict_types=1);
namespace Guard51\Entity;

enum PassdownPriority: string
{
    case LOW = 'low';
    case NORMAL = 'normal';
    case MEDIUM = 'medium';
    case IMPORTANT = 'important';
    case HIGH = 'high';
    case URGENT = 'urgent';

    public function label(): string { return ucfirst($this->value); }

    public function level(): int
    {
        return match($this) {
            self::LOW => 1,
            self::NORMAL => 2,
            self::MEDIUM => 3,
            self::IMPORTANT => 4,
            self::HIGH => 5,
            self::URGENT => 6,
        };
    }
}
