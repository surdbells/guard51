<?php

declare(strict_types=1);

namespace Guard51\Module\Auth;

use Guard51\DTO\Auth\ForgotPasswordRequest;
use Guard51\DTO\Auth\LoginRequest;
use Guard51\DTO\Auth\RegisterRequest;
use Guard51\DTO\Auth\ResetPasswordRequest;
use Guard51\Entity\AuditLog;
use Guard51\Entity\RefreshToken;
use Guard51\Entity\Tenant;
use Guard51\Entity\TenantStatus;
use Guard51\Entity\TenantType;
use Guard51\Entity\User;
use Guard51\Entity\UserRole;
use Guard51\Exception\ApiException;
use Guard51\Helper\JsonResponse;
use Guard51\Repository\RefreshTokenRepository;
use Guard51\Repository\TenantRepository;
use Guard51\Repository\UserRepository;
use Guard51\Service\JwtService;
use Guard51\Service\ValidationService;
use Guard51\Service\ZeptoMailService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

final class AuthController
{
    public function __construct(
        private readonly UserRepository $userRepo,
        private readonly TenantRepository $tenantRepo,
        private readonly RefreshTokenRepository $refreshTokenRepo,
        private readonly JwtService $jwtService,
        private readonly ValidationService $validator,
        private readonly ZeptoMailService $mailer,
        private readonly LoggerInterface $logger,
    ) {}

    // ── POST /api/v1/auth/login ──────────────────────

    public function login(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();
        $dto = LoginRequest::fromArray($body);
        $this->validator->validate($dto);

        $user = $this->userRepo->findByEmail($dto->email);

        if ($user === null) {
            throw ApiException::unauthorized('Invalid email or password.');
        }

        if (!$user->isActive()) {
            throw ApiException::unauthorized('Your account has been deactivated.');
        }

        if ($user->isLocked()) {
            throw ApiException::tooManyRequests('Account temporarily locked due to failed login attempts. Try again later.');
        }

        // Verify tenant is operational (skip for super admin)
        if (!$user->isSuperAdmin() && $user->getTenantId() !== null) {
            $tenant = $this->tenantRepo->find($user->getTenantId());
            if ($tenant !== null && !$tenant->getStatus()->isOperational()) {
                throw ApiException::unauthorized('Your organization\'s account has been suspended.');
            }
        }

        if (!$user->verifyPassword($dto->password)) {
            $user->recordFailedLogin();
            $this->userRepo->save($user);
            throw ApiException::unauthorized('Invalid email or password.');
        }

        // Success — generate tokens
        $ip = $this->getClientIp($request);
        $user->recordLogin($ip);
        $this->userRepo->save($user);

        $tokens = $this->issueTokens($user, $request);

        $this->logger->info('User logged in.', [
            'user_id' => $user->getId(),
            'email' => $user->getEmail(),
            'ip' => $ip,
        ]);

        return JsonResponse::success($response, [
            'user' => $user->toArray(),
            'tokens' => $tokens,
        ]);
    }

    // ── POST /api/v1/auth/register ───────────────────

    public function register(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();
        $dto = RegisterRequest::fromArray($body);
        $this->validator->validate($dto);

        // Check email uniqueness
        if ($this->userRepo->emailExists($dto->email)) {
            throw ApiException::conflict('An account with this email already exists.');
        }

        // Create tenant
        $tenantType = TenantType::tryFrom($dto->tenantType) ?? TenantType::PRIVATE_SECURITY;

        $tenant = new Tenant();
        $tenant->setName($dto->companyName)
            ->setTenantType($tenantType)
            ->setEmail($dto->email)
            ->setPhone($dto->phone)
            ->setStatus(TenantStatus::TRIAL);

        $this->tenantRepo->save($tenant);

        // Create company admin user
        $user = new User();
        $user->setEmail($dto->email)
            ->setPassword($dto->password)
            ->setFirstName($dto->firstName)
            ->setLastName($dto->lastName)
            ->setPhone($dto->phone)
            ->setRole(UserRole::COMPANY_ADMIN)
            ->setTenantId($tenant->getId())
            ->setIsActive(true);

        $this->userRepo->save($user);

        // Issue tokens
        $ip = $this->getClientIp($request);
        $user->recordLogin($ip);
        $this->userRepo->save($user);

        $tokens = $this->issueTokens($user, $request);

        // Audit log
        $this->logAudit('register', 'Tenant', $tenant->getId(), $tenant->getId(), $user->getId(), $user->getFullName(),
            'New tenant registered: ' . $tenant->getName(), $request);

        // Send welcome email
        $this->sendWelcomeEmail($user, $tenant);

        $this->logger->info('New tenant registered.', [
            'tenant_id' => $tenant->getId(),
            'tenant_name' => $tenant->getName(),
            'user_id' => $user->getId(),
        ]);

        return JsonResponse::success($response, [
            'user' => $user->toArray(),
            'tenant' => $tenant->toArray(),
            'tokens' => $tokens,
        ], 201);
    }

