<?php

declare(strict_types=1);

namespace Guard51\Tests\Service;

use Guard51\Entity\User;
use Guard51\Entity\UserRole;
use Guard51\Service\JwtService;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class JwtServiceTest extends TestCase
{
    private JwtService $jwt;

    protected function setUp(): void
    {
        $this->jwt = new JwtService([
            'secret' => 'test_secret_key_for_unit_tests_only_1234567890',
            'access_ttl' => 900,
            'refresh_ttl' => 604800,
            'algorithm' => 'HS256',
            'issuer' => 'guard51-test',
        ], new NullLogger());
    }

    private function makeUser(): User
    {
        $user = new User();
        $user->setEmail('test@example.com')
            ->setPassword('Test1234!')
            ->setFirstName('John')
            ->setLastName('Doe')
            ->setRole(UserRole::COMPANY_ADMIN)
            ->setTenantId('tenant-abc-123');
        return $user;
    }

    public function testGenerateAccessToken(): void
    {
        $user = $this->makeUser();
        $token = $this->jwt->generateAccessToken($user);

        $this->assertNotEmpty($token);
        $this->assertIsString($token);
        // JWT has 3 parts separated by dots
        $this->assertCount(3, explode('.', $token));
    }

    public function testValidateAccessToken(): void
    {
        $user = $this->makeUser();
        $token = $this->jwt->generateAccessToken($user);

        $payload = $this->jwt->validateAccessToken($token);

        $this->assertNotNull($payload);
        $this->assertEquals($user->getId(), $payload['sub']);
        $this->assertEquals('tenant-abc-123', $payload['tenant_id']);
        $this->assertEquals('company_admin', $payload['role']);
        $this->assertEquals('test@example.com', $payload['email']);
        $this->assertEquals('access', $payload['type']);
        $this->assertEquals('guard51-test', $payload['iss']);
    }

    public function testInvalidTokenReturnsNull(): void
    {
        $payload = $this->jwt->validateAccessToken('completely.invalid.token');
        $this->assertNull($payload);
    }

    public function testTamperedTokenReturnsNull(): void
    {
        $user = $this->makeUser();
        $token = $this->jwt->generateAccessToken($user);

        // Tamper with the signature
        $parts = explode('.', $token);
        $parts[2] = 'tampered_signature';
        $tamperedToken = implode('.', $parts);

        $payload = $this->jwt->validateAccessToken($tamperedToken);
        $this->assertNull($payload);
    }

    public function testExpiredTokenReturnsNull(): void
    {
        // Create a JWT service with 0 second TTL
        $expiredJwt = new JwtService([
            'secret' => 'test_secret_key_for_unit_tests_only_1234567890',
            'access_ttl' => -1, // Already expired
            'refresh_ttl' => 604800,
            'algorithm' => 'HS256',
            'issuer' => 'guard51-test',
        ], new NullLogger());

        $user = $this->makeUser();
        $token = $expiredJwt->generateAccessToken($user);

        $payload = $expiredJwt->validateAccessToken($token);
        $this->assertNull($payload);
    }

    public function testDifferentSecretRejectsToken(): void
    {
        $user = $this->makeUser();
        $token = $this->jwt->generateAccessToken($user);

        $otherJwt = new JwtService([
            'secret' => 'different_secret_key_completely',
            'access_ttl' => 900,
            'refresh_ttl' => 604800,
            'algorithm' => 'HS256',
            'issuer' => 'guard51-test',
        ], new NullLogger());

        $payload = $otherJwt->validateAccessToken($token);
        $this->assertNull($payload);
    }

    public function testSuperAdminTokenHasNullTenant(): void
    {
        $user = new User();
        $user->setEmail('admin@guard51.com')
            ->setPassword('Test1234!')
            ->setFirstName('Super')
            ->setLastName('Admin')
            ->setRole(UserRole::SUPER_ADMIN);

        $token = $this->jwt->generateAccessToken($user);
        $payload = $this->jwt->validateAccessToken($token);

        $this->assertNotNull($payload);
        $this->assertNull($payload['tenant_id']);
        $this->assertEquals('super_admin', $payload['role']);
    }

    public function testExtractFromHeader(): void
    {
        $this->assertEquals('mytoken', JwtService::extractFromHeader('Bearer mytoken'));
        $this->assertNull(JwtService::extractFromHeader('Bearer '));
        $this->assertNull(JwtService::extractFromHeader('Basic mytoken'));
        $this->assertNull(JwtService::extractFromHeader(''));
    }

    public function testGetTtlValues(): void
    {
        $this->assertEquals(900, $this->jwt->getAccessTtl());
        $this->assertEquals(604800, $this->jwt->getRefreshTtl());
    }
}
