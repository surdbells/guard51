<?php

declare(strict_types=1);

namespace Guard51\Module\Tenant;

use Guard51\Entity\TenantStatus;
use Guard51\Exception\ApiException;
use Guard51\Helper\JsonResponse;
use Guard51\Repository\SubscriptionRepository;
use Guard51\Repository\TenantRepository;
use Guard51\Repository\TenantUsageMetricRepository;
use Guard51\Repository\UserRepository;
use Guard51\Service\JwtService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

final class TenantController
{
    public function __construct(
        private readonly TenantRepository $tenantRepo,
        private readonly UserRepository $userRepo,
        private readonly SubscriptionRepository $subscriptionRepo,
        private readonly TenantUsageMetricRepository $usageRepo,
        private readonly JwtService $jwtService,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * GET /api/v1/admin/tenants — List all tenants (super admin)
     */
    public function index(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $page = max(1, (int) ($params['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($params['per_page'] ?? 20)));
        $status = $params['status'] ?? null;
        $type = $params['type'] ?? null;
        $search = $params['search'] ?? null;

        if ($search) {
            $tenants = $this->tenantRepo->search($search);
            return JsonResponse::success($response, [
                'tenants' => array_map(fn($t) => $t->toArray(), $tenants),
                'total' => count($tenants),
            ]);
        }

        $criteria = [];
        if ($status) $criteria['status'] = TenantStatus::tryFrom($status);
        if ($type) $criteria['tenantType'] = $type;

        $result = $this->tenantRepo->paginate($page, $perPage, $criteria);

        return JsonResponse::paginated(
            $response,
            array_map(fn($t) => $t->toArray(), $result['items']),
            $result['total'],
            $result['page'],
            $result['per_page'],
        );
    }

    /**
     * GET /api/v1/admin/tenants/{id} — Tenant detail with stats (super admin)
     */
    public function show(Request $request, Response $response): Response
    {
        $tenant = $this->tenantRepo->findOrFail($request->getAttribute('id'));
        $usage = $this->usageRepo->findByTenant($tenant->getId());
        $subscription = $this->subscriptionRepo->findActiveByTenant($tenant->getId());
        $userCount = $this->userRepo->countByTenant($tenant->getId());

        return JsonResponse::success($response, [
            'tenant' => $tenant->toArray(),
            'usage' => $usage?->toArray(),
            'subscription' => $subscription?->toArray(),
            'user_count' => $userCount,
        ]);
    }

    /**
     * POST /api/v1/admin/tenants/{id}/suspend — Suspend tenant (super admin)
     */
    public function suspend(Request $request, Response $response): Response
    {
        $tenant = $this->tenantRepo->findOrFail($request->getAttribute('id'));
        $body = (array) $request->getParsedBody();
        $reason = $body['reason'] ?? 'Suspended by administrator';

        if ($tenant->getStatus() === TenantStatus::SUSPENDED) {
            throw ApiException::conflict('Tenant is already suspended.');
        }

        $tenant->suspend($reason);
        $this->tenantRepo->save($tenant);

        $this->logger->warning('Tenant suspended.', [
            'tenant_id' => $tenant->getId(),
            'reason' => $reason,
        ]);

        return JsonResponse::success($response, [
            'tenant' => $tenant->toArray(),
            'message' => 'Tenant suspended.',
        ]);
    }

    /**
     * POST /api/v1/admin/tenants/{id}/reactivate — Reactivate tenant (super admin)
     */
    public function reactivate(Request $request, Response $response): Response
    {
        $tenant = $this->tenantRepo->findOrFail($request->getAttribute('id'));

        if ($tenant->getStatus() !== TenantStatus::SUSPENDED) {
            throw ApiException::conflict('Tenant is not currently suspended.');
        }

        $tenant->reactivate();
        $this->tenantRepo->save($tenant);

        $this->logger->info('Tenant reactivated.', ['tenant_id' => $tenant->getId()]);

        return JsonResponse::success($response, [
            'tenant' => $tenant->toArray(),
            'message' => 'Tenant reactivated.',
        ]);
    }

    /**
     * POST /api/v1/admin/tenants/{id}/impersonate — Get a JWT as this tenant's admin (super admin)
     */
    public function impersonate(Request $request, Response $response): Response
    {
        $tenant = $this->tenantRepo->findOrFail($request->getAttribute('id'));

        // Find the first company_admin user for this tenant
        $admins = $this->userRepo->findActiveByTenantAndRole($tenant->getId(), \Guard51\Entity\UserRole::COMPANY_ADMIN);
        if (empty($admins)) {
            throw ApiException::notFound('No active admin user found for this tenant.');
        }

        $adminUser = $admins[0];
        $token = $this->jwtService->generateAccessToken($adminUser);

        $this->logger->warning('Super admin impersonating tenant.', [
            'tenant_id' => $tenant->getId(),
            'impersonated_user' => $adminUser->getId(),
            'super_admin' => $request->getAttribute('user_id'),
        ]);

        return JsonResponse::success($response, [
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => $this->jwtService->getAccessTtl(),
            'impersonated_user' => $adminUser->toArray(),
            'tenant' => $tenant->toArray(),
        ]);
    }

    /**
     * GET /api/v1/admin/tenants/stats — Platform-wide tenant statistics (super admin)
     */
    public function stats(Request $request, Response $response): Response
    {
        return JsonResponse::success($response, [
            'total_tenants' => $this->tenantRepo->count([]),
            'active' => $this->tenantRepo->countByStatus(TenantStatus::ACTIVE),
            'trial' => $this->tenantRepo->countByStatus(TenantStatus::TRIAL),
            'suspended' => $this->tenantRepo->countByStatus(TenantStatus::SUSPENDED),
            'cancelled' => $this->tenantRepo->countByStatus(TenantStatus::CANCELLED),
            'active_subscriptions' => $this->subscriptionRepo->countActive(),
        ]);
    }
}
