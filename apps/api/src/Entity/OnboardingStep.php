<?php

declare(strict_types=1);

namespace Guard51\Entity;

/**
 * Tracks onboarding wizard progress. Steps 1-2 are handled by auth/register.
 * Steps 3-7 are completed post-registration via the onboarding API.
 */
enum OnboardingStep: string
{
    case COMPANY_INFO = 'company_info';           // Step 1 (register)
    case ADMIN_ACCOUNT = 'admin_account';         // Step 2 (register)
    case SELECT_PLAN = 'select_plan';             // Step 3
    case PAYMENT_METHOD = 'payment_method';       // Step 4
    case BANK_ACCOUNT = 'bank_account';           // Step 5
    case FIRST_SITE = 'first_site';               // Step 6
    case INVITE_GUARDS = 'invite_guards';         // Step 7

    public function stepNumber(): int
    {
        return match ($this) {
            self::COMPANY_INFO => 1,
            self::ADMIN_ACCOUNT => 2,
            self::SELECT_PLAN => 3,
            self::PAYMENT_METHOD => 4,
            self::BANK_ACCOUNT => 5,
            self::FIRST_SITE => 6,
            self::INVITE_GUARDS => 7,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::COMPANY_INFO => 'Company Information',
            self::ADMIN_ACCOUNT => 'Admin Account',
            self::SELECT_PLAN => 'Select Plan',
            self::PAYMENT_METHOD => 'Payment Method',
            self::BANK_ACCOUNT => 'Bank Account Setup',
            self::FIRST_SITE => 'Add First Site',
            self::INVITE_GUARDS => 'Invite Guards',
        };
    }

    /**
     * Steps that must be completed during onboarding wizard.
     * Steps 1-2 already done via registration.
     * @return self[]
     */
    public static function postRegistrationSteps(): array
    {
        return [
            self::SELECT_PLAN,
            self::PAYMENT_METHOD,
            self::BANK_ACCOUNT,
            self::FIRST_SITE,
            self::INVITE_GUARDS,
        ];
    }
}
