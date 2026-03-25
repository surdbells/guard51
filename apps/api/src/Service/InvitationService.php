<?php

declare(strict_types=1);

namespace Guard51\Service;

use Guard51\Entity\AuditLog;
use Guard51\Entity\TenantInvitation;
use Guard51\Entity\User;
use Guard51\Entity\UserRole;
use Guard51\Exception\ApiException;
use Guard51\Repository\TenantInvitationRepository;
use Guard51\Repository\TenantRepository;
use Guard51\Repository\UserRepository;
use Psr\Log\LoggerInterface;

final class InvitationService
{
    public function __construct(
        private readonly TenantInvitationRepository $invitationRepo,
        private readonly UserRepository $userRepo,
        private readonly TenantRepository $tenantRepo,
        private readonly ZeptoMailService $mailer,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Create and send a staff invitation.
     */
    public function invite(
        string $tenantId,
        string $email,
        UserRole $role,
        string $invitedByUserId,
        ?string $firstName = null,
        ?string $lastName = null,
        ?string $personalMessage = null,
    ): TenantInvitation {
        // Validate role — can't invite super_admin or citizen
        if (!$role->isTenantScoped()) {
            throw ApiException::validation('Cannot invite users with this role.');
        }

        // Check if email already has an account in this tenant
        $existingUser = $this->userRepo->findByEmail($email);
        if ($existingUser && $existingUser->getTenantId() === $tenantId) {
            throw ApiException::conflict('A user with this email already exists in your organization.');
        }

        // Check for existing pending invitation
        $existingInvite = $this->invitationRepo->findPendingByEmail($tenantId, $email);
        if ($existingInvite) {
            throw ApiException::conflict('A pending invitation already exists for this email. You can resend it instead.');
        }

        // Create invitation
        $result = TenantInvitation::create(
            tenantId: $tenantId,
            email: $email,
            role: $role,
            invitedBy: $invitedByUserId,
            firstName: $firstName,
            lastName: $lastName,
            personalMessage: $personalMessage,
        );

        $invitation = $result['entity'];
        $rawToken = $result['raw_token'];

        $this->invitationRepo->save($invitation);

        // Send invitation email
        $tenant = $this->tenantRepo->find($tenantId);
        $this->sendInvitationEmail($invitation, $rawToken, $tenant?->getName() ?? 'Guard51');

        $this->logger->info('Staff invitation sent.', [
            'tenant_id' => $tenantId,
            'email' => $email,
            'role' => $role->value,
        ]);

        return $invitation;
    }

    /**
     * Accept an invitation — create user account.
     */
    public function accept(
        string $token,
        string $email,
        string $password,
        ?string $firstName = null,
        ?string $lastName = null,
        ?string $phone = null,
    ): User {
        // Find matching invitation
        $invitation = $this->invitationRepo->findByEmail($email);
        $matched = null;

        if ($invitation) {
            foreach ($invitation as $inv) {
                if ($inv->matchesToken($token) && $inv->canAccept()) {
                    $matched = $inv;
                    break;
                }
            }
        }

        if (!$matched) {
            throw ApiException::validation('Invalid or expired invitation token.');
        }

        // Check email not already registered
        if ($this->userRepo->emailExists($email)) {
            throw ApiException::conflict('An account with this email already exists.');
        }

        // Create user
        $user = new User();
        $user->setEmail($email)
            ->setPassword($password)
            ->setFirstName($firstName ?? $matched->getFirstName() ?? '')
            ->setLastName($lastName ?? $matched->getLastName() ?? '')
            ->setPhone($phone)
            ->setRole($matched->getRole())
            ->setTenantId($matched->getTenantId())
            ->setIsActive(true);
        $user->verifyEmail(); // Email verified by accepting invitation

        $this->userRepo->save($user);

        // Mark invitation accepted
        $matched->accept($user->getId());
        $this->invitationRepo->save($matched);

        $this->logger->info('Invitation accepted.', [
            'invitation_id' => $matched->getId(),
            'user_id' => $user->getId(),
            'email' => $email,
        ]);

        return $user;
    }

    /**
     * Resend an existing pending invitation.
     */
    public function resend(string $invitationId, string $tenantId): TenantInvitation
    {
        $invitation = $this->invitationRepo->findOrFail($invitationId);

        if ($invitation->getTenantId() !== $tenantId) {
            throw ApiException::forbidden('This invitation does not belong to your organization.');
        }

        if (!$invitation->isPending()) {
            throw ApiException::validation('Only pending invitations can be resent.');
        }

        if ($invitation->getResendCount() >= 5) {
            throw ApiException::tooManyRequests('This invitation has been resent too many times.');
        }

        // Generate new token for security
        $rawToken = bin2hex(random_bytes(32));
        $invitation->setToken($rawToken);
        $invitation->setExpiresAt(new \DateTimeImmutable('+7 days'));
        $invitation->recordResend();

        $this->invitationRepo->save($invitation);

        $tenant = $this->tenantRepo->find($tenantId);
        $this->sendInvitationEmail($invitation, $rawToken, $tenant?->getName() ?? 'Guard51');

        return $invitation;
    }

    /**
     * Revoke a pending invitation.
     */
    public function revoke(string $invitationId, string $tenantId): TenantInvitation
    {
        $invitation = $this->invitationRepo->findOrFail($invitationId);

        if ($invitation->getTenantId() !== $tenantId) {
            throw ApiException::forbidden('This invitation does not belong to your organization.');
        }

        if ($invitation->getStatus()->value !== 'pending') {
            throw ApiException::validation('Only pending invitations can be revoked.');
        }

        $invitation->revoke();
        $this->invitationRepo->save($invitation);

        $this->logger->info('Invitation revoked.', ['invitation_id' => $invitationId]);
        return $invitation;
    }

    /**
     * List invitations for a tenant with optional status filter.
     */
    public function listForTenant(string $tenantId, ?string $status = null): array
    {
        if ($status) {
            return $this->invitationRepo->findBy(
                ['tenantId' => $tenantId, 'status' => $status],
                ['createdAt' => 'DESC']
            );
        }
        return $this->invitationRepo->findBy(
            ['tenantId' => $tenantId],
            ['createdAt' => 'DESC']
        );
    }

    private function sendInvitationEmail(TenantInvitation $invitation, string $rawToken, string $tenantName): void
    {
        $acceptUrl = ($_ENV['APP_URL'] ?? 'http://localhost:4200')
            . '/auth/accept-invitation?token=' . urlencode($rawToken)
            . '&email=' . urlencode($invitation->getEmail());

        $roleName = $invitation->getRole()->label();
        $name = $invitation->getFirstName() ?? 'there';
        $message = $invitation->getPersonalMessage();

        $html = sprintf(
            '<h2>You\'re Invited to Guard51</h2>
            <p>Hi %s,</p>
            <p>You\'ve been invited to join <strong>%s</strong> on Guard51 as a <strong>%s</strong>.</p>
            %s
            <p>Click the button below to create your account:</p>
            <p><a href="%s" style="display:inline-block;padding:12px 24px;background:#1B3A5C;color:#fff;text-decoration:none;border-radius:6px;">Accept Invitation</a></p>
            <p>This invitation expires in 7 days.</p>
            <p>Best regards,<br>The %s Team</p>',
            htmlspecialchars($name),
            htmlspecialchars($tenantName),
            htmlspecialchars($roleName),
            $message ? '<p><em>"' . htmlspecialchars($message) . '"</em></p>' : '',
            htmlspecialchars($acceptUrl),
            htmlspecialchars($tenantName),
        );

        $this->mailer->send(
            toEmail: $invitation->getEmail(),
            toName: trim(($invitation->getFirstName() ?? '') . ' ' . ($invitation->getLastName() ?? '')),
            subject: "You're invited to join {$tenantName} on Guard51",
            htmlBody: $html,
        );
    }
}
