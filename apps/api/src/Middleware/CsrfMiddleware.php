<?php
declare(strict_types=1);
namespace Guard51\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

/**
 * CSRF protection via Origin/Referer header validation.
 * Blocks state-changing requests (POST/PUT/DELETE) from untrusted origins.
 */
final class CsrfMiddleware implements MiddlewareInterface
{
    private array $allowedOrigins;

    public function __construct()
    {
        $appUrl = $_ENV['APP_URL'] ?? 'https://app.guard51.com';
        $this->allowedOrigins = [
            $appUrl,
            'https://guard51.com',
            'https://app.guard51.com',
            'https://api.guard51.com',
            'http://localhost:4200',  // Angular dev server
        ];
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        $method = $request->getMethod();

        // Only validate state-changing methods
        if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return $handler->handle($request);
        }

        // Skip for API key authenticated requests (webhooks)
        if ($request->hasHeader('X-Paystack-Signature') || $request->hasHeader('X-Api-Key')) {
            return $handler->handle($request);
        }

        $origin = $request->getHeaderLine('Origin');
        $referer = $request->getHeaderLine('Referer');

        // Accept if Origin matches
        if ($origin && $this->isAllowed($origin)) {
            return $handler->handle($request);
        }

        // Fallback to Referer
        if ($referer && $this->isAllowed($referer)) {
            return $handler->handle($request);
        }

        // If neither header present (e.g. mobile app, CLI), allow if Bearer token present
        if (!$origin && !$referer && $request->hasHeader('Authorization')) {
            return $handler->handle($request);
        }

        // Block
        $response = new \Slim\Psr7\Response();
        $response->getBody()->write(json_encode(['success' => false, 'message' => 'CSRF validation failed.']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
    }

    private function isAllowed(string $url): bool
    {
        $parsed = parse_url($url);
        $origin = ($parsed['scheme'] ?? '') . '://' . ($parsed['host'] ?? '') . (isset($parsed['port']) ? ':' . $parsed['port'] : '');
        foreach ($this->allowedOrigins as $allowed) {
            if (str_starts_with($origin, $allowed)) return true;
        }
        return false;
    }
}
