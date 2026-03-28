<?php
declare(strict_types=1);
namespace Guard51\Service;

use Guard51\Entity\GuardPerformanceIndex;
use Guard51\Repository\GuardPerformanceIndexRepository;
use Psr\Log\LoggerInterface;

final class AnalyticsService
{
    public function __construct(
        private readonly GuardPerformanceIndexRepository $perfRepo,
        private readonly LoggerInterface $logger,
    ) {}

    public function calculateGuardPerformance(string $tenantId, string $guardId, string $month): GuardPerformanceIndex
    {
        $perf = new GuardPerformanceIndex();
        $perf->setTenantId($tenantId)->setGuardId($guardId)->setPeriodMonth($month);
        // In production: query time_clocks, tour_sessions, reports, incidents
        $perf->setPunctualityScore(85.0)->setTourComplianceScore(90.0)
            ->setReportCompletionScore(95.0)->setIncidentResponseScore(80.0)
            ->calculateOverall();
        $this->perfRepo->save($perf);
        return $perf;
    }

    public function getGuardPerformance(string $guardId): array
    {
        return $this->perfRepo->findBy(['guardId' => $guardId], ['periodMonth' => 'DESC']);
    }

    public function getTenantKPIs(string $tenantId): array
    {
        // Aggregated KPIs — in production these query real data
        return [
            'avg_response_time_min' => 8.5,
            'tour_compliance_rate' => 92.3,
            'incident_resolution_hours' => 4.2,
            'guard_punctuality_rate' => 88.7,
            'active_guards' => 0,
            'active_sites' => 0,
            'open_incidents' => 0,
            'overdue_tasks' => 0,
        ];
    }
}
