<?php
declare(strict_types=1);
namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'custom_report_submissions')]
#[ORM\Index(name: 'idx_crs_tenant', columns: ['tenant_id'])]
#[ORM\Index(name: 'idx_crs_template', columns: ['template_id'])]
class CustomReportSubmission implements TenantAwareInterface
{
    use TenantAwareTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $templateId;

    #[ORM\Column(type: 'string', length: 36)]
    private string $guardId;

    #[ORM\Column(type: 'string', length: 36)]
    private string $siteId;

    #[ORM\Column(type: 'json')]
    private array $data = [];

    #[ORM\Column(type: 'json')]
    private array $attachments = [];

    #[ORM\Column(type: 'string', length: 20, enumType: ReportStatus::class)]
    private ReportStatus $status = ReportStatus::SUBMITTED;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $submittedAt;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $reviewedBy = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct() { $this->id = Uuid::uuid4()->toString(); $this->submittedAt = new \DateTimeImmutable(); $this->createdAt = new \DateTimeImmutable(); }

    public function getId(): string { return $this->id; }
    public function getTemplateId(): string { return $this->templateId; }
    public function getStatus(): ReportStatus { return $this->status; }

    public function setTemplateId(string $id): static { $this->templateId = $id; return $this; }
    public function setGuardId(string $id): static { $this->guardId = $id; return $this; }
    public function setSiteId(string $id): static { $this->siteId = $id; return $this; }
    public function setData(array $d): static { $this->data = $d; return $this; }
    public function setAttachments(array $a): static { $this->attachments = $a; return $this; }
    public function review(string $userId): static { $this->status = ReportStatus::REVIEWED; $this->reviewedBy = $userId; return $this; }
    public function approve(string $userId): static { $this->status = ReportStatus::APPROVED; $this->reviewedBy = $userId; return $this; }

    public function toArray(): array
    {
        return [
            'id' => $this->id, 'tenant_id' => $this->tenantId, 'template_id' => $this->templateId,
            'guard_id' => $this->guardId, 'site_id' => $this->siteId,
            'data' => $this->data, 'attachments' => $this->attachments,
            'status' => $this->status->value, 'status_label' => $this->status->label(),
            'submitted_at' => $this->submittedAt->format(\DateTimeInterface::ATOM),
            'reviewed_by' => $this->reviewedBy,
        ];
    }
}
