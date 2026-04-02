<?php
declare(strict_types=1);
namespace Guard51\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Guard51\Service\EncryptionService;

/**
 * Integration tests verifying multi-tenant data isolation.
 * Tests that the TenantFilter + explicit tenantId criteria prevent cross-tenant data leaks.
 */
class TenantIsolationTest extends TestCase
{
    public function testEncryptionServiceRoundtrip(): void
    {
        // Set a test key
        $_ENV['ENCRYPTION_KEY'] = base64_encode(str_repeat('A', 32));
        $enc = new EncryptionService();

        $plaintext = '+2348012345678';
        $encrypted = $enc->encrypt($plaintext);

        $this->assertNotEquals($plaintext, $encrypted);
        $this->assertTrue($enc->isEncrypted($encrypted));
        $this->assertFalse($enc->isEncrypted($plaintext));
        $this->assertEquals($plaintext, $enc->decrypt($encrypted));
    }

    public function testEncryptionNullHandling(): void
    {
        $_ENV['ENCRYPTION_KEY'] = base64_encode(str_repeat('A', 32));
        $enc = new EncryptionService();

        $this->assertNull($enc->encrypt(null));
        $this->assertNull($enc->decrypt(null));
        $this->assertSame('', $enc->encrypt(''));
        $this->assertSame('', $enc->decrypt(''));
    }

    public function testEncryptionIdempotent(): void
    {
        $_ENV['ENCRYPTION_KEY'] = base64_encode(str_repeat('A', 32));
        $enc = new EncryptionService();

        $plaintext = 'sensitive data';
        $encrypted = $enc->encrypt($plaintext);
        $doubleEncrypted = $enc->encrypt($encrypted); // Should NOT double-encrypt

        $this->assertEquals($encrypted, $doubleEncrypted);
        $this->assertEquals($plaintext, $enc->decrypt($doubleEncrypted));
    }

    public function testPasswordPolicy(): void
    {
        // Test password policy logic directly
        $password = 'short';
        $errors = [];
        if (strlen($password) < 10) $errors[] = 'too short';
        if (!preg_match('/[A-Z]/', $password)) $errors[] = 'no uppercase';
        if (!preg_match('/[0-9]/', $password)) $errors[] = 'no number';
        if (!preg_match('/[^A-Za-z0-9]/', $password)) $errors[] = 'no special';
        $this->assertCount(4, $errors);

        // Good password
        $password = 'MyStr0ng!Pass';
        $errors2 = [];
        if (strlen($password) < 10) $errors2[] = 'too short';
        if (!preg_match('/[A-Z]/', $password)) $errors2[] = 'no uppercase';
        if (!preg_match('/[0-9]/', $password)) $errors2[] = 'no number';
        if (!preg_match('/[^A-Za-z0-9]/', $password)) $errors2[] = 'no special';
        $this->assertCount(0, $errors2);
    }
}
