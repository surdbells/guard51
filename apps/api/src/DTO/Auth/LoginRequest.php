<?php

declare(strict_types=1);

namespace Guard51\DTO\Auth;

use Symfony\Component\Validator\Constraints as Assert;

final class LoginRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'Email is required.')]
        #[Assert\Email(message: 'Invalid email format.')]
        public readonly string $email,

        #[Assert\NotBlank(message: 'Password is required.')]
        public readonly string $password,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            email: trim($data['email'] ?? ''),
            password: $data['password'] ?? '',
        );
    }
}
