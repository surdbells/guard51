<?php

declare(strict_types=1);

namespace Guard51\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

final class JsonBodyParserMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandler $handler): Response
    {
        $contentType = $request->getHeaderLine('Content-Type');

        if (str_contains($contentType, 'application/json')) {
            $contents = $request->getBody()->getContents();
            $data = json_decode($contents, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                $request = $request->withParsedBody($data);
            }
        }

        return $handler->handle($request);
    }
}
