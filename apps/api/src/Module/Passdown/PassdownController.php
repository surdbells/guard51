<?php

declare(strict_types=1);

namespace Guard51\Module\Passdown;

use Guard51\Helper\JsonResponse;
use Guard51\Service\PassdownService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class PassdownController
{
    public function __construct(private readonly PassdownService $passdownService) {}

    public function create(Request $request, Response $response): Response
    {
        $log = $this->passdownService->createPassdown($request->getAttribute('tenant_id'), (array) $request->getParsedBody());
        return JsonResponse::success($response, $log->toArray(), 201);
    }

    public function listBySite(Request $request, Response $response, array $args): Response
    {
        $limit = (int) ($request->getQueryParams()['limit'] ?? 20);
        $logs = $this->passdownService->listBySite($args['siteId'], $limit);
        return JsonResponse::success($response, ['passdowns' => array_map(fn($p) => $p->toArray(), $logs)]);
    }

    public function unacknowledged(Request $request, Response $response): Response
    {
        $logs = $this->passdownService->listUnacknowledged($request->getAttribute('tenant_id'));
        return JsonResponse::success($response, ['passdowns' => array_map(fn($p) => $p->toArray(), $logs)]);
    }

    public function acknowledge(Request $request, Response $response, array $args): Response
    {
        $log = $this->passdownService->acknowledge($args['id'], $request->getAttribute('user_id'));
        return JsonResponse::success($response, $log->toArray());
    }
}
