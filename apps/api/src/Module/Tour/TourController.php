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
    public function listCheckpoints(Request $request, Response $response, array $args): Response
    {
        $cps = $this->tourService->listCheckpoints($args['siteId']);
        return JsonResponse::success($response, ['checkpoints' => array_map(fn($c) => $c->toArray(), $cps)]);
    }

    public function createCheckpoint(Request $request, Response $response, array $args): Response
    {
        $cp = $this->tourService->createCheckpoint($request->getAttribute('tenant_id'), $args['siteId'], (array) $request->getParsedBody());
        return JsonResponse::success($response, $cp->toArray(), 201);
    }

    public function updateCheckpoint(Request $request, Response $response, array $args): Response
    {
        $cp = $this->tourService->updateCheckpoint($args['id'], (array) $request->getParsedBody());
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

    public function recordScan(Request $request, Response $response, array $args): Response
    {
        $scan = $this->tourService->recordScan($args['sessionId'], (array) $request->getParsedBody());
        return JsonResponse::success($response, $scan->toArray(), 201);
    }

    public function completeTour(Request $request, Response $response, array $args): Response
    {
        $session = $this->tourService->completeTour($args['sessionId']);
        return JsonResponse::success($response, $session->toArray());
    }

    public function sessionDetail(Request $request, Response $response, array $args): Response
    {
        $data = $this->tourService->getSessionDetail($args['sessionId']);
        return JsonResponse::success($response, $data);
    }

    public function sessionsBySite(Request $request, Response $response, array $args): Response
    {
        $sessions = $this->tourService->listSessionsBySite($args['siteId']);
        return JsonResponse::success($response, ['sessions' => array_map(fn($s) => $s->toArray(), $sessions)]);
    }

    public function sessionsByGuard(Request $request, Response $response, array $args): Response
    {
        $sessions = $this->tourService->listSessionsByGuard($args['guardId']);
        return JsonResponse::success($response, ['sessions' => array_map(fn($s) => $s->toArray(), $sessions)]);
    }
}
