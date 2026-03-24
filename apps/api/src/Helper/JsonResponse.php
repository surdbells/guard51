<?php

declare(strict_types=1);

namespace Guard51\Helper;

use Psr\Http\Message\ResponseInterface;

final class JsonResponse
{
    public static function success(ResponseInterface $response, mixed $data = null, int $status = 200): ResponseInterface
    {
        $payload = ['success' => true];
        if ($data !== null) {
            $payload['data'] = $data;
        }

        $response->getBody()->write(json_encode($payload, JSON_THROW_ON_ERROR));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }

    public static function error(ResponseInterface $response, string $message, int $status = 400, array $errors = []): ResponseInterface
    {
        $payload = [
            'success' => false,
            'message' => $message,
        ];
        if (!empty($errors)) {
            $payload['errors'] = $errors;
        }

        $response->getBody()->write(json_encode($payload, JSON_THROW_ON_ERROR));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }

    public static function paginated(ResponseInterface $response, array $items, int $total, int $page, int $perPage): ResponseInterface
    {
        $payload = [
            'success' => true,
            'data' => $items,
            'meta' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'last_page' => (int) ceil($total / $perPage),
            ],
        ];

        $response->getBody()->write(json_encode($payload, JSON_THROW_ON_ERROR));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
}
