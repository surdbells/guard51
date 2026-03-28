<?php
declare(strict_types=1);
namespace Guard51\Module\Analytics;

use Guard51\Helper\JsonResponse;
use Guard51\Service\AnalyticsService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class AnalyticsController
{
    public function __construct(private readonly AnalyticsService $analytics) {}

    public function kpis(Request $request, Response $response): Response
    { return JsonResponse::success($response, $this->analytics->getTenantKPIs($request->getAttribute('tenant_id'))); }

    public function guardPerformance(Request $request, Response $response, array $args): Response
    { return JsonResponse::success($response, ['performance' => array_map(fn($p) => $p->toArray(), $this->analytics->getGuardPerformance($args['guardId']))]); }
}
