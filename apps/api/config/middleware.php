<?php

declare(strict_types=1);

use Guard51\Middleware\CorsMiddleware;
use Guard51\Middleware\JsonBodyParserMiddleware;
use Guard51\Middleware\RequestIdMiddleware;
use Slim\App;

return function (App $app): void {
    // Parse JSON request bodies
    $app->add(JsonBodyParserMiddleware::class);

    // Add unique request ID to every request
    $app->add(RequestIdMiddleware::class);

    // CORS headers
    $app->add(CorsMiddleware::class);

    // Slim error handling
    $app->addErrorMiddleware(
        displayErrorDetails: (bool) ($app->getContainer()->get('settings')['app']['debug'] ?? false),
        logErrors: true,
        logErrorDetails: true,
    );

    // Slim routing
    $app->addRoutingMiddleware();
};
