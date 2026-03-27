<?php
declare(strict_types=1);
namespace Guard51\Module\Dispatch;

use Guard51\Helper\JsonResponse;
use Guard51\Service\DispatchService;
use Guard51\Exception\ApiException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class DispatchController
{
    public function __construct(private readonly DispatchService $dispatchService) {}

    public function activeCalls(Request $request, Response $response): Response
    {
        $calls = $this->dispatchService->getActiveCalls($request->getAttribute('tenant_id'));
        return JsonResponse::success($response, ['calls' => array_map(fn($c) => $c->toArray(), $calls)]);
    }
    public function recentCalls(Request $request, Response $response): Response
    {
        $calls = $this->dispatchService->getRecentCalls($request->getAttribute('tenant_id'));
        return JsonResponse::success($response, ['calls' => array_map(fn($c) => $c->toArray(), $calls)]);
    }
    public function createCall(Request $request, Response $response): Response
    {
        $call = $this->dispatchService->createCall($request->getAttribute('tenant_id'), (array) $request->getParsedBody(), $request->getAttribute('user_id'));
        return JsonResponse::success($response, $call->toArray(), 201);
    }
    public function assignGuard(Request $request, Response $response, array $args): Response
    {
        $body = (array) $request->getParsedBody();
        $a = $this->dispatchService->assignGuard($args['id'], $body['guard_id'] ?? '');
        return JsonResponse::success($response, $a->toArray(), 201);
    }
    public function updateAssignment(Request $request, Response $response, array $args): Response
    {
        $body = (array) $request->getParsedBody();
        $a = $this->dispatchService->updateAssignmentStatus($args['assignmentId'], $body['action'] ?? '');
        return JsonResponse::success($response, $a->toArray());
    }
    public function resolveCall(Request $request, Response $response, array $args): Response
    {
        $body = (array) $request->getParsedBody();
        $call = $this->dispatchService->resolveCall($args['id'], $body['resolution'] ?? '');
        return JsonResponse::success($response, $call->toArray());
    }
    public function nearestGuards(Request $request, Response $response): Response
    {
        $p = $request->getQueryParams();
        if (!isset($p['lat']) || !isset($p['lng'])) throw ApiException::validation('lat and lng required.');
        $guards = $this->dispatchService->suggestNearestGuards($request->getAttribute('tenant_id'), (float) $p['lat'], (float) $p['lng']);
        return JsonResponse::success($response, ['guards' => $guards]);
    }
    public function assignments(Request $request, Response $response, array $args): Response
    {
        $assignments = $this->dispatchService->getAssignments($args['id']);
        return JsonResponse::success($response, ['assignments' => array_map(fn($a) => $a->toArray(), $assignments)]);
    }
}
