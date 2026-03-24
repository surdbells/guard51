<?php

declare(strict_types=1);

namespace Guard51\Middleware;

use Guard51\Entity\UserRole;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

/**
 * Restricts route access to specified roles.
 * Must run AFTER AuthMiddleware (requires 'user_role' request attribute).
 *
 * Usage in route definitions:
 *   $group->get('/admin/tenants', ...)->add(new RoleMiddleware(UserRole::SUPER_ADMIN));
 *   $group->get('/guards', ...)->add(new RoleMiddleware(UserRole::COMPANY_ADMIN, UserRole::SUPERVISOR));
 */
final class RoleMiddleware implements MiddlewareInterface
{
    /** @var UserRole[] */
    private array $allowedRoles;

    public function __construct(UserRole ...$allowedRoles)
    {
        $this->allowedRoles = $allowedRoles;
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        $roleValue = $request->getAttribute('user_role');

        if ($roleValue === null) {
            return $this->forbidden('Authentication required.');
        }

        $userRole = UserRole::tryFrom($roleValue);
        if ($userRole === null) {
            return $this->forbidden('Invalid user role.');
        }

        // Super admin always passes
        if ($userRole === UserRole::SUPER_ADMIN) {
            return $handler->handle($request);
        }

        if (!in_array($userRole, $this->allowedRoles, true)) {
            return $this->forbidden(
                sprintf('Access denied. Required role(s): %s',
                    implode(', ', array_map(fn(UserRole $r) => $r->label(), $this->allowedRoles))
                )
            );
        }

        return $handler->handle($request);
    }

    private function forbidden(string $message): Response
    {
        $response = new \Slim\Psr7\Response();
        $response->getBody()->write(json_encode([
            'success' => false,
            'message' => $message,
        ]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(403);
    }
}
