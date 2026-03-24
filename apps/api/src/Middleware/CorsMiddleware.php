<?php

declare(strict_types=1);

namespace Guard51\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

final class CorsMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly array $settings,
    ) {}

    public function process(Request $request, RequestHandler $handler): Response
    {
        $response = $handler->handle($request);

        $cors = $this->settings['cors'] ?? [];
        $origin = $request->getHeaderLine('Origin');
        $allowedOrigins = $cors['allowed_origins'] ?? ['*'];

        if (in_array('*', $allowedOrigins, true) || in_array($origin, $allowedOrigins, true)) {
            $response = $response
                ->withHeader('Access-Control-Allow-Origin', $origin ?: '*')
                ->withHeader('Access-Control-Allow-Methods', implode(', ', $cors['allowed_methods'] ?? ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS']))
                ->withHeader('Access-Control-Allow-Headers', implode(', ', $cors['allowed_headers'] ?? ['Content-Type', 'Authorization']))
                ->withHeader('Access-Control-Allow-Credentials', 'true')
                ->withHeader('Access-Control-Max-Age', (string) ($cors['max_age'] ?? 86400));
        }

        return $response;
    }
}
