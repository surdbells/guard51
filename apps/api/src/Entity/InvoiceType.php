<?php
declare(strict_types=1);
namespace Guard51\Entity;

enum InvoiceType: string
{
    case INVOICE = 'invoice';
    case ESTIMATE = 'estimate';
    public function label(): string { return ucfirst($this->value); }
}
