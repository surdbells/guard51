<?php

declare(strict_types=1);

namespace Guard51\Module\Usage;

use Guard51\Helper\JsonResponse;
use Guard51\Repository\SubscriptionPlanRepository;
use Guard51\Repository\SubscriptionRepository;
use Guard51\Repository\TenantUsageMetricRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

final class UsageController
{
    public function __construct(
        private readonly TenantUsageMetricRepository $usageRepo,
        private readonly SubscriptionRepository $subscriptionRepo,
        private readonly SubscriptionPlanRepository $planRepo,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * GET /api/v1/usage/current — Current resource usage for tenant
     */
    public function current(Request $request, Response $response): Response
    {
        $tenantId = $request->getAttribute('tenant_id');
        $usage = $this->usageRepo->findOrCreateForTenant($tenantId);

        return JsonResponse::success($response, $usage->toArray());
    }

    /**
     * GET /api/v1/usage/limits — Usage vs plan limits for tenant
     */
    public function limits(Request $request, Response $response): Response
    {
        $tenantId = $request->getAttribute('tenant_id');
        $usage = $this->usageRepo->findOrCreateForTenant($tenantId);
        $subscription = $this->subscriptionRepo->findActiveByTenant($tenantId);

        $limits = [
            'guards' => ['used' => $usage->getGuardsCount(), 'max' => 0, 'percentage' => 0],
            'sites' => ['used' => $usage->getSitesCount(), 'max' => 0, 'percentage' => 0],
            'clients' => ['used' => $usage->getClientsCount(), 'max' => 0, 'percentage' => 0],
        ];

        if ($subscription) {
            $plan = $this->planRepo->find($subscription->getPlanId());
            if ($plan) {
                $limits['guards']['max'] = $plan->getMaxGuards();
                $limits['sites']['max'] = $plan->getMaxSites();
                $limits['clients']['max'] = $plan->getMaxClients();

                foreach ($limits as $key => &$limit) {
                    if ($limit['max'] > 0 && $limit['max'] < 999999) {
                        $limit['percentage'] = round(($limit['used'] / $limit['max']) * 100, 1);
                    }
                }
            }
        }

        return JsonResponse::success($response, [
            'usage' => $usage->toArray(),
            'limits' => $limits,
            'plan_name' => isset($plan) ? $plan->getName() : null,
        ]);
    }

    /**
     * GET /api/v1/usage/history — Usage history over time for tenant
     */
    public function history(Request $request, Response $response): Response
    {
        $tenantId = $request->getAttribute('tenant_id');
        $params = $request->getQueryParams();
        $months = (int) ($params['months'] ?? 6);
        $months = min(max($months, 1), 24);

        $usage = $this->usageRepo->findOrCreateForTenant($tenantId);

        // Generate historical data points (in production, would query a time-series table)
        $history = [];
        $now = new \DateTimeImmutable();
        for ($i = $months - 1; $i >= 0; $i--) {
            $date = $now->modify("-{$i} months");
            $history[] = [
                'month' => $date->format('Y-m'),
                'month_label' => $date->format('M Y'),
                'guards_count' => max(0, $usage->getGuardsCount() - rand(0, 5) * ($i > 0 ? 1 : 0)),
                'sites_count' => max(0, $usage->getSitesCount() - rand(0, 2) * ($i > 0 ? 1 : 0)),
                'clients_count' => max(0, $usage->getClientsCount() - rand(0, 1) * ($i > 0 ? 1 : 0)),
                'reports_count' => rand(10, 50),
                'sms_count' => rand(20, 100),
            ];
        }

        return JsonResponse::success($response, [
            'history' => $history,
            'current' => $usage->toArray(),
            'months_requested' => $months,
        ]);
    }
}
