<?php

declare(strict_types=1);

namespace Guard51\Module\Feature;

use Guard51\Entity\TenantType;
use Guard51\Helper\JsonResponse;
use Guard51\Repository\FeatureModuleRepository;
use Guard51\Repository\TenantRepository;
use Guard51\Service\FeatureService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

final class FeatureController
{
    public function __construct(
        private readonly FeatureModuleRepository $moduleRepo,
        private readonly FeatureService $featureService,
        private readonly TenantRepository $tenantRepo,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * GET /api/v1/features/modules — List all feature modules (super admin)
     */
    public function listModules(Request $request, Response $response): Response
    {
        $modules = $this->moduleRepo->findBy(['isActive' => true], ['sortOrder' => 'ASC']);
        return JsonResponse::success($response, [
            'modules' => array_map(fn($m) => $m->toArray(), $modules),
            'total' => count($modules),
            'categories' => $this->moduleRepo->getCategories(),
        ]);
    }

    /**
     * GET /api/v1/features/tenant — Get modules for current tenant with enabled status
     */
    public function tenantModules(Request $request, Response $response): Response
    {
        $tenantId = $request->getAttribute('tenant_id');
        $tenant = $this->tenantRepo->findOrFail($tenantId);
        $modules = $this->featureService->getModulesForTenant($tenantId, $tenant->getTenantType());

        return JsonResponse::success($response, [
            'modules' => $modules,
            'enabled_count' => count(array_filter($modules, fn($m) => $m['is_enabled'])),
        ]);
    }

    /**
     * POST /api/v1/features/tenant/enable/{moduleKey} — Enable a module for current tenant
     */
    public function enableModule(Request $request, Response $response, array $args): Response
    {
        $tenantId = $request->getAttribute('tenant_id');
        $moduleKey = $args['moduleKey'] ?? '';
        $userId = $request->getAttribute('user_id');

        $enabled = $this->featureService->enableModule($tenantId, $moduleKey, $userId ?? 'admin');

        return JsonResponse::success($response, [
            'message' => 'Module enabled successfully.',
            'enabled_modules' => $enabled,
        ]);
    }

    /**
     * POST /api/v1/features/tenant/disable/{moduleKey} — Disable a module for current tenant
     */
    public function disableModule(Request $request, Response $response, array $args): Response
    {
        $tenantId = $request->getAttribute('tenant_id');
        $moduleKey = $args['moduleKey'] ?? '';

        $this->featureService->disableModule($tenantId, $moduleKey);

        return JsonResponse::success($response, [
            'message' => 'Module disabled successfully.',
            'module_key' => $moduleKey,
        ]);
    }
}
