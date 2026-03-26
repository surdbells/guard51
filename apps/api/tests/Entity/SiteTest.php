<?php

declare(strict_types=1);

namespace Guard51\Tests\Entity;

use Guard51\Entity\GeofenceType;
use Guard51\Entity\PostOrder;
use Guard51\Entity\PostOrderCategory;
use Guard51\Entity\PostOrderPriority;
use Guard51\Entity\Site;
use Guard51\Entity\SiteStatus;
use PHPUnit\Framework\TestCase;

class SiteTest extends TestCase
{
    // ── SiteStatus ───────────────────────────────────

    public function testSiteStatusOperational(): void
    {
        $this->assertTrue(SiteStatus::ACTIVE->isOperational());
        $this->assertFalse(SiteStatus::INACTIVE->isOperational());
        $this->assertFalse(SiteStatus::SUSPENDED->isOperational());
    }

    // ── GeofenceType ─────────────────────────────────

    public function testGeofenceTypeLabels(): void
    {
        $this->assertEquals('Circle', GeofenceType::CIRCLE->label());
        $this->assertEquals('Polygon', GeofenceType::POLYGON->label());
    }

    // ── PostOrderPriority ────────────────────────────

    public function testPriorityLevels(): void
    {
        $this->assertGreaterThan(PostOrderPriority::HIGH->level(), PostOrderPriority::CRITICAL->level());
        $this->assertGreaterThan(PostOrderPriority::MEDIUM->level(), PostOrderPriority::HIGH->level());
        $this->assertGreaterThan(PostOrderPriority::LOW->level(), PostOrderPriority::MEDIUM->level());
    }

    // ── PostOrderCategory ────────────────────────────

    public function testPostOrderCategoryLabels(): void
    {
        $this->assertEquals('General', PostOrderCategory::GENERAL->label());
        $this->assertEquals('Access Control', PostOrderCategory::ACCESS_CONTROL->label());
        $this->assertEquals('Patrol', PostOrderCategory::PATROL->label());
    }

    // ── Site Entity ──────────────────────────────────

    public function testSiteCreation(): void
    {
        $site = new Site();
        $site->setTenantId('tenant-1')
            ->setName('Lekki Phase 1 Estate')
            ->setAddress('15 Admiralty Way, Lekki Phase 1')
            ->setCity('Lagos')
            ->setState('Lagos')
            ->setLatitude(6.4281)
            ->setLongitude(3.4219)
            ->setGeofenceRadius(150)
            ->setGeofenceType(GeofenceType::CIRCLE);

        $this->assertNotEmpty($site->getId());
        $this->assertEquals(36, strlen($site->getId()));
        $this->assertEquals('Lekki Phase 1 Estate', $site->getName());
        $this->assertEquals('tenant-1', $site->getTenantId());
        $this->assertEquals(6.4281, $site->getLatitude());
        $this->assertEquals(3.4219, $site->getLongitude());
        $this->assertEquals(150, $site->getGeofenceRadius());
        $this->assertEquals(GeofenceType::CIRCLE, $site->getGeofenceType());
        $this->assertTrue($site->isCircularGeofence());
        $this->assertTrue($site->hasCoordinates());
        $this->assertEquals(SiteStatus::ACTIVE, $site->getStatus());
    }

    public function testSiteWithoutCoordinates(): void
    {
        $site = new Site();
        $site->setTenantId('tenant-1')
            ->setName('Test Site');

        $this->assertFalse($site->hasCoordinates());
        $this->assertNull($site->getLatitude());
        $this->assertNull($site->getLongitude());
    }

    public function testSiteStatusTransitions(): void
    {
        $site = new Site();
        $site->setTenantId('tenant-1')
            ->setName('Test');

        $this->assertEquals(SiteStatus::ACTIVE, $site->getStatus());

        $site->suspend();
        $this->assertEquals(SiteStatus::SUSPENDED, $site->getStatus());

        $site->activate();
        $this->assertEquals(SiteStatus::ACTIVE, $site->getStatus());

        $site->deactivate();
        $this->assertEquals(SiteStatus::INACTIVE, $site->getStatus());
    }

    public function testSiteClientAssignment(): void
    {
        $site = new Site();
        $site->setTenantId('tenant-1')
            ->setName('Client Site')
            ->setClientId('client-abc');

        $this->assertEquals('client-abc', $site->getClientId());

        $site->setClientId(null);
        $this->assertNull($site->getClientId());
    }

    public function testSiteToArray(): void
    {
        $site = new Site();
        $site->setTenantId('tenant-1')
            ->setName('HQ Site')
            ->setCity('Lagos')
            ->setLatitude(6.5)
            ->setLongitude(3.4);

        $arr = $site->toArray();

        $this->assertIsArray($arr);
        $this->assertEquals('HQ Site', $arr['name']);
        $this->assertEquals('Lagos', $arr['city']);
        $this->assertEquals(6.5, $arr['latitude']);
        $this->assertEquals('active', $arr['status']);
        $this->assertEquals('circle', $arr['geofence_type']);
        $this->assertEquals(100, $arr['geofence_radius']);
    }

