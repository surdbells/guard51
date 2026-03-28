<?php
declare(strict_types=1);
namespace Guard51\Module\Visitor;

use Guard51\Helper\JsonResponse;
use Guard51\Service\VisitorService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class VisitorController
{
    public function __construct(private readonly VisitorService $visitorService) {}

    public function checkIn(Request $request, Response $response): Response
    { return JsonResponse::success($response, $this->visitorService->checkIn($request->getAttribute('tenant_id'), (array) $request->getParsedBody(), $request->getAttribute('user_id'))->toArray(), 201); }

    public function checkOut(Request $request, Response $response, array $args): Response
    { return JsonResponse::success($response, $this->visitorService->checkOut($args['id'], $request->getAttribute('user_id'))->toArray()); }

    public function listBySite(Request $request, Response $response, array $args): Response
    { $p = $request->getQueryParams(); return JsonResponse::success($response, ['visitors' => array_map(fn($v) => $v->toArray(), $this->visitorService->listBySite($args['siteId'], $p['date'] ?? null))]); }

    public function listCheckedIn(Request $request, Response $response, array $args): Response
    { return JsonResponse::success($response, ['visitors' => array_map(fn($v) => $v->toArray(), $this->visitorService->listCheckedIn($args['siteId']))]); }

    public function search(Request $request, Response $response): Response
    { return JsonResponse::success($response, ['visitors' => array_map(fn($v) => $v->toArray(), $this->visitorService->search($request->getAttribute('tenant_id'), $request->getQueryParams()['q'] ?? ''))]); }
}
