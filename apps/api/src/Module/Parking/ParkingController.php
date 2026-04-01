<?php
declare(strict_types=1);
namespace Guard51\Module\Parking;

use Guard51\Helper\JsonResponse;
use Guard51\Service\ParkingService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class ParkingController
{
    public function __construct(private readonly ParkingService $parkingService) {}

    public function listAreas(Request $request, Response $response): Response
    { return JsonResponse::success($response, ['areas' => array_map(fn($a) => $a->toArray(), $this->parkingService->listAreas($request->getAttribute('tenant_id')))]); }

    public function createArea(Request $request, Response $response): Response
    { return JsonResponse::success($response, $this->parkingService->createArea($request->getAttribute('tenant_id'), (array) $request->getParsedBody())->toArray(), 201); }

    public function createLot(Request $request, Response $response): Response
    { return JsonResponse::success($response, $this->parkingService->createLot($request->getAttribute('areaId'), (array) $request->getParsedBody())->toArray(), 201); }

    public function logEntry(Request $request, Response $response): Response
    { return JsonResponse::success($response, $this->parkingService->logEntry($request->getAttribute('tenant_id'), (array) $request->getParsedBody(), $request->getAttribute('user_id'))->toArray(), 201); }

    public function logExit(Request $request, Response $response): Response
    { return JsonResponse::success($response, $this->parkingService->logExit($request->getAttribute('id'))->toArray()); }

    public function listParked(Request $request, Response $response): Response
    { return JsonResponse::success($response, ['vehicles' => array_map(fn($v) => $v->toArray(), $this->parkingService->listParked($request->getAttribute('siteId'))), 'count' => $this->parkingService->countParked($request->getAttribute('siteId'))]); }

    public function reportIncident(Request $request, Response $response): Response
    { return JsonResponse::success($response, $this->parkingService->reportIncident($request->getAttribute('tenant_id'), (array) $request->getParsedBody(), $request->getAttribute('user_id'))->toArray(), 201); }

    public function listIncidentTypes(Request $request, Response $response): Response
    { return JsonResponse::success($response, ['types' => array_map(fn($t) => $t->toArray(), $this->parkingService->listIncidentTypes($request->getAttribute('tenant_id')))]); }

    public function createIncidentType(Request $request, Response $response): Response
    { return JsonResponse::success($response, $this->parkingService->createIncidentType($request->getAttribute('tenant_id'), (array) $request->getParsedBody())->toArray(), 201); }
}
