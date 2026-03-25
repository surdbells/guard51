<?php

declare(strict_types=1);

namespace Guard51\Tests\Entity;

use Guard51\Entity\FeatureModule;
use Guard51\Entity\PaymentMethod;
use Guard51\Entity\Subscription;
use Guard51\Entity\SubscriptionInvoice;
use Guard51\Entity\SubscriptionPlan;
use Guard51\Entity\SubscriptionStatus;
use Guard51\Entity\SubscriptionTier;
use Guard51\Entity\TenantFeatureModule;
use Guard51\Entity\TenantType;
use Guard51\Entity\TenantUsageMetric;
use Guard51\Entity\InvoiceStatus;
use PHPUnit\Framework\TestCase;

class SubscriptionTest extends TestCase
{
    // ── SubscriptionTier ─────────────────────────────

    public function testTierSatisfies(): void
    {
        $this->assertTrue(SubscriptionTier::PROFESSIONAL->satisfies(SubscriptionTier::STARTER));
        $this->assertTrue(SubscriptionTier::ENTERPRISE->satisfies(SubscriptionTier::ALL));
        $this->assertTrue(SubscriptionTier::STARTER->satisfies(SubscriptionTier::ALL));
        $this->assertFalse(SubscriptionTier::STARTER->satisfies(SubscriptionTier::PROFESSIONAL));
    }

    public function testTierLevels(): void
    {
        $this->assertLessThan(SubscriptionTier::STARTER->level(), SubscriptionTier::ALL->level());
        $this->assertLessThan(SubscriptionTier::ENTERPRISE->level(), SubscriptionTier::BUSINESS->level());
    }

    // ── SubscriptionStatus ───────────────────────────

    public function testStatusOperational(): void
    {
        $this->assertTrue(SubscriptionStatus::ACTIVE->isOperational());
        $this->assertFalse(SubscriptionStatus::PENDING->isOperational());
        $this->assertFalse(SubscriptionStatus::CANCELLED->isOperational());
    }

    public function testStatusCanRenew(): void
    {
        $this->assertTrue(SubscriptionStatus::ACTIVE->canRenew());
        $this->assertTrue(SubscriptionStatus::EXPIRED->canRenew());
        $this->assertFalse(SubscriptionStatus::CANCELLED->canRenew());
    }

    // ── PaymentMethod ────────────────────────────────

    public function testPaymentMethodManualConfirmation(): void
    {
        $this->assertFalse(PaymentMethod::PAYSTACK->requiresManualConfirmation());
        $this->assertTrue(PaymentMethod::BANK_TRANSFER->requiresManualConfirmation());
    }

    // ── SubscriptionPlan ─────────────────────────────

    public function testPlanCreation(): void
    {
        $plan = new SubscriptionPlan();
        $plan->setName('Pro Plan')
            ->setTier(SubscriptionTier::PROFESSIONAL)
            ->setMonthlyPrice('75000.00')
            ->setAnnualPrice('750000.00')
            ->setMaxGuards(100)
            ->setMaxSites(20)
            ->setMaxClients(15)
            ->setIncludedModules(['guard_management', 'scheduling', 'payroll'])
            ->setTenantTypes(['private_security']);

        $this->assertEquals('Pro Plan', $plan->getName());
        $this->assertEquals(SubscriptionTier::PROFESSIONAL, $plan->getTier());
        $this->assertEquals('75000.00', $plan->getMonthlyPrice());
        $this->assertEquals(7500000, $plan->getMonthlyPriceKobo());
        $this->assertEquals(75000000, $plan->getAnnualPriceKobo());
        $this->assertTrue($plan->includesModule('payroll'));
        $this->assertFalse($plan->includesModule('white_label'));
        $this->assertFalse($plan->isUnlimitedGuards());
    }

    public function testPlanTenantTypeAvailability(): void
    {
        $plan = new SubscriptionPlan();
        $plan->setName('Test')
            ->setTier(SubscriptionTier::STARTER)
            ->setMonthlyPrice('25000.00')
            ->setTenantTypes(['private_security', 'neighborhood_watch']);

        $this->assertTrue($plan->isAvailableForTenantType(TenantType::PRIVATE_SECURITY));
        $this->assertTrue($plan->isAvailableForTenantType(TenantType::NEIGHBORHOOD_WATCH));
        $this->assertFalse($plan->isAvailableForTenantType(TenantType::STATE_POLICE));
    }

    public function testPlanPrivate(): void
    {
        $plan = new SubscriptionPlan();
        $plan->setName('Custom')
            ->setTier(SubscriptionTier::ENTERPRISE)
            ->setMonthlyPrice('0.00')
            ->setPrivateTenantId('tenant-xyz');

        $this->assertTrue($plan->isPrivatePlan());

        $plan->setPrivateTenantId(null);
        $this->assertFalse($plan->isPrivatePlan());
    }

    // ── Subscription ─────────────────────────────────

    public function testSubscriptionActivation(): void
    {
        $sub = new Subscription();
        $sub->setTenantId('t-1')
            ->setPlanId('p-1')
            ->setAmount('75000.00')
            ->setPaymentMethod(PaymentMethod::PAYSTACK);

        $this->assertEquals(SubscriptionStatus::PENDING, $sub->getStatus());

        $sub->activate();
        $this->assertEquals(SubscriptionStatus::ACTIVE, $sub->getStatus());
    }

