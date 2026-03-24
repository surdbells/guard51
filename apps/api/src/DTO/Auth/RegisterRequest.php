<?php

declare(strict_types=1);

namespace Guard51\DTO\Auth;

use Symfony\Component\Validator\Constraints as Assert;

final class RegisterRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'Company name is required.')]
        #[Assert\Length(min: 2, max: 200, minMessage: 'Company name must be at least 2 characters.')]
        public readonly string $companyName,

        #[Assert\NotBlank(message: 'First name is required.')]
        #[Assert\Length(min: 1, max: 100)]
        public readonly string $firstName,

        #[Assert\NotBlank(message: 'Last name is required.')]
        #[Assert\Length(min: 1, max: 100)]
        public readonly string $lastName,

        #[Assert\NotBlank(message: 'Email is required.')]
        #[Assert\Email(message: 'Invalid email format.')]
        public readonly string $email,

        #[Assert\NotBlank(message: 'Password is required.')]
        #[Assert\Length(min: 8, minMessage: 'Password must be at least 8 characters.')]
        #[Assert\Regex(
            pattern: '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
            message: 'Password must contain at least one uppercase letter, one lowercase letter, and one digit.'
        )]
        public readonly string $password,

        #[Assert\Length(max: 50)]
        public readonly ?string $phone = null,

        #[Assert\Choice(choices: ['private_security', 'state_police', 'neighborhood_watch', 'lg_security', 'nscdc'])]
        public readonly string $tenantType = 'private_security',
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            companyName: trim($data['company_name'] ?? ''),
            firstName: trim($data['first_name'] ?? ''),
            lastName: trim($data['last_name'] ?? ''),
            email: trim($data['email'] ?? ''),
            password: $data['password'] ?? '',
            phone: isset($data['phone']) ? trim($data['phone']) : null,
            tenantType: $data['tenant_type'] ?? 'private_security',
        );
    }
}
