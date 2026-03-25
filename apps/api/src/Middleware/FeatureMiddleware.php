<?php

declare(strict_types=1);

namespace Guard51\Middleware;

use Guard51\Service\FeatureService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

/**
 * Checks if the required feature module is enabled for the current tenant.
 * Returns 403 if the module is not enabled (not in their plan).
 *
 * Must run AFTER AuthMiddleware and TenantMiddleware.
 *
 * Usage:
 *   $group->get('/vehicle-patrol', ...)->add(new FeatureMiddleware($featureService, 'vehicle_patrol'));
 */
final class FeatureMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly FeatureService $featureService,
        private readonly string $moduleKey,
    ) {}

    public function process(Request $request, RequestHandler $handler): Response
    {
        $tenantId = $request->getAttribute('tenant_id');

        // Super admin bypasses feature checks
        $role = $request->getAttribute('user_role');
        if ($role === 'super_admin') {
            return $handler->handle($request);
        }

        if ($tenantId === null) {
            return $this->forbidden('Tenant context required to access this feature.');
        }

        if (!$this->featureService->isEnabled($tenantId, $this->moduleKey)) {
            return $this->forbidden(sprintf(
                'The "%s" feature is not available on your current plan. Please upgrade to access this feature.',
                str_replace('_', ' ', $this->moduleKey)
            ));
        }

        return $handler->handle($request);
    }

    private function forbidden(string $message): Response
    {
        $response = new \Slim\Psr7\Response();
        $response->getBody()->write(json_encode([
            'success' => false,
            'message' => $message,
            'error_code' => 'feature_not_available',
            'required_module' => $this->moduleKey,
        ]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(403);
    }
}
