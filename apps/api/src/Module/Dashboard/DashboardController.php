<?php

declare(strict_types=1);

namespace Guard51\Module\Dashboard;

use Guard51\Helper\JsonResponse;
use Guard51\Repository\DailySnapshotRepository;
use Guard51\Repository\GuardRepository;
use Guard51\Repository\SiteRepository;
use Guard51\Repository\ClientRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

final class DashboardController
{
    public function __construct(
        private readonly DailySnapshotRepository $snapshotRepo,
        private readonly GuardRepository $guardRepo,
        private readonly SiteRepository $siteRepo,
        private readonly ClientRepository $clientRepo,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * GET /api/v1/dashboard/stats — Current dashboard statistics
     */
    public function stats(Request $request, Response $response): Response
    {
        $tenantId = $request->getAttribute('tenant_id');

        $totalGuards = $this->guardRepo->countByTenant($tenantId);
        $activeGuards = $this->guardRepo->countActiveByTenant($tenantId);
        $totalSites = $this->siteRepo->countByTenant($tenantId);
        $totalClients = $this->clientRepo->countByTenant($tenantId);

        return JsonResponse::success($response, [
            'total_guards' => $totalGuards,
            'active_guards' => $activeGuards,
            'total_sites' => $totalSites,
            'total_clients' => $totalClients,
            'attendance_rate' => $totalGuards > 0 ? round(($activeGuards / $totalGuards) * 100, 1) : 0,
        ]);
    }

    /**
     * GET /api/v1/dashboard/snapshots — Historical daily snapshots
     */
    public function snapshots(Request $request, Response $response): Response
    {
        $tenantId = $request->getAttribute('tenant_id');
        $params = $request->getQueryParams();
        $days = min(max((int) ($params['days'] ?? 30), 7), 90);

        $snapshots = $this->snapshotRepo->findRange($tenantId, $days);

        return JsonResponse::success($response, [
            'snapshots' => array_map(fn($s) => $s->toArray(), $snapshots),
            'days' => $days,
        ]);
    }

    /**
     * GET /api/v1/dashboard/today — Today's snapshot
     */
    public function today(Request $request, Response $response): Response
    {
        $tenantId = $request->getAttribute('tenant_id');
        $today = new \DateTimeImmutable('today');
        $snapshot = $this->snapshotRepo->findByTenantAndDate($tenantId, $today);

        return JsonResponse::success($response, [
            'snapshot' => $snapshot?->toArray(),
            'date' => $today->format('Y-m-d'),
        ]);
    }
}
