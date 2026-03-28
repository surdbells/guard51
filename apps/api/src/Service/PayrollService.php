<?php
declare(strict_types=1);
namespace Guard51\Service;

use Guard51\Entity\PayRateAppliesTo;
use Guard51\Entity\PayRateMultiplier;
use Guard51\Entity\PayrollItem;
use Guard51\Entity\PayrollPeriod;
use Guard51\Entity\PayrollStatus;
use Guard51\Entity\Payslip;
use Guard51\Exception\ApiException;
use Guard51\Repository\PayRateMultiplierRepository;
use Guard51\Repository\PayrollItemRepository;
use Guard51\Repository\PayrollPeriodRepository;
use Guard51\Repository\PayslipRepository;
use Guard51\Repository\TimeClockRepository;
use Psr\Log\LoggerInterface;

final class PayrollService
{
    public function __construct(
        private readonly PayrollPeriodRepository $periodRepo,
        private readonly PayrollItemRepository $itemRepo,
        private readonly PayRateMultiplierRepository $rateRepo,
        private readonly PayslipRepository $slipRepo,
        private readonly TimeClockRepository $clockRepo,
        private readonly LoggerInterface $logger,
    ) {}

    public function createPeriod(string $tenantId, string $startDate, string $endDate): PayrollPeriod
    {
        $period = new PayrollPeriod();
        $period->setTenantId($tenantId)
            ->setPeriodStart(new \DateTimeImmutable($startDate))
            ->setPeriodEnd(new \DateTimeImmutable($endDate));
        $this->periodRepo->save($period);
        return $period;
    }

    public function addPayrollItem(string $periodId, array $data): PayrollItem
    {
        if (empty($data['guard_id']) || !isset($data['regular_rate'])) throw ApiException::validation('guard_id, regular_rate required.');
        $period = $this->periodRepo->findOrFail($periodId);

        $item = new PayrollItem();
        $item->setTenantId($period->getTenantId())->setPayrollPeriodId($periodId)->setGuardId($data['guard_id'])
            ->setRegularHours((float) ($data['regular_hours'] ?? 0))
            ->setOvertimeHours((float) ($data['overtime_hours'] ?? 0))
            ->setHolidayHours((float) ($data['holiday_hours'] ?? 0))
            ->setRegularRate((float) $data['regular_rate'])
            ->setOvertimeRate((float) ($data['overtime_rate'] ?? (float) $data['regular_rate'] * 1.5))
            ->setHolidayRate((float) ($data['holiday_rate'] ?? (float) $data['regular_rate'] * 2.0));

        // Nigeria PAYE deductions
        if (isset($data['deductions'])) {
            $item->setDeductions($data['deductions']);
        } else {
            $item->setDeductions($this->calculateNigeriaPAYE($item));
        }

        $item->calculatePay();
        $this->itemRepo->save($item);
        return $item;
    }

    /**
     * Auto-calculate payroll from time clock records for all guards in the period.
     */
    public function calculateFromTimeClock(string $periodId, float $defaultRate = 500): array
    {
        $period = $this->periodRepo->findOrFail($periodId);
        $period->setStatus(PayrollStatus::CALCULATING);
        $this->periodRepo->save($period);

        // Get multipliers
        $multipliers = $this->rateRepo->findActiveByTenant($period->getTenantId());
        $otMultiplier = 1.5;
        foreach ($multipliers as $m) {
            if ($m->getAppliesTo() === PayRateAppliesTo::OVERTIME) $otMultiplier = $m->getMultiplier();
        }

        // Query time clock records grouped by guard
        $conn = $this->periodRepo->getEntityManager()->getConnection();
        $sql = "SELECT guard_id, SUM(total_hours) as total_hours FROM time_clocks
                WHERE tenant_id = ? AND clock_in_time >= ? AND clock_in_time <= ?
                AND total_hours IS NOT NULL GROUP BY guard_id";
        $rows = $conn->fetchAllAssociative($sql, [
            $period->getTenantId(),
            $period->toArray()['period_start'],
            $period->toArray()['period_end'],
        ]);

        $items = [];
        $totalGross = 0; $totalDeductions = 0;
        foreach ($rows as $row) {
            $totalHours = (float) $row['total_hours'];
            $regularHours = min($totalHours, 160); // 40hrs/week × 4 weeks
            $overtimeHours = max(0, $totalHours - 160);

            $item = new PayrollItem();
            $item->setTenantId($period->getTenantId())->setPayrollPeriodId($periodId)
                ->setGuardId($row['guard_id'])
                ->setRegularHours($regularHours)->setOvertimeHours($overtimeHours)->setHolidayHours(0)
                ->setRegularRate($defaultRate)->setOvertimeRate($defaultRate * $otMultiplier)->setHolidayRate($defaultRate * 2);
            $item->setDeductions($this->calculateNigeriaPAYE($item));
            $item->calculatePay();
            $this->itemRepo->save($item);
            $items[] = $item;
            $totalGross += $item->getGrossPay();
            $totalDeductions += array_sum($item->getDeductions());
        }

        $period->updateTotals($totalGross, $totalDeductions);
        $period->setStatus(PayrollStatus::REVIEW);
        $this->periodRepo->save($period);
        return $items;
    }

