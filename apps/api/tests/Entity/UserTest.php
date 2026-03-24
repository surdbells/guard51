<?php

declare(strict_types=1);

namespace Guard51\Tests\Entity;

use Guard51\Entity\User;
use Guard51\Entity\UserRole;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    private function makeUser(): User
    {
        $user = new User();
        $user->setEmail('test@example.com')
            ->setPassword('SecurePass123!')
            ->setFirstName('John')
            ->setLastName('Doe')
            ->setPhone('+2348012345678')
            ->setRole(UserRole::GUARD)
            ->setTenantId('tenant-123');
        return $user;
    }

    public function testCreateUserWithDefaults(): void
    {
        $user = $this->makeUser();

        $this->assertNotEmpty($user->getId());
        $this->assertEquals(36, strlen($user->getId()));
        $this->assertEquals('test@example.com', $user->getEmail());
        $this->assertEquals('John', $user->getFirstName());
        $this->assertEquals('Doe', $user->getLastName());
        $this->assertEquals('John Doe', $user->getFullName());
        $this->assertEquals(UserRole::GUARD, $user->getRole());
        $this->assertEquals('tenant-123', $user->getTenantId());
        $this->assertTrue($user->isActive());
        $this->assertFalse($user->isEmailVerified());
        $this->assertFalse($user->isSuperAdmin());
    }

    public function testEmailNormalization(): void
    {
        $user = new User();
        $user->setEmail('  Test@Example.COM  ');

        $this->assertEquals('test@example.com', $user->getEmail());
    }

    public function testPasswordHashingAndVerification(): void
    {
        $user = $this->makeUser();

        $this->assertTrue($user->verifyPassword('SecurePass123!'));
        $this->assertFalse($user->verifyPassword('WrongPassword'));
        $this->assertNotEquals('SecurePass123!', $user->getPasswordHash());
        $this->assertStringStartsWith('$argon2id$', $user->getPasswordHash());
    }

    public function testRecordLogin(): void
    {
        $user = $this->makeUser();
        $user->recordLogin('192.168.1.1');

        $this->assertNotNull($user->getLastLoginAt());
        $this->assertEquals('192.168.1.1', $user->getLastLoginIp());
        $this->assertEquals(0, $user->getFailedLoginAttempts());
    }

    public function testAccountLocking(): void
    {
        $user = $this->makeUser();

        $this->assertFalse($user->isLocked());

        // 4 failed attempts — not locked yet
        for ($i = 0; $i < 4; $i++) {
            $user->recordFailedLogin();
        }
        $this->assertFalse($user->isLocked());
        $this->assertEquals(4, $user->getFailedLoginAttempts());

        // 5th failure — now locked
        $user->recordFailedLogin();
        $this->assertTrue($user->isLocked());
        $this->assertEquals(5, $user->getFailedLoginAttempts());

        // Successful login clears lock
        $user->recordLogin('10.0.0.1');
        $this->assertFalse($user->isLocked());
        $this->assertEquals(0, $user->getFailedLoginAttempts());
    }

    public function testEmailVerification(): void
    {
        $user = $this->makeUser();

        $this->assertFalse($user->isEmailVerified());
        $user->verifyEmail();
        $this->assertTrue($user->isEmailVerified());
    }

    public function testPasswordResetToken(): void
    {
        $user = $this->makeUser();

        $rawToken = $user->generatePasswordResetToken();

        $this->assertNotEmpty($rawToken);
        $this->assertTrue($user->isPasswordResetTokenValid($rawToken));
        $this->assertFalse($user->isPasswordResetTokenValid('wrong-token'));

        $user->clearPasswordResetToken();
        $this->assertFalse($user->isPasswordResetTokenValid($rawToken));
    }

    public function testSuperAdminHasNoTenant(): void
    {
        $user = new User();
        $user->setEmail('admin@guard51.com')
            ->setPassword('test')
            ->setFirstName('Super')
            ->setLastName('Admin')
            ->setRole(UserRole::SUPER_ADMIN);

        $this->assertTrue($user->isSuperAdmin());
        $this->assertNull($user->getTenantId());
    }

    public function testToArray(): void
    {
        $user = $this->makeUser();
        $array = $user->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('test@example.com', $array['email']);
        $this->assertEquals('John', $array['first_name']);
        $this->assertEquals('Doe', $array['last_name']);
        $this->assertEquals('John Doe', $array['full_name']);
        $this->assertEquals('guard', $array['role']);
        $this->assertEquals('tenant-123', $array['tenant_id']);
        $this->assertTrue($array['is_active']);
        // Password hash must NOT be in array
        $this->assertArrayNotHasKey('password_hash', $array);
        $this->assertArrayNotHasKey('passwordHash', $array);
    }
}
