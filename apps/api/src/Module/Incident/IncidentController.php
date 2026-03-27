<?php
declare(strict_types=1);
namespace Guard51\Module\Incident;

use Guard51\Helper\JsonResponse;
use Guard51\Service\IncidentService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class IncidentController
{
    public function __construct(private readonly IncidentService $incidentService) {}

    public function list(Request $request, Response $response): Response
    {
        $p = $request->getQueryParams();
        $incidents = $this->incidentService->listFiltered($request->getAttribute('tenant_id'), $p['site_id'] ?? null, $p['severity'] ?? null, $p['status'] ?? null);
        return JsonResponse::success($response, ['incidents' => array_map(fn($i) => $i->toArray(), $incidents)]);
    }
    public function active(Request $request, Response $response): Response
    {
        $incidents = $this->incidentService->listActive($request->getAttribute('tenant_id'));
        return JsonResponse::success($response, ['incidents' => array_map(fn($i) => $i->toArray(), $incidents)]);
    }
    public function create(Request $request, Response $response): Response
    {
        $ir = $this->incidentService->createIncident($request->getAttribute('tenant_id'), (array) $request->getParsedBody());
        return JsonResponse::success($response, $ir->toArray(), 201);
    }
    public function updateStatus(Request $request, Response $response, array $args): Response
    {
        $body = (array) $request->getParsedBody();
        $ir = $this->incidentService->updateStatus($args['id'], $body['status'] ?? '');
        return JsonResponse::success($response, $ir->toArray());
    }
    public function resolve(Request $request, Response $response, array $args): Response
    {
        $body = (array) $request->getParsedBody();
        $ir = $this->incidentService->resolve($args['id'], $request->getAttribute('user_id'), $body['resolution'] ?? '');
        return JsonResponse::success($response, $ir->toArray());
    }
    public function escalate(Request $request, Response $response, array $args): Response
    {
        $body = (array) $request->getParsedBody();
        $ir = $this->incidentService->escalate($args['id'], $body['escalated_to'] ?? '', $request->getAttribute('user_id'), $body['reason'] ?? '');
        return JsonResponse::success($response, $ir->toArray());
    }
    public function escalations(Request $request, Response $response, array $args): Response
    {
        $escs = $this->incidentService->getEscalations($args['id']);
        return JsonResponse::success($response, ['escalations' => array_map(fn($e) => $e->toArray(), $escs)]);
    }
}
