<?php
declare(strict_types=1);
namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'daily_activity_reports')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'idx_dar_tenant', columns: ['tenant_id'])]
#[ORM\Index(name: 'idx_dar_guard', columns: ['guard_id'])]
#[ORM\Index(name: 'idx_dar_date', columns: ['report_date'])]
class DailyActivityReport implements TenantAwareInterface
{
    use TenantAwareTrait;
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'guard_id', type: 'string', length: 36)]
    private string $guardId;

    #[ORM\Column(name: 'site_id', type: 'string', length: 36)]
    private string $siteId;

    #[ORM\Column(name: 'shift_id', type: 'string', length: 36, nullable: true)]
    private ?string $shiftId = null;

    #[ORM\Column(name: 'report_date', type: 'date_immutable')]
    private \DateTimeImmutable $reportDate;

    #[ORM\Column(type: 'text')]
    private string $content;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $weather = null;

    #[ORM\Column(type: 'string', length: 20, enumType: ReportStatus::class)]
    private ReportStatus $status = ReportStatus::DRAFT;

    #[ORM\Column(name: 'submitted_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $submittedAt = null;

    #[ORM\Column(name: 'reviewed_by', type: 'string', length: 36, nullable: true)]
    private ?string $reviewedBy = null;

    #[ORM\Column(name: 'reviewed_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $reviewedAt = null;

    #[ORM\Column(type: 'json')]
    private array $attachments = [];

    public function __construct() { $this->id = Uuid::uuid4()->toString(); $this->reportDate = new \DateTimeImmutable(); }

    public function getId(): string { return $this->id; }
    public function getGuardId(): string { return $this->guardId; }
    public function getSiteId(): string { return $this->siteId; }
    public function getReportDate(): \DateTimeImmutable { return $this->reportDate; }
    public function getContent(): string { return $this->content; }
    public function getStatus(): ReportStatus { return $this->status; }

    public function setGuardId(string $id): static { $this->guardId = $id; return $this; }
    public function setSiteId(string $id): static { $this->siteId = $id; return $this; }
    public function setShiftId(?string $id): static { $this->shiftId = $id; return $this; }
    public function setReportDate(\DateTimeImmutable $d): static { $this->reportDate = $d; return $this; }
    public function setContent(string $c): static { $this->content = $c; return $this; }
    public function setWeather(?string $w): static { $this->weather = $w; return $this; }
    public function setStatus(ReportStatus $s): static { $this->status = $s; return $this; }
    public function setAttachments(array $a): static { $this->attachments = $a; return $this; }

    public function submit(): static { $this->status = ReportStatus::SUBMITTED; $this->submittedAt = new \DateTimeImmutable(); return $this; }
    public function review(string $userId): static { $this->status = ReportStatus::REVIEWED; $this->reviewedBy = $userId; $this->reviewedAt = new \DateTimeImmutable(); return $this; }
    public function approve(string $userId): static { $this->status = ReportStatus::APPROVED; $this->reviewedBy = $userId; $this->reviewedAt = new \DateTimeImmutable(); return $this; }

    public function toArray(): array
    {
        return [
            'id' => $this->id, 'tenant_id' => $this->tenantId, 'guard_id' => $this->guardId,
            'site_id' => $this->siteId, 'shift_id' => $this->shiftId,
            'report_date' => $this->reportDate->format('Y-m-d'), 'content' => $this->content,
            'weather' => $this->weather, 'status' => $this->status->value, 'status_label' => $this->status->label(),
            'submitted_at' => $this->submittedAt?->format(\DateTimeInterface::ATOM),
            'reviewed_by' => $this->reviewedBy, 'reviewed_at' => $this->reviewedAt?->format(\DateTimeInterface::ATOM),
            'attachments' => $this->attachments, 'attachment_count' => count($this->attachments),
            'created_at' => $this->createdAt?->format(\DateTimeInterface::ATOM),
        ];
    }
}
