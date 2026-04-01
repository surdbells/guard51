<?php
declare(strict_types=1);
namespace Guard51\Module\Tour;

use Guard51\Helper\JsonResponse;
use Guard51\Service\TourService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class TourController
{
    public function __construct(private readonly TourService $tourService) {}

    // Checkpoints
    public function listCheckpoints(Request $request, Response $response): Response
    {
        $cps = $this->tourService->listCheckpoints($request->getAttribute('siteId'));
        return JsonResponse::success($response, ['checkpoints' => array_map(fn($c) => $c->toArray(), $cps)]);
    }

    public function createCheckpoint(Request $request, Response $response): Response
    {
        $cp = $this->tourService->createCheckpoint($request->getAttribute('tenant_id'), $request->getAttribute('siteId'), (array) $request->getParsedBody());
        return JsonResponse::success($response, $cp->toArray(), 201);
    }

    public function updateCheckpoint(Request $request, Response $response): Response
    {
        $cp = $this->tourService->updateCheckpoint($request->getAttribute('id'), (array) $request->getParsedBody());
        return JsonResponse::success($response, $cp->toArray());
    }

    // Tour Sessions
    public function startTour(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();
        $session = $this->tourService->startTour(
            $request->getAttribute('tenant_id'), $body['guard_id'] ?? '', $body['site_id'] ?? '', $body['shift_id'] ?? null
        );
        return JsonResponse::success($response, $session->toArray(), 201);
    }

    public function recordScan(Request $request, Response $response): Response
    {
        $scan = $this->tourService->recordScan($request->getAttribute('sessionId'), (array) $request->getParsedBody());
        return JsonResponse::success($response, $scan->toArray(), 201);
    }

    public function completeTour(Request $request, Response $response): Response
    {
        $session = $this->tourService->completeTour($request->getAttribute('sessionId'));
        return JsonResponse::success($response, $session->toArray());
    }

    public function sessionDetail(Request $request, Response $response): Response
    {
        $data = $this->tourService->getSessionDetail($request->getAttribute('sessionId'));
        return JsonResponse::success($response, $data);
    }

    public function sessionsBySite(Request $request, Response $response): Response
    {
        $sessions = $this->tourService->listSessionsBySite($request->getAttribute('siteId'));
        return JsonResponse::success($response, ['sessions' => array_map(fn($s) => $s->toArray(), $sessions)]);
    }

    public function sessionsByGuard(Request $request, Response $response): Response
    {
        $sessions = $this->tourService->listSessionsByGuard($request->getAttribute('guardId'));
        return JsonResponse::success($response, ['sessions' => array_map(fn($s) => $s->toArray(), $sessions)]);
    }
}
