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
        $cors = $this->settings['cors'] ?? [];
        $origin = $request->getHeaderLine('Origin');
        $allowedOrigins = $cors['allowed_origins'] ?? ['*'];

        $isAllowed = in_array('*', $allowedOrigins, true) || in_array($origin, $allowedOrigins, true);

        // Handle preflight OPTIONS requests immediately (before routing)
        if ($request->getMethod() === 'OPTIONS') {
            $response = new \Slim\Psr7\Response();
            $response = $response->withStatus(204);
            if ($isAllowed && $origin) {
                $response = $this->addCorsHeaders($response, $origin, $cors);
            }
            return $response;
        }

        // Process the actual request
        try {
            $response = $handler->handle($request);
        } catch (\Throwable $e) {
            // Even on errors, add CORS headers so the browser can read the error
            $response = new \Slim\Psr7\Response();
            $response = $response->withStatus(500);
            $response->getBody()->write(json_encode(['error' => 'Internal Server Error']));
            $response = $response->withHeader('Content-Type', 'application/json');
        }

        if ($isAllowed && $origin) {
            $response = $this->addCorsHeaders($response, $origin, $cors);
        }

        return $response;
    }

    private function addCorsHeaders(Response $response, string $origin, array $cors): Response
    {
        return $response
            ->withHeader('Access-Control-Allow-Origin', $origin)
            ->withHeader('Access-Control-Allow-Methods', implode(', ', $cors['allowed_methods'] ?? ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS']))
            ->withHeader('Access-Control-Allow-Headers', implode(', ', $cors['allowed_headers'] ?? ['Content-Type', 'Authorization']))
            ->withHeader('Access-Control-Allow-Credentials', 'true')
            ->withHeader('Access-Control-Max-Age', (string) ($cors['max_age'] ?? 86400));
    }
}
