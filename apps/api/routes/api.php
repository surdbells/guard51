<?php

declare(strict_types=1);

use Guard51\Middleware\AuthMiddleware;
use Guard51\Middleware\RateLimitMiddleware;
use Guard51\Middleware\TenantMiddleware;
use Guard51\Module\Auth\AuthController;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app): void {

    // ── Health Check ────────────────────────────────
    $app->get('/api/health', function (Request $request, Response $response): Response {
        $payload = json_encode([
            'status' => 'ok',
            'service' => 'guard51-api',
            'version' => '0.1.0',
            'timestamp' => (new DateTimeImmutable())->format(DateTimeInterface::ATOM),
            'php' => PHP_VERSION,
        ]);

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // ── API v1 Routes ───────────────────────────────
    $app->group('/api/v1', function (RouteCollectorProxy $group) use ($app): void {
        $container = $app->getContainer();

        // ── Auth: Public routes (no auth required) ──
        $group->group('/auth', function (RouteCollectorProxy $auth) use ($container): void {
            $auth->post('/login', [AuthController::class, 'login'])
                ->add(new RateLimitMiddleware($container->get(\Predis\Client::class), maxAttempts: 5, windowSeconds: 60, prefix: 'login'));

            $auth->post('/register', [AuthController::class, 'register'])
                ->add(new RateLimitMiddleware($container->get(\Predis\Client::class), maxAttempts: 3, windowSeconds: 60, prefix: 'register'));

            $auth->post('/refresh', [AuthController::class, 'refresh'])
                ->add(new RateLimitMiddleware($container->get(\Predis\Client::class), maxAttempts: 10, windowSeconds: 60, prefix: 'refresh'));

            $auth->post('/forgot-password', [AuthController::class, 'forgotPassword'])
                ->add(new RateLimitMiddleware($container->get(\Predis\Client::class), maxAttempts: 3, windowSeconds: 300, prefix: 'forgot'));

            $auth->post('/reset-password', [AuthController::class, 'resetPassword'])
                ->add(new RateLimitMiddleware($container->get(\Predis\Client::class), maxAttempts: 5, windowSeconds: 300, prefix: 'reset'));
        });

        // ── Auth: Protected routes (JWT required) ───
        $group->group('/auth', function (RouteCollectorProxy $auth): void {
            $auth->get('/me', [AuthController::class, 'me']);
            $auth->post('/logout', [AuthController::class, 'logout']);
        })
            ->add($container->get(TenantMiddleware::class))
            ->add($container->get(AuthMiddleware::class));

    });

    // ── CORS Preflight ──────────────────────────────
    $app->options('/{routes:.+}', function (Request $request, Response $response): Response {
        return $response;
    });

    // ── 404 Catch-all ───────────────────────────────
    $app->map(['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], '/{routes:.+}', function (Request $request, Response $response): Response {
        $payload = json_encode([
            'error' => 'Not Found',
            'message' => 'The requested endpoint does not exist.',
            'status' => 404,
        ]);

        $response->getBody()->write($payload);
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(404);
    });
};
