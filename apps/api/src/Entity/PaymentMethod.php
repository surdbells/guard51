<?php

declare(strict_types=1);

namespace Guard51\Entity;

enum PaymentMethod: string
{
    case PAYSTACK = 'paystack';
    case BANK_TRANSFER = 'bank_transfer';

    public function label(): string
    {
        return match ($this) {
            self::PAYSTACK => 'Paystack (Card/Bank)',
            self::BANK_TRANSFER => 'Manual Bank Transfer',
        };
    }

    public function requiresManualConfirmation(): bool
    {
        return $this === self::BANK_TRANSFER;
    }
}
