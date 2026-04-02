<?php

declare(strict_types=1);

namespace Guard51\Service;

use Guard51\Exception\ApiException;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ValidationService
{
    private ValidatorInterface $validator;

    public function __construct()
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    /**
     * Validate a DTO. Throws ApiException with 422 status if validation fails.
     *
     * @throws ApiException
     */
    public function validate(object $dto): void
    {
        $violations = $this->validator->validate($dto);

        if (count($violations) === 0) {
            return;
        }

        $errors = [];
        foreach ($violations as $violation) {
            $field = $violation->getPropertyPath();
            $errors[$field][] = $violation->getMessage();
        }

        throw ApiException::validation('Validation failed.', $errors);
    }

    public function validatePassword(string $password): array
    {
        $errors = [];
        if (strlen($password) < 10) $errors[] = 'Password must be at least 10 characters.';
        if (!preg_match('/[A-Z]/', $password)) $errors[] = 'Password must contain an uppercase letter.';
        if (!preg_match('/[0-9]/', $password)) $errors[] = 'Password must contain a number.';
        if (!preg_match('/[^A-Za-z0-9]/', $password)) $errors[] = 'Password must contain a special character.';
        return $errors;
    }
}
