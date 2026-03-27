<?php
declare(strict_types=1);
namespace Guard51\Module\Tracking;

use Guard51\Helper\JsonResponse;
use Guard51\Service\LocationService;
use Guard51\Exception\ApiException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class TrackingController
{
    public function __construct(private readonly LocationService $locationService) {}

    /** POST /api/v1/tracking/location — Record a GPS ping */
    public function recordLocation(Request $request, Response $response): Response
    {
        $loc = $this->locationService->recordLocation($request->getAttribute('tenant_id'), (array) $request->getParsedBody());
        return JsonResponse::success($response, $loc->toArray(), 201);
    }

    /** POST /api/v1/tracking/batch — Batch record GPS pings */
    public function recordBatch(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();
        $count = $this->locationService->recordBatch($request->getAttribute('tenant_id'), $body['locations'] ?? []);
        return JsonResponse::success($response, ['recorded' => $count]);
    }

    /** GET /api/v1/tracking/live — Active guard locations */
    public function liveLocations(Request $request, Response $response): Response
    {
        $locations = $this->locationService->getActiveGuardLocations($request->getAttribute('tenant_id'));
        return JsonResponse::success($response, ['guards' => $locations]);
    }

    /** GET /api/v1/tracking/guard/{guardId}/latest — Latest location for a guard */
    public function latestLocation(Request $request, Response $response, array $args): Response
    {
        $loc = $this->locationService->getLatestLocation($args['guardId']);
        return JsonResponse::success($response, ['location' => $loc?->toArray()]);
    }

    /** GET /api/v1/tracking/guard/{guardId}/path — Path replay */
    public function guardPath(Request $request, Response $response, array $args): Response
    {
        $p = $request->getQueryParams();
        $path = $this->locationService->getPath($args['guardId'], $p['start'] ?? '-12 hours', $p['end'] ?? 'now');
        return JsonResponse::success($response, ['path' => $path, 'points' => count($path)]);
    }

    // ── Geofence Alerts ──────────────────────────────

    /** GET /api/v1/tracking/geofence-alerts */
    public function geofenceAlerts(Request $request, Response $response): Response
    {
        $active = $this->locationService->getActiveGeofenceAlerts($request->getAttribute('tenant_id'));
        return JsonResponse::success($response, ['alerts' => array_map(fn($a) => $a->toArray(), $active)]);
    }

    /** GET /api/v1/tracking/geofence-alerts/recent */
    public function recentGeofenceAlerts(Request $request, Response $response): Response
    {
        $hours = (int) ($request->getQueryParams()['hours'] ?? 24);
        $alerts = $this->locationService->getRecentGeofenceAlerts($request->getAttribute('tenant_id'), $hours);
        return JsonResponse::success($response, ['alerts' => array_map(fn($a) => $a->toArray(), $alerts)]);
    }

    /** POST /api/v1/tracking/geofence-alerts/{id}/acknowledge */
    public function acknowledgeGeofenceAlert(Request $request, Response $response, array $args): Response
    {
        $alert = $this->locationService->acknowledgeGeofenceAlert($args['id'], $request->getAttribute('user_id'));
        return JsonResponse::success($response, $alert->toArray());
    }

    // ── Idle Alerts ──────────────────────────────────

    /** GET /api/v1/tracking/idle-alerts */
    public function idleAlerts(Request $request, Response $response): Response
    {
        $active = $this->locationService->getActiveIdleAlerts($request->getAttribute('tenant_id'));
        return JsonResponse::success($response, ['alerts' => array_map(fn($a) => $a->toArray(), $active)]);
    }

    /** POST /api/v1/tracking/idle-alerts/{id}/acknowledge */
    public function acknowledgeIdleAlert(Request $request, Response $response, array $args): Response
    {
        $alert = $this->locationService->acknowledgeIdleAlert($args['id'], $request->getAttribute('user_id'));
        return JsonResponse::success($response, $alert->toArray());
    }

    /** POST /api/v1/tracking/detect-idle — Run idle detection (called by cron/worker) */
    public function detectIdle(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();
        $threshold = (int) ($body['threshold_minutes'] ?? 30);
        $alerts = $this->locationService->detectIdleGuards($request->getAttribute('tenant_id'), $threshold);
        return JsonResponse::success($response, ['detected' => count($alerts)]);
    }
}
