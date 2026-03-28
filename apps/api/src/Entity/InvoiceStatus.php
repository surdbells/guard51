<?php
declare(strict_types=1);
namespace Guard51\Entity;

enum InvoiceStatus: string
{
    // Subscription invoices (Phase 0)
    case PENDING = 'pending';
    case FAILED = 'failed';
    // Client invoices (Phase 5)
    case DRAFT = 'draft';
    case SENT = 'sent';
    case VIEWED = 'viewed';
    case PARTIALLY_PAID = 'partially_paid';
    case PAID = 'paid';
    case OVERDUE = 'overdue';
    case CANCELLED = 'cancelled';

    public function label(): string { return ucfirst(str_replace('_', ' ', $this->value)); }
    public function isActive(): bool { return in_array($this, [self::PENDING, self::SENT, self::VIEWED, self::PARTIALLY_PAID, self::OVERDUE]); }
    public function isPaid(): bool { return $this === self::PAID; }
}