    // ── POST /api/v1/auth/refresh ────────────────────

    public function refresh(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();
        $rawRefreshToken = $body['refresh_token'] ?? '';

        if (empty($rawRefreshToken)) {
            throw ApiException::validation('Refresh token is required.');
        }

        // Find all valid refresh tokens and match
        // We search by iterating valid tokens since we store hashes
        $allTokens = $this->refreshTokenRepo->findAll();
        $matchedToken = null;

        foreach ($allTokens as $token) {
            if ($token->isValid() && $token->matchesToken($rawRefreshToken)) {
                $matchedToken = $token;
                break;
            }
        }

        if ($matchedToken === null) {
            throw ApiException::unauthorized('Invalid or expired refresh token.');
        }

        // Revoke the used refresh token (rotation)
        $matchedToken->revoke();
        $this->refreshTokenRepo->save($matchedToken);

        // Find the user
        $user = $this->userRepo->find($matchedToken->getUserId());
        if ($user === null || !$user->isActive()) {
            throw ApiException::unauthorized('User account not found or deactivated.');
        }

        // Issue new token pair
        $tokens = $this->issueTokens($user, $request);

        $this->logger->debug('Token refreshed.', ['user_id' => $user->getId()]);

        return JsonResponse::success($response, [
            'user' => $user->toArray(),
            'tokens' => $tokens,
        ]);
    }

    // ── POST /api/v1/auth/logout ─────────────────────

    public function logout(Request $request, Response $response): Response
    {
        $userId = $request->getAttribute('user_id');

        if ($userId) {
            // Revoke all refresh tokens for this user
            $revoked = $this->refreshTokenRepo->revokeAllForUser($userId);
            $this->logger->info('User logged out. Refresh tokens revoked.', [
                'user_id' => $userId,
                'tokens_revoked' => $revoked,
            ]);
        }

        return JsonResponse::success($response, ['message' => 'Logged out successfully.']);
    }

    // ── GET /api/v1/auth/me ──────────────────────────

    public function me(Request $request, Response $response): Response
    {
        $userId = $request->getAttribute('user_id');
        $user = $this->userRepo->findOrFail($userId);

        $data = $user->toArray();

        // Include tenant info if user belongs to one
        if ($user->getTenantId()) {
            $tenant = $this->tenantRepo->find($user->getTenantId());
            if ($tenant) {
                $data['tenant'] = $tenant->toArray();
            }
        }

        return JsonResponse::success($response, $data);
    }

    // ── POST /api/v1/auth/forgot-password ────────────

    public function forgotPassword(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();
        $dto = ForgotPasswordRequest::fromArray($body);
        $this->validator->validate($dto);

        $user = $this->userRepo->findByEmail($dto->email);

        // Always return success to prevent email enumeration
        if ($user === null) {
            return JsonResponse::success($response, [
                'message' => 'If an account with this email exists, a password reset link has been sent.',
            ]);
        }

        $rawToken = $user->generatePasswordResetToken();
        $this->userRepo->save($user);

        // Send reset email
        $this->sendPasswordResetEmail($user, $rawToken);

        $this->logger->info('Password reset requested.', ['email' => $dto->email]);

        return JsonResponse::success($response, [
            'message' => 'If an account with this email exists, a password reset link has been sent.',
        ]);
    }

    // ── POST /api/v1/auth/reset-password ─────────────

    public function resetPassword(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();
        $dto = ResetPasswordRequest::fromArray($body);
        $this->validator->validate($dto);

        $user = $this->userRepo->findByEmail($dto->email);

        if ($user === null || !$user->isPasswordResetTokenValid($dto->token)) {
            throw ApiException::validation('Invalid or expired password reset token.');
        }

        $user->setPassword($dto->password);
        $user->clearPasswordResetToken();
        $this->userRepo->save($user);

        // Revoke all refresh tokens (force re-login everywhere)
        $this->refreshTokenRepo->revokeAllForUser($user->getId());

        $this->logAudit('password_reset', 'User', $user->getId(), $user->getTenantId(),
            $user->getId(), $user->getFullName(), 'Password reset completed', $request);

        $this->logger->info('Password reset completed.', ['user_id' => $user->getId()]);

        return JsonResponse::success($response, [
            'message' => 'Password has been reset successfully. Please log in with your new password.',
        ]);
    }

