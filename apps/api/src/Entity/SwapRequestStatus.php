<?php

declare(strict_types=1);

namespace Guard51\Entity;

enum SwapRequestStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';

    public function label(): string { return ucfirst($this->value); }
    public function isPending(): bool { return $this === self::PENDING; }
    public function isResolved(): bool { return in_array($this, [self::APPROVED, self::REJECTED, self::CANCELLED]); }
}
