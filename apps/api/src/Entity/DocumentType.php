<?php

declare(strict_types=1);

namespace Guard51\Entity;

enum DocumentType: string
{
    case LICENSE = 'license';
    case ID_CARD = 'id_card';
    case CERTIFICATE = 'certificate';
    case MEDICAL = 'medical';
    case CONTRACT = 'contract';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::LICENSE => 'Security License',
            self::ID_CARD => 'ID Card',
            self::CERTIFICATE => 'Certificate',
            self::MEDICAL => 'Medical Report',
            self::CONTRACT => 'Contract',
            self::OTHER => 'Other',
        };
    }
}
