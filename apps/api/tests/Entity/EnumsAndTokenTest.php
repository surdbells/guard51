<?php

declare(strict_types=1);

namespace Guard51\Tests\Entity;

use Guard51\Entity\RefreshToken;
use Guard51\Entity\TenantType;
use Guard51\Entity\TenantStatus;
use Guard51\Entity\UserRole;
use PHPUnit\Framework\TestCase;

class EnumsAndTokenTest extends TestCase
{
    // ── TenantType ───────────────────────────────────

    public function testTenantTypeGovernmentDetection(): void
    {
        $this->assertFalse(TenantType::PRIVATE_SECURITY->isGovernment());
        $this->assertTrue(TenantType::STATE_POLICE->isGovernment());
        $this->assertTrue(TenantType::NEIGHBORHOOD_WATCH->isGovernment());
        $this->assertTrue(TenantType::LG_SECURITY->isGovernment());
        $this->assertTrue(TenantType::NSCDC->isGovernment());
    }

    public function testTenantTypeGovernmentList(): void
    {
        $govTypes = TenantType::governmentTypes();
        $this->assertCount(4, $govTypes);
        $this->assertNotContains(TenantType::PRIVATE_SECURITY, $govTypes);
    }

    public function testTenantTypeLabels(): void
    {
        $this->assertEquals('Private Security Company', TenantType::PRIVATE_SECURITY->label());
        $this->assertEquals('State Police', TenantType::STATE_POLICE->label());
    }

    // ── TenantStatus ─────────────────────────────────

    public function testTenantStatusOperational(): void
    {
        $this->assertTrue(TenantStatus::ACTIVE->isOperational());
        $this->assertTrue(TenantStatus::TRIAL->isOperational());
        $this->assertFalse(TenantStatus::SUSPENDED->isOperational());
        $this->assertFalse(TenantStatus::CANCELLED->isOperational());
    }

    // ── UserRole ─────────────────────────────────────

    public function testUserRoleLevels(): void
    {
        $this->assertLessThan(UserRole::COMPANY_ADMIN->level(), UserRole::SUPER_ADMIN->level());
        $this->assertLessThan(UserRole::GUARD->level(), UserRole::SUPERVISOR->level());
    }

    public function testUserRoleTenantScoping(): void
    {
        $this->assertFalse(UserRole::SUPER_ADMIN->isTenantScoped());
        $this->assertFalse(UserRole::CITIZEN->isTenantScoped());
        $this->assertTrue(UserRole::COMPANY_ADMIN->isTenantScoped());
        $this->assertTrue(UserRole::GUARD->isTenantScoped());
    }

    public function testBackOfficeRoles(): void
    {
        $roles = UserRole::backOfficeRoles();
        $this->assertContains(UserRole::SUPER_ADMIN, $roles);
        $this->assertContains(UserRole::COMPANY_ADMIN, $roles);
        $this->assertNotContains(UserRole::GUARD, $roles);
        $this->assertNotContains(UserRole::CLIENT, $roles);
    }

    // ── RefreshToken ─────────────────────────────────

    public function testRefreshTokenGeneration(): void
    {
        $result = RefreshToken::generate('user-123', 604800, 'Mozilla/5.0', '127.0.0.1');

        $this->assertArrayHasKey('entity', $result);
        $this->assertArrayHasKey('raw_token', $result);

        $entity = $result['entity'];
        $rawToken = $result['raw_token'];

        $this->assertInstanceOf(RefreshToken::class, $entity);
        $this->assertEquals('user-123', $entity->getUserId());
        $this->assertTrue($entity->matchesToken($rawToken));
        $this->assertFalse($entity->matchesToken('wrong-token'));
        $this->assertFalse($entity->isRevoked());
        $this->assertTrue($entity->isValid());
        $this->assertEquals('Mozilla/5.0', $entity->getUserAgent());
        $this->assertEquals('127.0.0.1', $entity->getIpAddress());
    }

    public function testRefreshTokenRevoke(): void
    {
        $result = RefreshToken::generate('user-123', 604800);
        $entity = $result['entity'];

        $this->assertTrue($entity->isValid());

        $entity->revoke();

        $this->assertTrue($entity->isRevoked());
        $this->assertFalse($entity->isValid());
    }

    public function testRefreshTokenHashStorage(): void
    {
        $result = RefreshToken::generate('user-123', 604800);
        $entity = $result['entity'];
        $rawToken = $result['raw_token'];

        // Token hash should NOT equal the raw token
        $this->assertNotEquals($rawToken, $entity->getTokenHash());
        // Hash should be SHA-256 (64 chars hex)
        $this->assertEquals(64, strlen($entity->getTokenHash()));
    }
}
