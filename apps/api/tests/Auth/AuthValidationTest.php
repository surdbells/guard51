<?php

declare(strict_types=1);

namespace Guard51\Tests\Auth;

use Guard51\DTO\Auth\LoginRequest;
use Guard51\DTO\Auth\RegisterRequest;
use Guard51\DTO\Auth\ForgotPasswordRequest;
use Guard51\DTO\Auth\ResetPasswordRequest;
use Guard51\Exception\ApiException;
use Guard51\Service\ValidationService;
use PHPUnit\Framework\TestCase;

class AuthValidationTest extends TestCase
{
    private ValidationService $validator;

    protected function setUp(): void
    {
        $this->validator = new ValidationService();
    }

    // ── LoginRequest ─────────────────────────────────

    public function testValidLoginRequest(): void
    {
        $dto = LoginRequest::fromArray([
            'email' => 'test@example.com',
            'password' => 'MyPassword123',
        ]);

        // Should not throw
        $this->validator->validate($dto);
        $this->assertEquals('test@example.com', $dto->email);
    }

    public function testLoginRequestMissingEmail(): void
    {
        $dto = LoginRequest::fromArray([
            'email' => '',
            'password' => 'MyPassword123',
        ]);

        $this->expectException(ApiException::class);
        $this->validator->validate($dto);
    }

    public function testLoginRequestInvalidEmail(): void
    {
        $dto = LoginRequest::fromArray([
            'email' => 'not-an-email',
            'password' => 'MyPassword123',
        ]);

        $this->expectException(ApiException::class);
        $this->validator->validate($dto);
    }

    public function testLoginRequestMissingPassword(): void
    {
        $dto = LoginRequest::fromArray([
            'email' => 'test@example.com',
            'password' => '',
        ]);

        $this->expectException(ApiException::class);
        $this->validator->validate($dto);
    }

    // ── RegisterRequest ──────────────────────────────

    public function testValidRegisterRequest(): void
    {
        $dto = RegisterRequest::fromArray([
            'company_name' => 'ShieldForce Security',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@shield.com',
            'password' => 'Strong1Pass!',
            'phone' => '+2348012345678',
            'tenant_type' => 'private_security',
        ]);

        $this->validator->validate($dto);
        $this->assertEquals('ShieldForce Security', $dto->companyName);
        $this->assertEquals('private_security', $dto->tenantType);
    }

    public function testRegisterRequestWeakPassword(): void
    {
        $dto = RegisterRequest::fromArray([
            'company_name' => 'Test Co',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@test.com',
            'password' => 'weakpass', // no uppercase, no digit
        ]);

        $this->expectException(ApiException::class);
        $this->validator->validate($dto);
    }

    public function testRegisterRequestShortPassword(): void
    {
        $dto = RegisterRequest::fromArray([
            'company_name' => 'Test Co',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@test.com',
            'password' => 'Ab1', // too short
        ]);

        $this->expectException(ApiException::class);
        $this->validator->validate($dto);
    }

    public function testRegisterRequestGovTenantType(): void
    {
        $dto = RegisterRequest::fromArray([
            'company_name' => 'Ikeja Neighborhood Watch',
            'first_name' => 'Bola',
            'last_name' => 'Ade',
            'email' => 'bola@watch.com',
            'password' => 'Strong1Pass!',
            'tenant_type' => 'neighborhood_watch',
        ]);

        $this->validator->validate($dto);
        $this->assertEquals('neighborhood_watch', $dto->tenantType);
    }

    public function testRegisterRequestDefaultsToPrivateSecurity(): void
    {
        $dto = RegisterRequest::fromArray([
            'company_name' => 'Test Security',
            'first_name' => 'A',
            'last_name' => 'B',
            'email' => 'a@b.com',
            'password' => 'Strong1Pass!',
        ]);

        $this->assertEquals('private_security', $dto->tenantType);
    }

    // ── ForgotPasswordRequest ────────────────────────

    public function testValidForgotPasswordRequest(): void
    {
        $dto = ForgotPasswordRequest::fromArray(['email' => 'test@example.com']);
        $this->validator->validate($dto);
        $this->assertEquals('test@example.com', $dto->email);
    }

    public function testForgotPasswordInvalidEmail(): void
    {
        $dto = ForgotPasswordRequest::fromArray(['email' => 'bad']);
        $this->expectException(ApiException::class);
        $this->validator->validate($dto);
    }

    // ── ResetPasswordRequest ─────────────────────────

    public function testValidResetPasswordRequest(): void
    {
        $dto = ResetPasswordRequest::fromArray([
            'token' => 'abc123def456',
            'email' => 'test@example.com',
            'password' => 'NewStrong1Pass!',
        ]);

        $this->validator->validate($dto);
        $this->assertEquals('abc123def456', $dto->token);
    }

    public function testResetPasswordMissingToken(): void
    {
        $dto = ResetPasswordRequest::fromArray([
            'token' => '',
            'email' => 'test@example.com',
            'password' => 'NewStrong1Pass!',
        ]);

        $this->expectException(ApiException::class);
        $this->validator->validate($dto);
    }

    // ── Validation Error Format ──────────────────────

    public function testValidationErrorContainsFieldErrors(): void
    {
        $dto = LoginRequest::fromArray(['email' => '', 'password' => '']);

        try {
            $this->validator->validate($dto);
            $this->fail('Expected ApiException');
        } catch (ApiException $e) {
            $this->assertEquals(422, $e->getStatusCode());
            $this->assertEquals('Validation failed.', $e->getMessage());
            $errors = $e->getErrors();
            $this->assertArrayHasKey('email', $errors);
            $this->assertArrayHasKey('password', $errors);
        }
    }
}
