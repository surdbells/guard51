<?php

declare(strict_types=1);

namespace Guard51\Entity;

enum PassdownPriority: string
{
    case NORMAL = 'normal';
    case IMPORTANT = 'important';
    case URGENT = 'urgent';

    public function label(): string { return ucfirst($this->value); }
    public function level(): int { return match ($this) { self::NORMAL => 1, self::IMPORTANT => 2, self::URGENT => 3 }; }
}
