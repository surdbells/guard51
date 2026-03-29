<?php
declare(strict_types=1);
namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'guard_performance_index')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'idx_gpi_tenant', columns: ['tenant_id'])]
#[ORM\Index(name: 'idx_gpi_guard', columns: ['guard_id'])]
class GuardPerformanceIndex implements TenantAwareInterface
{
    use TenantAwareTrait;
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'guard_id', type: 'string', length: 36)]
    private string $guardId;

    #[ORM\Column(name: 'period_month', type: 'string', length: 20)]
    private string $periodMonth; // "2026-03"

    #[ORM\Column(name: 'punctuality_score', type: 'decimal', precision: 5, scale: 2)]
    private string $punctualityScore = '0'; // 0-100

    #[ORM\Column(name: 'tour_compliance_score', type: 'decimal', precision: 5, scale: 2)]
    private string $tourComplianceScore = '0';

    #[ORM\Column(name: 'report_completion_score', type: 'decimal', precision: 5, scale: 2)]
    private string $reportCompletionScore = '0';

    #[ORM\Column(name: 'incident_response_score', type: 'decimal', precision: 5, scale: 2)]
    private string $incidentResponseScore = '0';

    #[ORM\Column(name: 'overall_score', type: 'decimal', precision: 5, scale: 2)]
    private string $overallScore = '0';

    #[ORM\Column(type: 'string', length: 2)]
    private string $grade = 'C'; // A+, A, B+, B, C, D, F

    #[ORM\Column(type: 'json')]
    private array $breakdown = [];

    public function __construct() { $this->id = Uuid::uuid4()->toString(); }

    public function getId(): string { return $this->id; }
    public function setGuardId(string $id): static { $this->guardId = $id; return $this; }
    public function setPeriodMonth(string $m): static { $this->periodMonth = $m; return $this; }
    public function setPunctualityScore(float $s): static { $this->punctualityScore = (string) $s; return $this; }
    public function setTourComplianceScore(float $s): static { $this->tourComplianceScore = (string) $s; return $this; }
    public function setReportCompletionScore(float $s): static { $this->reportCompletionScore = (string) $s; return $this; }
    public function setIncidentResponseScore(float $s): static { $this->incidentResponseScore = (string) $s; return $this; }
    public function setBreakdown(array $b): static { $this->breakdown = $b; return $this; }

    public function calculateOverall(): static
    {
        $overall = ((float)$this->punctualityScore * 0.3) + ((float)$this->tourComplianceScore * 0.25)
            + ((float)$this->reportCompletionScore * 0.25) + ((float)$this->incidentResponseScore * 0.2);
        $this->overallScore = (string) round($overall, 2);
        $this->grade = match(true) {
            $overall >= 95 => 'A+', $overall >= 85 => 'A', $overall >= 80 => 'B+',
            $overall >= 70 => 'B', $overall >= 60 => 'C', $overall >= 50 => 'D', default => 'F',
        };
        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id, 'tenant_id' => $this->tenantId, 'guard_id' => $this->guardId,
            'period_month' => $this->periodMonth,
            'punctuality_score' => (float) $this->punctualityScore,
            'tour_compliance_score' => (float) $this->tourComplianceScore,
            'report_completion_score' => (float) $this->reportCompletionScore,
            'incident_response_score' => (float) $this->incidentResponseScore,
            'overall_score' => (float) $this->overallScore, 'grade' => $this->grade,
            'breakdown' => $this->breakdown,
            'created_at' => $this->createdAt?->format(\DateTimeInterface::ATOM),
        ];
    }
}
