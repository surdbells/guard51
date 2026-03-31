<?php
declare(strict_types=1);
namespace Guard51\Command;

use Doctrine\ORM\EntityManagerInterface;
use Guard51\Entity\DailySnapshot;
use Guard51\Entity\Tenant;
use Guard51\Repository\GuardRepository;
use Guard51\Repository\SiteRepository;
use Guard51\Repository\IncidentReportRepository;
use Guard51\Repository\TimeClockRepository;

final class GenerateDailySnapshotsCommand
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly GuardRepository $guardRepo,
        private readonly SiteRepository $siteRepo,
    ) {}

    public function execute(): int
    {
        $tenants = $this->em->getRepository(Tenant::class)->findAll();
        $today = new \DateTimeImmutable('today');
        $count = 0;

        foreach ($tenants as $tenant) {
            $tid = $tenant->getId();
            // Skip if snapshot already exists
            $existing = $this->em->getRepository(DailySnapshot::class)->findOneBy([
                'tenantId' => $tid, 'snapshotDate' => $today
            ]);
            if ($existing) continue;

            $snapshot = new DailySnapshot();
            $snapshot->setTenantId($tid);
            $snapshot->setSnapshotDate($today);
            $snapshot->setTotalGuards($this->guardRepo->countByTenant($tid));
            $snapshot->setTotalSites($this->siteRepo->countByTenant($tid));
            $snapshot->setGuardsOnDuty($this->guardRepo->countActiveByTenant($tid));

            $this->em->persist($snapshot);
            $count++;
        }
        $this->em->flush();
        echo "Generated {$count} daily snapshots.\n";
        return 0;
    }
}
