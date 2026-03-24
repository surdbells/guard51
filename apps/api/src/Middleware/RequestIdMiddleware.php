<?php

declare(strict_types=1);

namespace Guard51\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Ramsey\Uuid\Uuid;

final class RequestIdMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandler $handler): Response
    {
        $requestId = $request->getHeaderLine('X-Request-ID') ?: Uuid::uuid4()->toString();

        $request = $request->withAttribute('request_id', $requestId);
        $response = $handler->handle($request);

        return $response->withHeader('X-Request-ID', $requestId);
    }
}
