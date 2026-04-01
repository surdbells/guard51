<?php
declare(strict_types=1);
namespace Guard51\Module\Incident;

use Guard51\Helper\JsonResponse;
use Guard51\Helper\HandlesFileUploads;
use Guard51\Service\FileStorageService;
use Guard51\Service\IncidentService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class IncidentController
{
    use HandlesFileUploads;

    public function __construct(
        private readonly IncidentService $incidentService,
        private readonly FileStorageService $storage,
    ) {}

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
        $tenantId = $request->getAttribute('tenant_id');
        $body = (array) $request->getParsedBody();

        // Handle evidence file uploads
        $attachments = $this->handleMultipleUploads($request, $this->storage, $tenantId, 'incidents');
        if (!empty($attachments)) {
            $body['attachments'] = $attachments;
        }

        $ir = $this->incidentService->createIncident($tenantId, $body);
        return JsonResponse::success($response, $ir->toArray(), 201);
    }
    public function updateStatus(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();
        $ir = $this->incidentService->updateStatus($request->getAttribute('id'), $body['status'] ?? '');
        return JsonResponse::success($response, $ir->toArray());
    }
    public function resolve(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();
        $ir = $this->incidentService->resolve($request->getAttribute('id'), $request->getAttribute('user_id'), $body['resolution'] ?? '');
        return JsonResponse::success($response, $ir->toArray());
    }
    public function escalate(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();
        $ir = $this->incidentService->escalate($request->getAttribute('id'), $body['escalated_to'] ?? '', $request->getAttribute('user_id'), $body['reason'] ?? '');
        return JsonResponse::success($response, $ir->toArray());
    }
    public function escalations(Request $request, Response $response): Response
    {
        $escs = $this->incidentService->getEscalations($request->getAttribute('id'));
        return JsonResponse::success($response, ['escalations' => array_map(fn($e) => $e->toArray(), $escs)]);
    }
}
