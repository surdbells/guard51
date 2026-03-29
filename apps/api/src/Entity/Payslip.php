<?php
declare(strict_types=1);
namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'payslips')]
#[ORM\Index(name: 'idx_ps_guard', columns: ['guard_id'])]
class Payslip
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'payroll_item_id', type: 'string', length: 36)]
    private string $payrollItemId;

    #[ORM\Column(name: 'guard_id', type: 'string', length: 36)]
    private string $guardId;

    #[ORM\Column(name: 'period_start', type: 'date_immutable')]
    private \DateTimeImmutable $periodStart;

    #[ORM\Column(name: 'period_end', type: 'date_immutable')]
    private \DateTimeImmutable $periodEnd;

    #[ORM\Column(name: 'gross_pay', type: 'decimal', precision: 10, scale: 2)]
    private string $grossPay;

    #[ORM\Column(name: 'deductions_breakdown', type: 'json')]
    private array $deductionsBreakdown = [];

    #[ORM\Column(name: 'net_pay', type: 'decimal', precision: 10, scale: 2)]
    private string $netPay;

    #[ORM\Column(name: 'pdf_url', type: 'string', length: 500, nullable: true)]
    private ?string $pdfUrl = null;

    #[ORM\Column(name: 'emailed_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $emailedAt = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct() { $this->id = Uuid::uuid4()->toString(); $this->createdAt = new \DateTimeImmutable(); }

    public function getId(): string { return $this->id; }
    public function getGuardId(): string { return $this->guardId; }
    public function getGrossPay(): float { return (float) $this->grossPay; }
    public function getNetPay(): float { return (float) $this->netPay; }

    public function setPayrollItemId(string $id): static { $this->payrollItemId = $id; return $this; }
    public function setGuardId(string $id): static { $this->guardId = $id; return $this; }
    public function setPeriodStart(\DateTimeImmutable $d): static { $this->periodStart = $d; return $this; }
    public function setPeriodEnd(\DateTimeImmutable $d): static { $this->periodEnd = $d; return $this; }
    public function setGrossPay(float $v): static { $this->grossPay = (string) $v; return $this; }
    public function setDeductionsBreakdown(array $d): static { $this->deductionsBreakdown = $d; return $this; }
    public function setNetPay(float $v): static { $this->netPay = (string) $v; return $this; }
    public function setPdfUrl(?string $u): static { $this->pdfUrl = $u; return $this; }
    public function markEmailed(): static { $this->emailedAt = new \DateTimeImmutable(); return $this; }

    public function toArray(): array
    {
        return [
            'id' => $this->id, 'payroll_item_id' => $this->payrollItemId, 'guard_id' => $this->guardId,
            'period_start' => $this->periodStart->format('Y-m-d'), 'period_end' => $this->periodEnd->format('Y-m-d'),
            'gross_pay' => $this->getGrossPay(), 'deductions_breakdown' => $this->deductionsBreakdown,
            'total_deductions' => array_sum($this->deductionsBreakdown), 'net_pay' => $this->getNetPay(),
            'pdf_url' => $this->pdfUrl, 'emailed_at' => $this->emailedAt?->format(\DateTimeInterface::ATOM),
            'created_at' => $this->createdAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
