<?php
declare(strict_types=1);
namespace Guard51\Module\Task;

use Guard51\Helper\JsonResponse;
use Guard51\Service\TaskService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class TaskController
{
    public function __construct(private readonly TaskService $taskService) {}

    public function list(Request $request, Response $response): Response
    {
        $p = $request->getQueryParams();
        $tasks = $this->taskService->listByTenant($request->getAttribute('tenant_id'), $p['status'] ?? null);
        return JsonResponse::success($response, ['tasks' => array_map(fn($t) => $t->toArray(), $tasks)]);
    }
    public function create(Request $request, Response $response): Response
    {
        $task = $this->taskService->createTask($request->getAttribute('tenant_id'), (array) $request->getParsedBody(), $request->getAttribute('user_id'));
        return JsonResponse::success($response, $task->toArray(), 201);
    }
    public function updateStatus(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();
        $task = $this->taskService->updateStatus($request->getAttribute('id'), $body['action'] ?? '', $body['notes'] ?? null);
        return JsonResponse::success($response, $task->toArray());
    }
    public function byGuard(Request $request, Response $response): Response
    {
        $tasks = $this->taskService->listByGuard($request->getAttribute('guardId'));
        return JsonResponse::success($response, ['tasks' => array_map(fn($t) => $t->toArray(), $tasks)]);
    }
    public function bySite(Request $request, Response $response): Response
    {
        $tasks = $this->taskService->listBySite($request->getAttribute('siteId'));
        return JsonResponse::success($response, ['tasks' => array_map(fn($t) => $t->toArray(), $tasks)]);
    }
    public function overdue(Request $request, Response $response): Response
    {
        $tasks = $this->taskService->findOverdue($request->getAttribute('tenant_id'));
        return JsonResponse::success($response, ['tasks' => array_map(fn($t) => $t->toArray(), $tasks)]);
    }
}
