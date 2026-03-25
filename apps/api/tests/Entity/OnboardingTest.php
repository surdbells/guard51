<?php

declare(strict_types=1);

namespace Guard51\Tests\Entity;

use Guard51\Entity\InvitationStatus;
use Guard51\Entity\OnboardingStep;
use Guard51\Entity\TenantInvitation;
use Guard51\Entity\UserRole;
use PHPUnit\Framework\TestCase;

class OnboardingTest extends TestCase
{
    // ── InvitationStatus ─────────────────────────────

    public function testInvitationStatusLabels(): void
    {
        $this->assertEquals('Pending', InvitationStatus::PENDING->label());
        $this->assertEquals('Accepted', InvitationStatus::ACCEPTED->label());
        $this->assertEquals('Expired', InvitationStatus::EXPIRED->label());
        $this->assertEquals('Revoked', InvitationStatus::REVOKED->label());
    }

    // ── OnboardingStep ───────────────────────────────

    public function testOnboardingStepNumbers(): void
    {
        $this->assertEquals(1, OnboardingStep::COMPANY_INFO->stepNumber());
        $this->assertEquals(3, OnboardingStep::SELECT_PLAN->stepNumber());
        $this->assertEquals(7, OnboardingStep::INVITE_GUARDS->stepNumber());
    }

    public function testPostRegistrationSteps(): void
    {
        $steps = OnboardingStep::postRegistrationSteps();
        $this->assertCount(5, $steps);
        $this->assertContains(OnboardingStep::SELECT_PLAN, $steps);
        $this->assertNotContains(OnboardingStep::COMPANY_INFO, $steps);
        $this->assertNotContains(OnboardingStep::ADMIN_ACCOUNT, $steps);
    }

    public function testOnboardingStepLabels(): void
    {
        $this->assertEquals('Company Information', OnboardingStep::COMPANY_INFO->label());
        $this->assertEquals('Bank Account Setup', OnboardingStep::BANK_ACCOUNT->label());
    }

    // ── TenantInvitation ─────────────────────────────

    public function testCreateInvitation(): void
    {
        $result = TenantInvitation::create(
            tenantId: 'tenant-123',
            email: 'guard@example.com',
            role: UserRole::GUARD,
            invitedBy: 'admin-456',
            firstName: 'Musa',
            lastName: 'Ibrahim',
        );

        $invitation = $result['entity'];
        $rawToken = $result['raw_token'];

        $this->assertInstanceOf(TenantInvitation::class, $invitation);
        $this->assertNotEmpty($rawToken);
        $this->assertEquals('guard@example.com', $invitation->getEmail());
        $this->assertEquals(UserRole::GUARD, $invitation->getRole());
        $this->assertEquals('tenant-123', $invitation->getTenantId());
        $this->assertEquals('admin-456', $invitation->getInvitedBy());
        $this->assertEquals('Musa', $invitation->getFirstName());
        $this->assertEquals(InvitationStatus::PENDING, $invitation->getStatus());
        $this->assertTrue($invitation->isPending());
        $this->assertTrue($invitation->canAccept());
    }

    public function testInvitationTokenVerification(): void
    {
        $result = TenantInvitation::create(
            tenantId: 'tenant-123',
            email: 'test@example.com',
            role: UserRole::SUPERVISOR,
            invitedBy: 'admin-456',
        );

        $invitation = $result['entity'];
        $rawToken = $result['raw_token'];

        $this->assertTrue($invitation->matchesToken($rawToken));
        $this->assertFalse($invitation->matchesToken('wrong-token'));
    }

    public function testInvitationAccept(): void
    {
        $result = TenantInvitation::create(
            tenantId: 'tenant-123',
            email: 'test@example.com',
            role: UserRole::GUARD,
            invitedBy: 'admin-456',
        );

        $invitation = $result['entity'];
        $invitation->accept('new-user-789');

        $this->assertEquals(InvitationStatus::ACCEPTED, $invitation->getStatus());
        $this->assertNotNull($invitation->getAcceptedAt());
        $this->assertEquals('new-user-789', $invitation->getAcceptedUserId());
        $this->assertFalse($invitation->isPending());
        $this->assertFalse($invitation->canAccept());
    }

    public function testInvitationRevoke(): void
    {
        $result = TenantInvitation::create(
            tenantId: 'tenant-123',
            email: 'test@example.com',
            role: UserRole::GUARD,
            invitedBy: 'admin-456',
        );

        $invitation = $result['entity'];
        $invitation->revoke();

        $this->assertEquals(InvitationStatus::REVOKED, $invitation->getStatus());
        $this->assertFalse($invitation->isPending());
        $this->assertFalse($invitation->canAccept());
    }

    public function testInvitationResendCount(): void
    {
        $result = TenantInvitation::create(
            tenantId: 'tenant-123',
            email: 'test@example.com',
            role: UserRole::GUARD,
            invitedBy: 'admin-456',
        );

        $invitation = $result['entity'];
        $this->assertEquals(0, $invitation->getResendCount());

        $invitation->recordResend();
        $this->assertEquals(1, $invitation->getResendCount());

        $invitation->recordResend();
        $this->assertEquals(2, $invitation->getResendCount());
    }

    public function testInvitationEmailNormalization(): void
    {
        $result = TenantInvitation::create(
            tenantId: 'tenant-123',
            email: '  Guard@Example.COM  ',
            role: UserRole::GUARD,
            invitedBy: 'admin-456',
        );

        $this->assertEquals('guard@example.com', $result['entity']->getEmail());
    }

    public function testInvitationToArray(): void
    {
        $result = TenantInvitation::create(
            tenantId: 'tenant-123',
            email: 'test@example.com',
            role: UserRole::DISPATCHER,
            invitedBy: 'admin-456',
            personalMessage: 'Welcome to the team!',
        );

        $array = $result['entity']->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('test@example.com', $array['email']);
        $this->assertEquals('dispatcher', $array['role']);
        $this->assertEquals('pending', $array['status']);
        $this->assertEquals('Welcome to the team!', $array['personal_message']);
        $this->assertFalse($array['is_expired']);
        // Token hash must NOT be in the array
        $this->assertArrayNotHasKey('token_hash', $array);
    }

    public function testInvitationWithAllRoles(): void
    {
        $validRoles = [UserRole::GUARD, UserRole::SUPERVISOR, UserRole::DISPATCHER, UserRole::COMPANY_ADMIN, UserRole::CLIENT];

        foreach ($validRoles as $role) {
            $result = TenantInvitation::create(
                tenantId: 'tenant-123',
                email: "test-{$role->value}@example.com",
                role: $role,
                invitedBy: 'admin-456',
            );
            $this->assertEquals($role, $result['entity']->getRole());
        }
    }
}
