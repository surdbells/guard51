<?php

declare(strict_types=1);

namespace Guard51\Module\Scheduling;

use Guard51\Helper\JsonResponse;
use Guard51\Service\ShiftService;
use Guard51\Exception\ApiException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class ShiftController
{
    public function __construct(private readonly ShiftService $shiftService) {}

    // Templates
    public function listTemplates(Request $request, Response $response): Response
    {
        $tenantId = $request->getAttribute('tenant_id');
        $templates = $this->shiftService->listTemplates($tenantId, ($request->getQueryParams()['active'] ?? '') === 'true');
        return JsonResponse::success($response, ['templates' => array_map(fn($t) => $t->toArray(), $templates)]);
    }

    public function createTemplate(Request $request, Response $response): Response
    {
        $t = $this->shiftService->createTemplate($request->getAttribute('tenant_id'), (array) $request->getParsedBody());
        return JsonResponse::success($response, $t->toArray(), 201);
    }

    public function updateTemplate(Request $request, Response $response): Response
    {
        $t = $this->shiftService->updateTemplate($request->getAttribute('id'), (array) $request->getParsedBody());
        return JsonResponse::success($response, $t->toArray());
    }

    // Shifts
    public function listShifts(Request $request, Response $response): Response
    {
        $p = $request->getQueryParams();
        $shifts = $this->shiftService->getShifts(
            $request->getAttribute('tenant_id'),
            $p['start_date'] ?? (new \DateTimeImmutable('monday this week'))->format('Y-m-d'),
            $p['end_date'] ?? (new \DateTimeImmutable('sunday this week'))->format('Y-m-d'),
            $p['site_id'] ?? null, $p['guard_id'] ?? null
        );
        return JsonResponse::success($response, ['shifts' => array_map(fn($s) => $s->toArray(), $shifts), 'total' => count($shifts)]);
    }

    public function createShift(Request $request, Response $response): Response
    {
        $shift = $this->shiftService->createShift($request->getAttribute('tenant_id'), (array) $request->getParsedBody(), $request->getAttribute('user_id'));
        return JsonResponse::success($response, $shift->toArray(), 201);
    }

    public function updateShift(Request $request, Response $response): Response
    {
        $shift = $this->shiftService->updateShift($request->getAttribute('id'), (array) $request->getParsedBody());
        return JsonResponse::success($response, $shift->toArray());
    }

    public function cancelShift(Request $request, Response $response): Response
    {
        $shift = $this->shiftService->cancelShift($request->getAttribute('id'));
        return JsonResponse::success($response, $shift->toArray());
    }

    public function bulkGenerate(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();
        if (empty($body['template_id']) || empty($body['start_date']) || empty($body['end_date'])) {
            throw ApiException::validation('template_id, start_date, end_date are required.');
        }
        $shifts = $this->shiftService->bulkGenerate(
            $request->getAttribute('tenant_id'), $body['template_id'],
            $body['start_date'], $body['end_date'], $request->getAttribute('user_id'),
            $body['site_id'] ?? null
        );
        return JsonResponse::success($response, ['created' => count($shifts), 'shifts' => array_map(fn($s) => $s->toArray(), $shifts)], 201);
    }

    public function publishShifts(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();
        $count = $this->shiftService->publishShifts($request->getAttribute('tenant_id'), $body['shift_ids'] ?? []);
        return JsonResponse::success($response, ['published' => $count]);
    }

    public function confirmShift(Request $request, Response $response): Response
    {
        $shift = $this->shiftService->confirmShift($request->getAttribute('id'), $request->getAttribute('user_id'));
        return JsonResponse::success($response, $shift->toArray());
    }

    public function openShifts(Request $request, Response $response): Response
    {
        $shifts = $this->shiftService->getOpenShifts($request->getAttribute('tenant_id'));
        return JsonResponse::success($response, ['shifts' => array_map(fn($s) => $s->toArray(), $shifts)]);
    }

    public function claimShift(Request $request, Response $response): Response
    {
        $shift = $this->shiftService->claimOpenShift($request->getAttribute('id'), $request->getAttribute('user_id'));
        return JsonResponse::success($response, $shift->toArray());
    }

    // Swap Requests
    public function listSwapRequests(Request $request, Response $response): Response
    {
        $reqs = $this->shiftService->listSwapRequests($request->getAttribute('tenant_id'));
        return JsonResponse::success($response, ['swap_requests' => array_map(fn($r) => $r->toArray(), $reqs)]);
    }

    public function createSwapRequest(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();
        $body['requesting_guard_id'] = $body['requesting_guard_id'] ?? $request->getAttribute('user_id');
        $req = $this->shiftService->createSwapRequest($request->getAttribute('tenant_id'), $body);
        return JsonResponse::success($response, $req->toArray(), 201);
    }

    public function approveSwap(Request $request, Response $response): Response
    {
        $req = $this->shiftService->approveSwap($request->getAttribute('id'), $request->getAttribute('user_id'));
        return JsonResponse::success($response, $req->toArray());
    }

    public function rejectSwap(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();
        $req = $this->shiftService->rejectSwap($request->getAttribute('id'), $request->getAttribute('user_id'), $body['notes'] ?? null);
        return JsonResponse::success($response, $req->toArray());
    }

    /**
     * GET /api/v1/shifts/available-guards — Find guards available for a time slot + optional skill
     */
    public function availableGuards(Request $request, Response $response): Response
    {
        $p = $request->getQueryParams();
        if (empty($p['start_time']) || empty($p['end_time'])) {
            throw ApiException::validation('start_time and end_time query params required.');
        }
        $guards = $this->shiftService->findAvailableGuards(
            $request->getAttribute('tenant_id'),
            $p['start_time'], $p['end_time'],
            $p['skill_id'] ?? null
        );
        return JsonResponse::success($response, ['guards' => $guards]);
    }
}
