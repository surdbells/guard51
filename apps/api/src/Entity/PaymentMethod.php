<?php
declare(strict_types=1);
namespace Guard51\Entity;

enum PaymentMethod: string
{
    // SaaS subscription payments (Phase 0)
    case PAYSTACK = 'paystack';
    // Client invoice payments (Phase 5)
    case CASH = 'cash';
    case BANK_TRANSFER = 'bank_transfer';
    case POS_CARD = 'pos_card';
    case CHEQUE = 'cheque';

    public function label(): string
    {
        return match ($this) {
            self::PAYSTACK => 'Paystack',
            self::CASH => 'Cash',
            self::BANK_TRANSFER => 'Bank Transfer',
            self::POS_CARD => 'POS / Card',
            self::CHEQUE => 'Cheque',
        };
    }

    public function requiresManualConfirmation(): bool
    {
        return in_array($this, [self::BANK_TRANSFER, self::CASH, self::POS_CARD, self::CHEQUE]);
    }
}
