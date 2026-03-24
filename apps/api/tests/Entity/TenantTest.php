<?php

declare(strict_types=1);

namespace Guard51\Tests\Entity;

use Guard51\Entity\Tenant;
use Guard51\Entity\TenantStatus;
use Guard51\Entity\TenantType;
use PHPUnit\Framework\TestCase;

class TenantTest extends TestCase
{
    public function testCreateTenantWithDefaults(): void
    {
        $tenant = new Tenant();
        $tenant->setName('Test Security Co');

        $this->assertNotEmpty($tenant->getId());
        $this->assertEquals(36, strlen($tenant->getId()));
        $this->assertEquals('Test Security Co', $tenant->getName());
        $this->assertEquals(TenantType::PRIVATE_SECURITY, $tenant->getTenantType());
        $this->assertEquals(TenantStatus::ACTIVE, $tenant->getStatus());
        $this->assertEquals('NGN', $tenant->getCurrency());
        $this->assertEquals('Africa/Lagos', $tenant->getTimezone());
        $this->assertFalse($tenant->isOnboarded());
        $this->assertFalse($tenant->isGovernment());
        $this->assertTrue($tenant->isPrivateSecurity());
    }

    public function testGovernmentTenantType(): void
    {
        $tenant = new Tenant();
        $tenant->setName('Lagos State Police')
            ->setTenantType(TenantType::STATE_POLICE)
            ->setOrgSubtype('state_command');

        $this->assertTrue($tenant->isGovernment());
        $this->assertFalse($tenant->isPrivateSecurity());
        $this->assertEquals('state_command', $tenant->getOrgSubtype());
    }

    public function testMarkOnboarded(): void
    {
        $tenant = new Tenant();
        $tenant->setName('Test Co');

        $this->assertFalse($tenant->isOnboarded());
        $this->assertNull($tenant->getOnboardedAt());

        $tenant->markOnboarded();

        $this->assertTrue($tenant->isOnboarded());
        $this->assertNotNull($tenant->getOnboardedAt());
    }

    public function testSuspendAndReactivate(): void
    {
        $tenant = new Tenant();
        $tenant->setName('Test Co');

        $tenant->suspend('Non-payment');

        $this->assertEquals(TenantStatus::SUSPENDED, $tenant->getStatus());
        $this->assertNotNull($tenant->getSuspendedAt());
        $this->assertEquals('Non-payment', $tenant->getSuspensionReason());

        $tenant->reactivate();

        $this->assertEquals(TenantStatus::ACTIVE, $tenant->getStatus());
        $this->assertNull($tenant->getSuspendedAt());
        $this->assertNull($tenant->getSuspensionReason());
    }

    public function testToArray(): void
    {
        $tenant = new Tenant();
        $tenant->setName('Shield Security')
            ->setEmail('info@shield.com')
            ->setCity('Lagos');

        $array = $tenant->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('Shield Security', $array['name']);
        $this->assertEquals('info@shield.com', $array['email']);
        $this->assertEquals('Lagos', $array['city']);
        $this->assertEquals('private_security', $array['tenant_type']);
        $this->assertEquals('active', $array['status']);
    }

    public function testBranding(): void
    {
        $tenant = new Tenant();
        $tenant->setName('Test Co')
            ->setBranding(['primary_color' => '#FF0000', 'secondary_color' => '#00FF00']);

        $this->assertEquals('#FF0000', $tenant->getBranding()['primary_color']);
        $this->assertEquals('#00FF00', $tenant->getBranding()['secondary_color']);
    }
}
