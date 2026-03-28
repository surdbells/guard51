<?php
declare(strict_types=1);
namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'payroll_periods')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'idx_pp_tenant', columns: ['tenant_id'])]
class PayrollPeriod implements TenantAwareInterface
{
    use TenantAwareTrait;
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $periodStart;

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $periodEnd;

    #[ORM\Column(type: 'string', length: 20, enumType: PayrollStatus::class)]
    private PayrollStatus $status = PayrollStatus::DRAFT;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private string $totalGross = '0';

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private string $totalDeductions = '0';

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private string $totalNet = '0';

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $approvedBy = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $approvedAt = null;

    public function __construct() { $this->id = Uuid::uuid4()->toString(); }

    public function getId(): string { return $this->id; }
    public function getStatus(): PayrollStatus { return $this->status; }
    public function getTotalGross(): float { return (float) $this->totalGross; }
    public function getTotalNet(): float { return (float) $this->totalNet; }

    public function setPeriodStart(\DateTimeImmutable $d): static { $this->periodStart = $d; return $this; }
    public function setPeriodEnd(\DateTimeImmutable $d): static { $this->periodEnd = $d; return $this; }
    public function setStatus(PayrollStatus $s): static { $this->status = $s; return $this; }

    public function updateTotals(float $gross, float $deductions): static
    {
        $this->totalGross = (string) round($gross, 2);
        $this->totalDeductions = (string) round($deductions, 2);
        $this->totalNet = (string) round($gross - $deductions, 2);
        return $this;
    }

    public function approve(string $userId): static
    {
        $this->status = PayrollStatus::APPROVED;
        $this->approvedBy = $userId;
        $this->approvedAt = new \DateTimeImmutable();
        return $this;
    }

    public function markPaid(): static { $this->status = PayrollStatus::PAID; return $this; }

    public function toArray(): array
    {
        return [
            'id' => $this->id, 'tenant_id' => $this->tenantId,
            'period_start' => $this->periodStart->format('Y-m-d'), 'period_end' => $this->periodEnd->format('Y-m-d'),
            'status' => $this->status->value, 'status_label' => $this->status->label(),
            'total_gross' => $this->getTotalGross(), 'total_deductions' => (float) $this->totalDeductions,
            'total_net' => $this->getTotalNet(),
            'approved_by' => $this->approvedBy, 'approved_at' => $this->approvedAt?->format(\DateTimeInterface::ATOM),
            'created_at' => $this->createdAt?->format(\DateTimeInterface::ATOM),
        ];
    }
}
