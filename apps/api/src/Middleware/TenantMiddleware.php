<?php

declare(strict_types=1);

namespace Guard51\Middleware;

use Doctrine\ORM\EntityManagerInterface;
use Guard51\Entity\UserRole;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

/**
 * Resolves the current tenant from the authenticated user's JWT claims.
 * Enables the Doctrine TenantFilter so all queries are automatically scoped.
 *
 * Must run AFTER AuthMiddleware (which sets 'user' attribute on request).
 * Super admin requests skip tenant filtering.
 */
final class TenantMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    public function process(Request $request, RequestHandler $handler): Response
    {
        $user = $request->getAttribute('user');

        if ($user === null) {
            // No authenticated user — skip tenant filtering (public routes)
            return $handler->handle($request);
        }

        $role = UserRole::tryFrom($user['role'] ?? '');
        $tenantId = $user['tenant_id'] ?? null;

        // Super admins see all tenants — do NOT enable filter
        if ($role === UserRole::SUPER_ADMIN) {
            return $handler->handle($request);
        }

        // Citizens are not tenant-scoped (they interact across tenants by location)
        if ($role === UserRole::CITIZEN) {
            return $handler->handle($request);
        }

        // All other roles must have a tenant_id
        if ($tenantId === null) {
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Tenant context required.',
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(403);
        }

        // Enable the Doctrine tenant filter with the resolved tenant_id
        $filter = $this->em->getFilters()->enable('tenant_filter');
        $filter->setParameter('tenant_id', "'{$tenantId}'");

        // Pass tenant_id forward as request attribute for controllers
        $request = $request->withAttribute('tenant_id', $tenantId);

        return $handler->handle($request);
    }
}