    // ── Private Helpers ──────────────────────────────

    private function issueTokens(User $user, Request $request): array
    {
        $accessToken = $this->jwtService->generateAccessToken($user);

        // Generate refresh token
        $result = RefreshToken::generate(
            userId: $user->getId(),
            ttlSeconds: $this->jwtService->getRefreshTtl(),
            userAgent: $request->getHeaderLine('User-Agent') ?: null,
            ip: $this->getClientIp($request),
        );

        $this->refreshTokenRepo->save($result['entity']);

        return [
            'access_token' => $accessToken,
            'refresh_token' => $result['raw_token'],
            'token_type' => 'Bearer',
            'expires_in' => $this->jwtService->getAccessTtl(),
        ];
    }

    private function sendWelcomeEmail(User $user, Tenant $tenant): void
    {
        $html = sprintf(
            '<h2>Welcome to Guard51!</h2>
            <p>Hi %s,</p>
            <p>Your organization <strong>%s</strong> has been registered on Guard51.</p>
            <p>You can now log in to your dashboard and start setting up your security operations.</p>
            <p><strong>Next steps:</strong></p>
            <ul>
                <li>Complete your organization profile</li>
                <li>Add your first site/post</li>
                <li>Invite your guards and staff</li>
                <li>Download the Guard51 mobile app</li>
            </ul>
            <p>Best regards,<br>The Guard51 Team</p>',
            htmlspecialchars($user->getFirstName()),
            htmlspecialchars($tenant->getName()),
        );

        $this->mailer->send(
            toEmail: $user->getEmail(),
            toName: $user->getFullName(),
            subject: 'Welcome to Guard51 — Your account is ready',
            htmlBody: $html,
        );
    }

    private function sendPasswordResetEmail(User $user, string $rawToken): void
    {
        $resetUrl = ($_ENV['APP_URL'] ?? 'http://localhost:4200') . '/auth/reset-password?token=' . urlencode($rawToken) . '&email=' . urlencode($user->getEmail());

        $html = sprintf(
            '<h2>Guard51 — Password Reset</h2>
            <p>Hi %s,</p>
            <p>We received a request to reset your password. Click the link below to set a new password:</p>
            <p><a href="%s" style="display:inline-block;padding:12px 24px;background:#1B3A5C;color:#fff;text-decoration:none;border-radius:6px;">Reset Password</a></p>
            <p>This link expires in 1 hour.</p>
            <p>If you didn\'t request this, you can safely ignore this email.</p>
            <p>Best regards,<br>The Guard51 Team</p>',
            htmlspecialchars($user->getFirstName()),
            htmlspecialchars($resetUrl),
        );

        $this->mailer->send(
            toEmail: $user->getEmail(),
            toName: $user->getFullName(),
            subject: 'Guard51 — Reset your password',
            htmlBody: $html,
        );
    }

    private function logAudit(
        string $action, string $entityType, ?string $entityId,
        ?string $tenantId, ?string $userId, ?string $userName,
        ?string $description, Request $request
    ): void {
        $log = AuditLog::create(
            action: $action,
            entityType: $entityType,
            entityId: $entityId,
            tenantId: $tenantId,
            userId: $userId,
            userName: $userName,
            description: $description,
            ipAddress: $this->getClientIp($request),
            userAgent: $request->getHeaderLine('User-Agent') ?: null,
            requestId: $request->getAttribute('request_id'),
        );

        // Persist via entity manager (accessed through repo's EM)
        // Using the user repo's entity manager for simplicity
        $this->userRepo->save($log);
    }

    private function getClientIp(Request $request): string
    {
        $headers = ['X-Forwarded-For', 'X-Real-IP', 'CF-Connecting-IP'];
        foreach ($headers as $header) {
            $value = $request->getHeaderLine($header);
            if (!empty($value)) {
                return trim(explode(',', $value)[0]);
            }
        }
        return $request->getServerParams()['REMOTE_ADDR'] ?? '127.0.0.1';
    }
}
