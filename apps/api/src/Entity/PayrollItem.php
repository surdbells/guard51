<?php
declare(strict_types=1);
namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'payroll_items')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'idx_pi_period', columns: ['payroll_period_id'])]
#[ORM\Index(name: 'idx_pi_guard', columns: ['guard_id'])]
class PayrollItem implements TenantAwareInterface
{
    use TenantAwareTrait;
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $payrollPeriodId;

    #[ORM\Column(type: 'string', length: 36)]
    private string $guardId;

    #[ORM\Column(type: 'decimal', precision: 6, scale: 2)]
    private string $regularHours = '0';

    #[ORM\Column(type: 'decimal', precision: 6, scale: 2)]
    private string $overtimeHours = '0';

    #[ORM\Column(type: 'decimal', precision: 6, scale: 2)]
    private string $holidayHours = '0';

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $regularRate;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $overtimeRate;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $holidayRate;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $grossPay = '0';

    /** @var array{paye?: float, pension?: float, nhf?: float, other?: float} */
    #[ORM\Column(type: 'json')]
    private array $deductions = [];

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $netPay = '0';

    #[ORM\Column(type: 'string', length: 20, enumType: PayrollItemStatus::class)]
    private PayrollItemStatus $status = PayrollItemStatus::PENDING;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    public function __construct() { $this->id = Uuid::uuid4()->toString(); }

    public function getId(): string { return $this->id; }
    public function getPayrollPeriodId(): string { return $this->payrollPeriodId; }
    public function getGuardId(): string { return $this->guardId; }
    public function getGrossPay(): float { return (float) $this->grossPay; }
    public function getNetPay(): float { return (float) $this->netPay; }
    public function getDeductions(): array { return $this->deductions; }
    public function getStatus(): PayrollItemStatus { return $this->status; }

    public function setPayrollPeriodId(string $id): static { $this->payrollPeriodId = $id; return $this; }
    public function setGuardId(string $id): static { $this->guardId = $id; return $this; }
    public function setRegularHours(float $h): static { $this->regularHours = (string) $h; return $this; }
    public function setOvertimeHours(float $h): static { $this->overtimeHours = (string) $h; return $this; }
    public function setHolidayHours(float $h): static { $this->holidayHours = (string) $h; return $this; }
    public function setRegularRate(float $r): static { $this->regularRate = (string) $r; return $this; }
    public function setOvertimeRate(float $r): static { $this->overtimeRate = (string) $r; return $this; }
    public function setHolidayRate(float $r): static { $this->holidayRate = (string) $r; return $this; }
    public function setDeductions(array $d): static { $this->deductions = $d; return $this; }
    public function setNotes(?string $n): static { $this->notes = $n; return $this; }

    public function calculatePay(): static
    {
        $gross = ((float) $this->regularHours * (float) $this->regularRate)
            + ((float) $this->overtimeHours * (float) $this->overtimeRate)
            + ((float) $this->holidayHours * (float) $this->holidayRate);
        $this->grossPay = (string) round($gross, 2);
        $totalDeductions = array_sum($this->deductions);
        $this->netPay = (string) round($gross - $totalDeductions, 2);
        return $this;
    }

    public function approve(): static { $this->status = PayrollItemStatus::APPROVED; return $this; }
    public function markPaid(): static { $this->status = PayrollItemStatus::PAID; return $this; }

    public function toArray(): array
    {
        return [
            'id' => $this->id, 'payroll_period_id' => $this->payrollPeriodId, 'guard_id' => $this->guardId,
            'regular_hours' => (float) $this->regularHours, 'overtime_hours' => (float) $this->overtimeHours,
            'holiday_hours' => (float) $this->holidayHours,
            'regular_rate' => (float) $this->regularRate, 'overtime_rate' => (float) $this->overtimeRate,
            'holiday_rate' => (float) $this->holidayRate,
            'gross_pay' => $this->getGrossPay(), 'deductions' => $this->deductions,
            'total_deductions' => array_sum($this->deductions), 'net_pay' => $this->getNetPay(),
            'status' => $this->status->value, 'status_label' => $this->status->label(), 'notes' => $this->notes,
            'created_at' => $this->createdAt?->format(\DateTimeInterface::ATOM),
        ];
    }
}
