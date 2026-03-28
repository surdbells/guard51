<?php
declare(strict_types=1);
namespace Guard51\Module\Payroll;

use Guard51\Helper\JsonResponse;
use Guard51\Service\PayrollService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class PayrollController
{
    public function __construct(private readonly PayrollService $payrollService) {}

    public function listPeriods(Request $request, Response $response): Response
    {
        $periods = $this->payrollService->listPeriods($request->getAttribute('tenant_id'));
        return JsonResponse::success($response, ['periods' => array_map(fn($p) => $p->toArray(), $periods)]);
    }
    public function createPeriod(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();
        $period = $this->payrollService->createPeriod($request->getAttribute('tenant_id'), $body['period_start'] ?? '', $body['period_end'] ?? '');
        return JsonResponse::success($response, $period->toArray(), 201);
    }
    public function periodDetail(Request $request, Response $response, array $args): Response
    {
        return JsonResponse::success($response, $this->payrollService->getPeriodDetail($args['id']));
    }
    public function addItem(Request $request, Response $response, array $args): Response
    {
        $item = $this->payrollService->addPayrollItem($args['id'], (array) $request->getParsedBody());
        return JsonResponse::success($response, $item->toArray(), 201);
    }
    public function calculateFromTimeClock(Request $request, Response $response, array $args): Response
    {
        $body = (array) $request->getParsedBody();
        $items = $this->payrollService->calculateFromTimeClock($args['id'], (float) ($body['default_rate'] ?? 500));
        return JsonResponse::success($response, ['calculated' => count($items), 'items' => array_map(fn($i) => $i->toArray(), $items)]);
    }
    public function approve(Request $request, Response $response, array $args): Response
    {
        $period = $this->payrollService->approvePeriod($args['id'], $request->getAttribute('user_id'));
        return JsonResponse::success($response, $period->toArray());
    }
    public function guardPayslips(Request $request, Response $response, array $args): Response
    {
        $slips = $this->payrollService->getGuardPayslips($args['guardId']);
        return JsonResponse::success($response, ['payslips' => array_map(fn($s) => $s->toArray(), $slips)]);
    }
    public function listRates(Request $request, Response $response): Response
    {
        $rates = $this->payrollService->listRates($request->getAttribute('tenant_id'));
        return JsonResponse::success($response, ['rates' => array_map(fn($r) => $r->toArray(), $rates)]);
    }
    public function createRate(Request $request, Response $response): Response
    {
        $rate = $this->payrollService->createRate($request->getAttribute('tenant_id'), (array) $request->getParsedBody());
        return JsonResponse::success($response, $rate->toArray(), 201);
    }
}
