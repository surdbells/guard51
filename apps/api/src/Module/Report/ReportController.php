<?php
declare(strict_types=1);
namespace Guard51\Module\Report;

use Guard51\Helper\JsonResponse;
use Guard51\Service\ReportService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class ReportController
{
    public function __construct(private readonly ReportService $reportService) {}

    // DARs
    public function listDARs(Request $request, Response $response): Response
    {
        $p = $request->getQueryParams();
        $dars = $this->reportService->listDARs($request->getAttribute('tenant_id'), $p['site_id'] ?? null, $p['guard_id'] ?? null, $p['status'] ?? null);
        return JsonResponse::success($response, ['reports' => array_map(fn($d) => $d->toArray(), $dars)]);
    }
    public function createDAR(Request $request, Response $response): Response
    {
        $dar = $this->reportService->createDAR($request->getAttribute('tenant_id'), (array) $request->getParsedBody());
        return JsonResponse::success($response, $dar->toArray(), 201);
    }
    public function submitDAR(Request $request, Response $response, array $args): Response
    {
        $dar = $this->reportService->submitDAR($args['id']);
        return JsonResponse::success($response, $dar->toArray());
    }
    public function reviewDAR(Request $request, Response $response, array $args): Response
    {
        $body = (array) $request->getParsedBody();
        $dar = $this->reportService->reviewDAR($args['id'], $request->getAttribute('user_id'), ($body['approve'] ?? false) === true);
        return JsonResponse::success($response, $dar->toArray());
    }

    // Custom templates
    public function listTemplates(Request $request, Response $response): Response
    {
        $templates = $this->reportService->listTemplates($request->getAttribute('tenant_id'), ($request->getQueryParams()['active'] ?? '') === 'true');
        return JsonResponse::success($response, ['templates' => array_map(fn($t) => $t->toArray(), $templates)]);
    }
    public function createTemplate(Request $request, Response $response): Response
    {
        $t = $this->reportService->createTemplate($request->getAttribute('tenant_id'), (array) $request->getParsedBody(), $request->getAttribute('user_id'));
        return JsonResponse::success($response, $t->toArray(), 201);
    }

    // Custom submissions
    public function submitCustomReport(Request $request, Response $response): Response
    {
        $sub = $this->reportService->submitCustomReport($request->getAttribute('tenant_id'), (array) $request->getParsedBody());
        return JsonResponse::success($response, $sub->toArray(), 201);
    }
    public function listSubmissions(Request $request, Response $response, array $args): Response
    {
        $subs = $this->reportService->listSubmissions($args['templateId']);
        return JsonResponse::success($response, ['submissions' => array_map(fn($s) => $s->toArray(), $subs)]);
    }

    // Watch mode
    public function logWatch(Request $request, Response $response): Response
    {
        $log = $this->reportService->logWatchMedia($request->getAttribute('tenant_id'), (array) $request->getParsedBody());
        return JsonResponse::success($response, $log->toArray(), 201);
    }
    public function watchFeed(Request $request, Response $response, array $args): Response
    {
        $logs = $this->reportService->getWatchFeed($args['siteId']);
        return JsonResponse::success($response, ['feed' => array_map(fn($l) => $l->toArray(), $logs)]);
    }
    public function recentWatchFeed(Request $request, Response $response): Response
    {
        $logs = $this->reportService->getRecentWatchFeed($request->getAttribute('tenant_id'));
        return JsonResponse::success($response, ['feed' => array_map(fn($l) => $l->toArray(), $logs)]);
    }
}
