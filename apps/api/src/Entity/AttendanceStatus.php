<?php

declare(strict_types=1);

namespace Guard51\Entity;

enum AttendanceStatus: string
{
    case PRESENT = 'present';
    case LATE = 'late';
    case ABSENT = 'absent';
    case EXCUSED = 'excused';
    case ON_LEAVE = 'on_leave';

    public function label(): string { return ucfirst(str_replace('_', ' ', $this->value)); }
    public function isPresent(): bool { return in_array($this, [self::PRESENT, self::LATE]); }
}
