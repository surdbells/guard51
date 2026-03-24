<?php

declare(strict_types=1);

namespace Guard51\Middleware;

use Guard51\Service\JwtService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

/**
 * Validates the JWT access token from the Authorization header.
 * On success, sets 'user' attribute on the request with decoded claims.
 * On failure, returns 401 Unauthorized.
 *
 * Must run BEFORE TenantMiddleware and RoleMiddleware.
 */
final class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly JwtService $jwtService,
    ) {}

    public function process(Request $request, RequestHandler $handler): Response
    {
        $header = $request->getHeaderLine('Authorization');

        if (empty($header)) {
            return $this->unauthorized('Missing Authorization header.');
        }

        $token = JwtService::extractFromHeader($header);
        if ($token === null) {
            return $this->unauthorized('Invalid Authorization header format. Expected: Bearer <token>');
        }

        $payload = $this->jwtService->validateAccessToken($token);
        if ($payload === null) {
            return $this->unauthorized('Invalid or expired access token.');
        }

        // Set decoded user claims as request attribute
        $request = $request
            ->withAttribute('user', $payload)
            ->withAttribute('user_id', $payload['sub'] ?? null)
            ->withAttribute('tenant_id', $payload['tenant_id'] ?? null)
            ->withAttribute('user_role', $payload['role'] ?? null);

        return $handler->handle($request);
    }

    private function unauthorized(string $message): Response
    {
        $response = new \Slim\Psr7\Response();
        $response->getBody()->write(json_encode([
            'success' => false,
            'message' => $message,
        ]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(401);
    }
}
