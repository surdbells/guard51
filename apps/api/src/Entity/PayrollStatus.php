<?php
declare(strict_types=1);
namespace Guard51\Entity;

enum PayrollStatus: string
{
    case DRAFT = 'draft';
    case CALCULATING = 'calculating';
    case REVIEW = 'review';
    case APPROVED = 'approved';
    case PAID = 'paid';

    public function label(): string { return ucfirst($this->value); }
}