    public function testPolygonGeofence(): void
    {
        $site = new Site();
        $site->setTenantId('tenant-1')
            ->setName('Complex Site')
            ->setGeofenceType(GeofenceType::POLYGON)
            ->setGeofencePolygon('{"type":"Polygon","coordinates":[[[3.42,6.43],[3.43,6.43],[3.43,6.44],[3.42,6.44],[3.42,6.43]]]}');

        $this->assertFalse($site->isCircularGeofence());
        $this->assertEquals(GeofenceType::POLYGON, $site->getGeofenceType());
        $this->assertNotNull($site->getGeofencePolygon());
    }

    // ── PostOrder Entity ─────────────────────────────

    public function testPostOrderCreation(): void
    {
        $order = new PostOrder();
        $order->setTenantId('tenant-1')
            ->setSiteId('site-1')
            ->setTitle('Main Gate Access Control')
            ->setInstructions('Check all IDs. Log every visitor. No entry after 10pm without prior authorization.')
            ->setPriority(PostOrderPriority::HIGH)
            ->setCategory(PostOrderCategory::ACCESS_CONTROL)
            ->setCreatedBy('user-admin');

        $this->assertNotEmpty($order->getId());
        $this->assertEquals('Main Gate Access Control', $order->getTitle());
        $this->assertEquals(PostOrderPriority::HIGH, $order->getPriority());
        $this->assertEquals(PostOrderCategory::ACCESS_CONTROL, $order->getCategory());
        $this->assertTrue($order->isActive());
        $this->assertEquals(1, $order->getVersion());
        $this->assertTrue($order->isCurrentlyEffective());
    }

    public function testPostOrderVersioning(): void
    {
        $order = new PostOrder();
        $order->setTenantId('tenant-1')
            ->setSiteId('site-1')
            ->setTitle('Original Title')
            ->setInstructions('Original instructions')
            ->setCreatedBy('user-1');

        $this->assertEquals(1, $order->getVersion());

        $order->updateContent('Updated Title', 'Updated instructions', 'user-2');

        $this->assertEquals('Updated Title', $order->getTitle());
        $this->assertEquals('Updated instructions', $order->getInstructions());
        $this->assertEquals('user-2', $order->getLastUpdatedBy());
        $this->assertEquals(2, $order->getVersion());

        $order->updateContent('Third Version', 'More updates', 'user-3');
        $this->assertEquals(3, $order->getVersion());
    }

    public function testPostOrderExpiry(): void
    {
        $order = new PostOrder();
        $order->setTenantId('tenant-1')
            ->setSiteId('site-1')
            ->setTitle('Temporary Order')
            ->setInstructions('Active for limited time')
            ->setCreatedBy('user-1')
            ->setEffectiveTo(new \DateTimeImmutable('+7 days'));

        $this->assertFalse($order->isExpired());
        $this->assertTrue($order->isCurrentlyEffective());

        // Set to past date
        $order->setEffectiveTo(new \DateTimeImmutable('-1 day'));
        $this->assertTrue($order->isExpired());
        $this->assertFalse($order->isCurrentlyEffective());
    }

    public function testPostOrderNoExpiry(): void
    {
        $order = new PostOrder();
        $order->setTenantId('tenant-1')
            ->setSiteId('site-1')
            ->setTitle('Permanent Order')
            ->setInstructions('Always active')
            ->setCreatedBy('user-1');

        $this->assertNull($order->getEffectiveTo());
        $this->assertFalse($order->isExpired());
        $this->assertTrue($order->isCurrentlyEffective());
    }

    public function testPostOrderDeactivation(): void
    {
        $order = new PostOrder();
        $order->setTenantId('tenant-1')
            ->setSiteId('site-1')
            ->setTitle('Test')
            ->setInstructions('Test')
            ->setCreatedBy('user-1');

        $this->assertTrue($order->isActive());
        $this->assertTrue($order->isCurrentlyEffective());

        $order->setIsActive(false);
        $this->assertFalse($order->isActive());
        $this->assertFalse($order->isCurrentlyEffective());
    }

    public function testPostOrderToArray(): void
    {
        $order = new PostOrder();
        $order->setTenantId('tenant-1')
            ->setSiteId('site-1')
            ->setTitle('Guard Patrol Route')
            ->setInstructions('Patrol perimeter every 30 minutes.')
            ->setPriority(PostOrderPriority::MEDIUM)
            ->setCategory(PostOrderCategory::PATROL)
            ->setCreatedBy('user-admin');

        $arr = $order->toArray();

        $this->assertIsArray($arr);
        $this->assertEquals('Guard Patrol Route', $arr['title']);
        $this->assertEquals('medium', $arr['priority']);
        $this->assertEquals('Medium', $arr['priority_label']);
        $this->assertEquals('patrol', $arr['category']);
        $this->assertEquals('Patrol', $arr['category_label']);
        $this->assertTrue($arr['is_active']);
        $this->assertTrue($arr['is_currently_effective']);
        $this->assertEquals(1, $arr['version']);
    }
}
