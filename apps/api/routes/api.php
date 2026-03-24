<?php

declare(strict_types=1);

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
    $app->group('/api/v1', function (RouteCollectorProxy $group): void {

        // Auth routes (Phase 0C)
        // $group->group('/auth', function (RouteCollectorProxy $auth): void {
        //     $auth->post('/login', [AuthController::class, 'login']);
        //     $auth->post('/register', [AuthController::class, 'register']);
        //     $auth->post('/refresh', [AuthController::class, 'refresh']);
        //     $auth->post('/logout', [AuthController::class, 'logout']);
        //     $auth->get('/me', [AuthController::class, 'me']);
        //     $auth->post('/forgot-password', [AuthController::class, 'forgotPassword']);
        //     $auth->post('/reset-password', [AuthController::class, 'resetPassword']);
        // });

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
