<?php
declare(strict_types=1);
namespace Guard51\Entity;

enum Severity: string
{
    case CRITICAL = 'critical';
    case HIGH = 'high';
    case MEDIUM = 'medium';
    case LOW = 'low';

    public function label(): string { return ucfirst($this->value); }
    public function level(): int { return match ($this) { self::CRITICAL => 4, self::HIGH => 3, self::MEDIUM => 2, self::LOW => 1 }; }
}
