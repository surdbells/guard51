<?php
declare(strict_types=1);
namespace Guard51\Module\Panic;

use Guard51\Helper\JsonResponse;
use Guard51\Service\PanicService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class PanicController
{
    public function __construct(private readonly PanicService $panicService) {}

    /** POST /api/v1/panic/trigger */
    public function trigger(Request $request, Response $response): Response
    {
        $alert = $this->panicService->triggerPanic($request->getAttribute('tenant_id'), (array) $request->getParsedBody());
        return JsonResponse::success($response, $alert->toArray(), 201);
    }

    /** GET /api/v1/panic/active */
    public function active(Request $request, Response $response): Response
    {
        $alerts = $this->panicService->getActiveAlerts($request->getAttribute('tenant_id'));
        return JsonResponse::success($response, ['alerts' => array_map(fn($a) => $a->toArray(), $alerts)]);
    }

    /** GET /api/v1/panic/recent */
    public function recent(Request $request, Response $response): Response
    {
        $hours = (int) ($request->getQueryParams()['hours'] ?? 24);
        $alerts = $this->panicService->getRecentAlerts($request->getAttribute('tenant_id'), $hours);
        return JsonResponse::success($response, ['alerts' => array_map(fn($a) => $a->toArray(), $alerts)]);
    }

    /** POST /api/v1/panic/{id}/acknowledge */
    public function acknowledge(Request $request, Response $response): Response
    {
        $alert = $this->panicService->acknowledge($request->getAttribute('id'), $request->getAttribute('user_id'));
        return JsonResponse::success($response, $alert->toArray());
    }

    /** POST /api/v1/panic/{id}/responding */
    public function responding(Request $request, Response $response): Response
    {
        $alert = $this->panicService->markResponding($request->getAttribute('id'));
        return JsonResponse::success($response, $alert->toArray());
    }

    /** POST /api/v1/panic/{id}/resolve */
    public function resolve(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();
        $alert = $this->panicService->resolve($request->getAttribute('id'), $request->getAttribute('user_id'), $body['notes'] ?? null);
        return JsonResponse::success($response, $alert->toArray());
    }

    /** POST /api/v1/panic/{id}/false-alarm */
    public function falseAlarm(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();
        $alert = $this->panicService->markFalseAlarm($request->getAttribute('id'), $request->getAttribute('user_id'), $body['notes'] ?? null);
        return JsonResponse::success($response, $alert->toArray());
    }
}
