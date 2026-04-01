<?php

declare(strict_types=1);

namespace Guard51\Module\Onboarding;

use Guard51\Entity\UserRole;
use Guard51\Exception\ApiException;
use Guard51\Helper\JsonResponse;
use Guard51\Service\InvitationService;
use Guard51\Service\JwtService;
use Guard51\Service\ValidationService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

final class InvitationController
{
    public function __construct(
        private readonly InvitationService $invitationService,
        private readonly JwtService $jwtService,
        private readonly ValidationService $validator,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * POST /api/v1/invitations — Send a staff invitation
     */
    public function invite(Request $request, Response $response): Response
    {
        $tenantId = $request->getAttribute('tenant_id');
        $userId = $request->getAttribute('user_id');
        $body = (array) $request->getParsedBody();

        $email = trim($body['email'] ?? '');
        $roleValue = $body['role'] ?? 'guard';
        $firstName = $body['first_name'] ?? null;
        $lastName = $body['last_name'] ?? null;
        $message = $body['personal_message'] ?? null;

        if (empty($email)) {
            throw ApiException::validation('Email is required.');
        }

        $role = UserRole::tryFrom($roleValue);
        if (!$role) {
            throw ApiException::validation('Invalid role.');
        }

        $invitation = $this->invitationService->invite(
            $tenantId, $email, $role, $userId, $firstName, $lastName, $message
        );

        return JsonResponse::success($response, $invitation->toArray(), 201);
    }

    /**
     * POST /api/v1/invitations/accept — Accept invitation (public, no auth)
     */
    public function accept(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();

        $token = $body['token'] ?? '';
        $email = trim($body['email'] ?? '');
        $password = $body['password'] ?? '';
        $firstName = $body['first_name'] ?? null;
        $lastName = $body['last_name'] ?? null;
        $phone = $body['phone'] ?? null;

        if (empty($token) || empty($email) || empty($password)) {
            throw ApiException::validation('Token, email, and password are required.');
        }

        if (strlen($password) < 8) {
            throw ApiException::validation('Password must be at least 8 characters.');
        }

        $user = $this->invitationService->accept($token, $email, $password, $firstName, $lastName, $phone);

        // Issue JWT for the new user
        $accessToken = $this->jwtService->generateAccessToken($user);

        return JsonResponse::success($response, [
            'user' => $user->toArray(),
            'tokens' => [
                'access_token' => $accessToken,
                'token_type' => 'Bearer',
                'expires_in' => $this->jwtService->getAccessTtl(),
            ],
            'message' => 'Invitation accepted. Welcome to the team!',
        ], 201);
    }

    /**
     * GET /api/v1/invitations — List invitations for tenant
     */
    public function index(Request $request, Response $response): Response
    {
        $tenantId = $request->getAttribute('tenant_id');
        $status = $request->getQueryParams()['status'] ?? null;

        $invitations = $this->invitationService->listForTenant($tenantId, $status);

        return JsonResponse::success($response, [
            'invitations' => array_map(fn($i) => $i->toArray(), $invitations),
            'total' => count($invitations),
        ]);
    }

    /**
     * POST /api/v1/invitations/{id}/resend — Resend invitation
     */
    public function resend(Request $request, Response $response): Response
    {
        $tenantId = $request->getAttribute('tenant_id');
        $invitation = $this->invitationService->resend($request->getAttribute('id'), $tenantId);

        return JsonResponse::success($response, [
            'invitation' => $invitation->toArray(),
            'message' => 'Invitation resent successfully.',
        ]);
    }

    /**
     * DELETE /api/v1/invitations/{id} — Revoke invitation
     */
    public function revoke(Request $request, Response $response): Response
    {
        $tenantId = $request->getAttribute('tenant_id');
        $invitation = $this->invitationService->revoke($request->getAttribute('id'), $tenantId);

        return JsonResponse::success($response, [
            'invitation' => $invitation->toArray(),
            'message' => 'Invitation revoked.',
        ]);
    }
}