    public function approvePeriod(string $periodId, string $userId): PayrollPeriod
    {
        $period = $this->periodRepo->findOrFail($periodId);
        $period->approve($userId);
        $this->periodRepo->save($period);

        // Generate payslips
        $items = $this->itemRepo->findByPeriod($periodId);
        foreach ($items as $item) {
            $item->approve();
            $this->itemRepo->save($item);
            $slip = new Payslip();
            $slip->setPayrollItemId($item->getId())->setGuardId($item->getGuardId())
                ->setPeriodStart($period->toArray()['period_start'] ? new \DateTimeImmutable($period->toArray()['period_start']) : new \DateTimeImmutable())
                ->setPeriodEnd($period->toArray()['period_end'] ? new \DateTimeImmutable($period->toArray()['period_end']) : new \DateTimeImmutable())
                ->setGrossPay($item->getGrossPay())->setDeductionsBreakdown($item->getDeductions())->setNetPay($item->getNetPay());
            $this->slipRepo->save($slip);
        }
        return $period;
    }

    public function listPeriods(string $tenantId): array { return $this->periodRepo->findByTenant($tenantId); }
    public function getPeriodDetail(string $periodId): array
    {
        $period = $this->periodRepo->findOrFail($periodId);
        $items = $this->itemRepo->findByPeriod($periodId);
        return ['period' => $period->toArray(), 'items' => array_map(fn($i) => $i->toArray(), $items)];
    }

    public function getGuardPayslips(string $guardId): array { return $this->slipRepo->findByGuard($guardId); }

    // Rate multipliers
    public function listRates(string $tenantId): array { return $this->rateRepo->findByTenant($tenantId); }
    public function createRate(string $tenantId, array $data): PayRateMultiplier
    {
        if (empty($data['name']) || !isset($data['multiplier']) || empty($data['applies_to'])) throw ApiException::validation('name, multiplier, applies_to required.');
        $r = new PayRateMultiplier();
        $r->setTenantId($tenantId)->setName($data['name'])->setMultiplier((float) $data['multiplier'])->setAppliesTo(PayRateAppliesTo::from($data['applies_to']));
        $this->rateRepo->save($r);
        return $r;
    }

    /**
     * Email payslip to guard via ZeptoMail.
     * TODO: Integrate with ZeptoMail API once email service is configured.
     */
    public function emailPayslip(string $payslipId): Payslip
    {
        $slip = $this->slipRepo->findOrFail($payslipId);
        // TODO: $this->emailService->sendPayslip($slip);
        $slip->markEmailed();
        $this->slipRepo->save($slip);
        $this->logger->info('Payslip emailed', ['id' => $slip->getId(), 'guard_id' => $slip->getGuardId()]);
        return $slip;
    }

    /**
     * Export payroll period as CSV data (guard breakdown).
     */
    public function exportPayrollCsv(string $periodId): string
    {
        $detail = $this->getPeriodDetail($periodId);
        $csv = "Guard ID,Regular Hours,OT Hours,Holiday Hours,Regular Rate,OT Rate,Holiday Rate,Gross Pay,PAYE,Pension,NHF,Total Deductions,Net Pay\n";
        foreach ($detail['items'] as $item) {
            $paye = $item['deductions']['paye'] ?? 0;
            $pension = $item['deductions']['pension'] ?? 0;
            $nhf = $item['deductions']['nhf'] ?? 0;
            $csv .= implode(',', [
                $item['guard_id'], $item['regular_hours'], $item['overtime_hours'], $item['holiday_hours'],
                $item['regular_rate'], $item['overtime_rate'], $item['holiday_rate'],
                $item['gross_pay'], $paye, $pension, $nhf, $item['total_deductions'], $item['net_pay'],
            ]) . "\n";
        }
        return $csv;
    }

    /**
     * Simplified Nigeria PAYE calculation.
     * CRA = max(200000, 1% of gross) + 20% of gross. Taxable = Gross - CRA - Pension.
     */
    private function calculateNigeriaPAYE(PayrollItem $item): array
    {
        $item->calculatePay();
        $annualGross = $item->getGrossPay() * 12;
        $pension = round($annualGross * 0.08, 2); // 8% employee contribution
        $nhf = round($annualGross * 0.025, 2); // 2.5% NHF
        $cra = max(200000, $annualGross * 0.01) + ($annualGross * 0.20);
        $taxable = max(0, $annualGross - $cra - $pension);

        // PAYE brackets
        $tax = 0;
        $brackets = [[300000, 0.07], [300000, 0.11], [500000, 0.15], [500000, 0.19], [1600000, 0.21], [PHP_FLOAT_MAX, 0.24]];
        $remaining = $taxable;
        foreach ($brackets as [$limit, $rate]) {
            $chunk = min($remaining, $limit);
            $tax += $chunk * $rate;
            $remaining -= $chunk;
            if ($remaining <= 0) break;
        }

        return [
            'paye' => round($tax / 12, 2),
            'pension' => round($pension / 12, 2),
            'nhf' => round($nhf / 12, 2),
        ];
    }
}
