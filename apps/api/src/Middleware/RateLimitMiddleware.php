<?php

declare(strict_types=1);

namespace Guard51\Middleware;

use Predis\Client as RedisClient;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

/**
 * Redis-based rate limiter using sliding window counters.
 *
 * Usage:
 *   new RateLimitMiddleware($redis, maxAttempts: 5, windowSeconds: 60)   // 5 per minute
 *   new RateLimitMiddleware($redis, maxAttempts: 30, windowSeconds: 60)  // 30 per minute
 *
 * Key format: rate_limit:{prefix}:{ip}
 * Returns X-RateLimit-Limit, X-RateLimit-Remaining, Retry-After headers.
 */
final class RateLimitMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly RedisClient $redis,
        private readonly int $maxAttempts = 60,
        private readonly int $windowSeconds = 60,
        private readonly string $prefix = 'default',
    ) {}

    public function process(Request $request, RequestHandler $handler): Response
    {
        $ip = $this->getClientIp($request);
        $key = "rate_limit:{$this->prefix}:{$ip}";

        $current = (int) $this->redis->get($key);

        if ($current >= $this->maxAttempts) {
            $ttl = $this->redis->ttl($key);
            return $this->tooManyRequests($ttl > 0 ? $ttl : $this->windowSeconds);
        }

        // Increment counter
        $pipe = $this->redis->pipeline();
        $pipe->incr($key);
        if ($current === 0) {
            $pipe->expire($key, $this->windowSeconds);
        }
        $pipe->execute();

        $remaining = max(0, $this->maxAttempts - $current - 1);

        // Process request and add rate limit headers
        $response = $handler->handle($request);

        return $response
            ->withHeader('X-RateLimit-Limit', (string) $this->maxAttempts)
            ->withHeader('X-RateLimit-Remaining', (string) $remaining)
            ->withHeader('X-RateLimit-Window', (string) $this->windowSeconds);
    }

    private function tooManyRequests(int $retryAfter): Response
    {
        $response = new \Slim\Psr7\Response();
        $response->getBody()->write(json_encode([
            'success' => false,
            'message' => 'Too many requests. Please try again later.',
            'retry_after' => $retryAfter,
        ]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Retry-After', (string) $retryAfter)
            ->withHeader('X-RateLimit-Limit', (string) $this->maxAttempts)
            ->withHeader('X-RateLimit-Remaining', '0')
            ->withStatus(429);
    }

    private function getClientIp(Request $request): string
    {
        // Check common proxy headers
        $headers = ['X-Forwarded-For', 'X-Real-IP', 'CF-Connecting-IP'];
        foreach ($headers as $header) {
            $value = $request->getHeaderLine($header);
            if (!empty($value)) {
                // X-Forwarded-For can contain multiple IPs; take the first
                $ips = explode(',', $value);
                return trim($ips[0]);
            }
        }

        $serverParams = $request->getServerParams();
        return $serverParams['REMOTE_ADDR'] ?? '127.0.0.1';
    }
}
