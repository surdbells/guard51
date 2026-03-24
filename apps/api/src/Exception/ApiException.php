<?php

declare(strict_types=1);

namespace Guard51\Exception;

use RuntimeException;

class ApiException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly int $statusCode = 500,
        private readonly array $errors = [],
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function toArray(): array
    {
        return [
            'error' => true,
            'message' => $this->getMessage(),
            'status' => $this->statusCode,
            'errors' => $this->errors,
        ];
    }

    public static function notFound(string $message = 'Resource not found'): self
    {
        return new self($message, 404);
    }

    public static function unauthorized(string $message = 'Unauthorized'): self
    {
        return new self($message, 401);
    }

    public static function forbidden(string $message = 'Forbidden'): self
    {
        return new self($message, 403);
    }

    public static function validation(string $message = 'Validation failed', array $errors = []): self
    {
        return new self($message, 422, $errors);
    }

    public static function conflict(string $message = 'Conflict'): self
    {
        return new self($message, 409);
    }

    public static function tooManyRequests(string $message = 'Too many requests'): self
    {
        return new self($message, 429);
    }
}
