<?php
declare(strict_types=1);
namespace Guard51\Entity;

enum PayrollItemStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case PAID = 'paid';

    public function label(): string { return ucfirst($this->value); }
}
