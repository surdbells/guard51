<?php

declare(strict_types=1);

namespace Guard51\Module\TimeClock;

use Guard51\Entity\ClockMethod;
use Guard51\Helper\JsonResponse;
use Guard51\Service\TimeClockService;
use Guard51\Exception\ApiException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class TimeClockController
{
    public function __construct(private readonly TimeClockService $clockService) {}

    public function clockIn(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();
        if (empty($body['guard_id']) || empty($body['site_id']) || !isset($body['lat']) || !isset($body['lng'])) {
            throw ApiException::validation('guard_id, site_id, lat, lng are required.');
        }
        $clock = $this->clockService->clockIn(
            $request->getAttribute('tenant_id'), $body['guard_id'], $body['site_id'],
            (float) $body['lat'], (float) $body['lng'],
            ClockMethod::from($body['method'] ?? 'web_portal'),
            $body['shift_id'] ?? null, $body['photo_url'] ?? null
        );
        return JsonResponse::success($response, $clock->toArray(), 201);
    }

    public function clockOut(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();
        if (empty($body['guard_id']) || !isset($body['lat']) || !isset($body['lng'])) {
            throw ApiException::validation('guard_id, lat, lng are required.');
        }
        $clock = $this->clockService->clockOut(
            $body['guard_id'], (float) $body['lat'], (float) $body['lng'],
            ClockMethod::from($body['method'] ?? 'web_portal')
        );
        return JsonResponse::success($response, $clock->toArray());
    }

    public function status(Request $request, Response $response): Response
    {
        $guardId = $request->getQueryParams()['guard_id'] ?? '';
        $clock = $this->clockService->getActiveClockByGuard($guardId);
        return JsonResponse::success($response, ['clock' => $clock?->toArray(), 'is_clocked_in' => $clock !== null]);
    }

    public function activeBySite(Request $request, Response $response, array $args): Response
    {
        $clocks = $this->clockService->getActiveBySite($args['siteId']);
        return JsonResponse::success($response, ['clocks' => array_map(fn($c) => $c->toArray(), $clocks)]);
    }

    public function history(Request $request, Response $response): Response
    {
        $p = $request->getQueryParams();
        $clocks = $this->clockService->getClockHistory($p['guard_id'] ?? '', $p['start_date'] ?? '-30 days', $p['end_date'] ?? 'now');
        return JsonResponse::success($response, ['records' => array_map(fn($c) => $c->toArray(), $clocks)]);
    }

    // Attendance
    public function attendanceByDate(Request $request, Response $response): Response
    {
        $date = $request->getQueryParams()['date'] ?? (new \DateTimeImmutable())->format('Y-m-d');
        $records = $this->clockService->getAttendanceByDate($request->getAttribute('tenant_id'), $date);
        return JsonResponse::success($response, ['records' => array_map(fn($r) => $r->toArray(), $records), 'date' => $date]);
    }

    public function attendanceByGuard(Request $request, Response $response, array $args): Response
    {
        $p = $request->getQueryParams();
        $records = $this->clockService->getAttendanceByGuard($args['guardId'], $p['start_date'] ?? '-30 days', $p['end_date'] ?? 'now');
        return JsonResponse::success($response, ['records' => array_map(fn($r) => $r->toArray(), $records)]);
    }

    public function unreconciled(Request $request, Response $response): Response
    {
        $records = $this->clockService->getUnreconciled($request->getAttribute('tenant_id'));
        return JsonResponse::success($response, ['records' => array_map(fn($r) => $r->toArray(), $records)]);
    }

    public function reconcile(Request $request, Response $response, array $args): Response
    {
        $body = (array) $request->getParsedBody();
        $record = $this->clockService->reconcile($args['id'], $request->getAttribute('user_id'), $body['status'] ?? 'present', $body['notes'] ?? null);
        return JsonResponse::success($response, $record->toArray());
    }

    /**
     * POST /api/v1/attendance/bulk-reconcile — Auto-approve records below late threshold
     */
    public function bulkReconcile(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();
        $threshold = (int) ($body['late_threshold_minutes'] ?? 15);
        $reconciled = $this->clockService->bulkReconcile(
            $request->getAttribute('tenant_id'),
            $request->getAttribute('user_id'),
            $threshold
        );
        return JsonResponse::success($response, [
            'reconciled_count' => count($reconciled),
            'threshold_minutes' => $threshold,
        ]);
    }

    // Breaks
    public function listBreakConfigs(Request $request, Response $response): Response
    {
        $configs = $this->clockService->listBreakConfigs($request->getAttribute('tenant_id'));
        return JsonResponse::success($response, ['configs' => array_map(fn($c) => $c->toArray(), $configs)]);
    }

    public function createBreakConfig(Request $request, Response $response): Response
    {
        $config = $this->clockService->createBreakConfig($request->getAttribute('tenant_id'), (array) $request->getParsedBody());
        return JsonResponse::success($response, $config->toArray(), 201);
    }

    public function startBreak(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();
        $log = $this->clockService->startBreak($body['time_clock_id'] ?? '', $body['break_config_id'] ?? '');
        return JsonResponse::success($response, $log->toArray(), 201);
    }

    public function endBreak(Request $request, Response $response, array $args): Response
    {
        $log = $this->clockService->endBreak($args['id']);
        return JsonResponse::success($response, $log->toArray());
    }
}
