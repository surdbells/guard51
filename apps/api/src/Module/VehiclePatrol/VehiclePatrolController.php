<?php
declare(strict_types=1);
namespace Guard51\Module\VehiclePatrol;

use Guard51\Helper\JsonResponse;
use Guard51\Service\VehiclePatrolService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class VehiclePatrolController
{
    public function __construct(private readonly VehiclePatrolService $patrolService) {}

    public function listVehicles(Request $request, Response $response): Response
    { return JsonResponse::success($response, ['vehicles' => array_map(fn($v) => $v->toArray(), $this->patrolService->listVehicles($request->getAttribute('tenant_id')))]); }

    public function createVehicle(Request $request, Response $response): Response
    { return JsonResponse::success($response, $this->patrolService->createVehicle($request->getAttribute('tenant_id'), (array) $request->getParsedBody())->toArray(), 201); }

    public function listRoutes(Request $request, Response $response): Response
    { return JsonResponse::success($response, ['routes' => array_map(fn($r) => $r->toArray(), $this->patrolService->listRoutes($request->getAttribute('tenant_id')))]); }

    public function createRoute(Request $request, Response $response): Response
    { return JsonResponse::success($response, $this->patrolService->createRoute($request->getAttribute('tenant_id'), (array) $request->getParsedBody())->toArray(), 201); }

    public function recordHit(Request $request, Response $response): Response
    { return JsonResponse::success($response, $this->patrolService->recordHit($request->getAttribute('tenant_id'), (array) $request->getParsedBody())->toArray(), 201); }

    public function routeHits(Request $request, Response $response, array $args): Response
    { return JsonResponse::success($response, ['hits' => array_map(fn($h) => $h->toArray(), $this->patrolService->getRouteHits($args['routeId'], $request->getQueryParams()['date'] ?? null))]); }

    public function missedPatrols(Request $request, Response $response): Response
    { return JsonResponse::success($response, ['missed' => $this->patrolService->detectMissedPatrols($request->getAttribute('tenant_id'))]); }
}
