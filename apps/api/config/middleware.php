<?php
declare(strict_types=1);

use Guard51\Middleware\CorsMiddleware;
use Guard51\Middleware\CsrfMiddleware;
use Guard51\Middleware\JsonBodyParserMiddleware;
use Guard51\Middleware\RequestIdMiddleware;
use Slim\App;

return function (App $app): void {
    // Slim routing (innermost — runs last)
    $app->addRoutingMiddleware();

    // Slim error handling
    $app->addErrorMiddleware(
        displayErrorDetails: (bool) ($app->getContainer()->get('settings')['app']['debug'] ?? false),
        logErrors: true,
        logErrorDetails: true,
    );

    // Security headers (HSTS, X-Frame-Options, CSP, etc.)
    $app->add(\Guard51\Middleware\SecurityHeadersMiddleware::class);

    // CSRF protection (validates Origin/Referer on POST/PUT/DELETE)
    $app->add(CsrfMiddleware::class);

    // Parse JSON request bodies
    $app->add(JsonBodyParserMiddleware::class);

    // Add unique request ID to every request
    $app->add(RequestIdMiddleware::class);

    // CORS headers (outermost — runs first, added last in Slim's LIFO order)
    $app->add(CorsMiddleware::class);
};