    public function testSubscriptionBankTransferConfirmation(): void
    {
        $sub = new Subscription();
        $sub->setTenantId('t-1')
            ->setPlanId('p-1')
            ->setAmount('25000.00')
            ->setBillingCycle('monthly')
            ->setPaymentMethod(PaymentMethod::BANK_TRANSFER)
            ->setStatus(SubscriptionStatus::PENDING);

        $this->assertTrue($sub->isPendingBankTransfer());

        $sub->confirmBankTransferPayment('admin-user-id');

        $this->assertEquals(SubscriptionStatus::ACTIVE, $sub->getStatus());
        $this->assertNotNull($sub->getPaymentConfirmedAt());
        $this->assertEquals('admin-user-id', $sub->getPaymentConfirmedBy());
        $this->assertNotNull($sub->getCurrentPeriodStart());
        $this->assertNotNull($sub->getCurrentPeriodEnd());
        $this->assertFalse($sub->isPendingBankTransfer());
    }

    public function testSubscriptionCancellation(): void
    {
        $sub = new Subscription();
        $sub->setTenantId('t-1')
            ->setPlanId('p-1')
            ->setAmount('75000.00')
            ->setPaymentMethod(PaymentMethod::PAYSTACK);
        $sub->activate();

        $sub->cancel('Too expensive');

        $this->assertEquals(SubscriptionStatus::CANCELLED, $sub->getStatus());
        $this->assertEquals('Too expensive', $sub->getCancellationReason());
        $this->assertNotNull($sub->getCancelledAt());
    }

    // ── FeatureModule ────────────────────────────────

    public function testFeatureModuleCreation(): void
    {
        $module = new FeatureModule();
        $module->setModuleKey('vehicle_patrol')
            ->setName('Vehicle Patrol Management')
            ->setCategory('vehicle')
            ->setMinimumTier(SubscriptionTier::PROFESSIONAL)
            ->setIsCore(false)
            ->setDependencies(['guard_management', 'site_management'])
            ->setTenantTypes(['private_security', 'state_police']);

        $this->assertEquals('vehicle_patrol', $module->getModuleKey());
        $this->assertFalse($module->isCore());
        $this->assertCount(2, $module->getDependencies());
        $this->assertTrue($module->isAvailableForTenantType(TenantType::PRIVATE_SECURITY));
        $this->assertTrue($module->isAvailableForTenantType(TenantType::STATE_POLICE));
        $this->assertFalse($module->isAvailableForTenantType(TenantType::LG_SECURITY));
    }

    // ── TenantFeatureModule ──────────────────────────

    public function testTenantFeatureModuleEnableDisable(): void
    {
        $tfm = new TenantFeatureModule();
        $tfm->setTenantId('t-1')
            ->setModuleKey('payroll')
            ->enable('admin-user');

        $this->assertTrue($tfm->isEnabled());
        $this->assertEquals('admin-user', $tfm->getEnabledBy());
        $this->assertNotNull($tfm->getEnabledAt());

        $tfm->disable();
        $this->assertFalse($tfm->isEnabled());
    }

    // ── SubscriptionInvoice ──────────────────────────

    public function testInvoiceMarkPaid(): void
    {
        $invoice = new SubscriptionInvoice();
        $invoice->setTenantId('t-1')
            ->setSubscriptionId('s-1')
            ->setInvoiceNumber('INV-001')
            ->setAmount('75000.00')
            ->setPaymentMethod(PaymentMethod::PAYSTACK)
            ->setPeriodStart(new \DateTimeImmutable())
            ->setPeriodEnd(new \DateTimeImmutable('+1 month'))
            ->setDueDate(new \DateTimeImmutable('+7 days'));

        $this->assertEquals(InvoiceStatus::PENDING, $invoice->getStatus());

        $invoice->markPaid('admin-1');
        $this->assertEquals(InvoiceStatus::PAID, $invoice->getStatus());
        $this->assertNotNull($invoice->getPaidAt());
    }

    // ── TenantUsageMetric ────────────────────────────

    public function testUsageMetricTracking(): void
    {
        $usage = new TenantUsageMetric();
        $usage->setTenantId('t-1')
            ->setGuardsCount(10)
            ->setSitesCount(3)
            ->setClientsCount(2);

        $this->assertEquals(10, $usage->getGuardsCount());
        $this->assertFalse($usage->wouldExceedGuardLimit(25));
        $this->assertTrue($usage->wouldExceedGuardLimit(10));

        $usage->incrementGuards();
        $this->assertEquals(11, $usage->getGuardsCount());

        $usage->decrementGuards();
        $this->assertEquals(10, $usage->getGuardsCount());
    }

    public function testUsageLimitChecks(): void
    {
        $usage = new TenantUsageMetric();
        $usage->setTenantId('t-1')
            ->setGuardsCount(24)
            ->setSitesCount(5)
            ->setClientsCount(4);

        // Just under the Starter limit
        $this->assertFalse($usage->wouldExceedGuardLimit(25));
        $this->assertTrue($usage->wouldExceedSiteLimit(5));
        $this->assertFalse($usage->wouldExceedClientLimit(5));

        $usage->incrementGuards(); // now 25
        $this->assertTrue($usage->wouldExceedGuardLimit(25));
    }
}
