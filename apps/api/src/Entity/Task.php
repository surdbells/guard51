<?php
declare(strict_types=1);
namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'tasks')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'idx_task_tenant', columns: ['tenant_id'])]
#[ORM\Index(name: 'idx_task_site', columns: ['site_id'])]
#[ORM\Index(name: 'idx_task_guard', columns: ['assigned_to'])]
#[ORM\Index(name: 'idx_task_status', columns: ['status'])]
class Task implements TenantAwareInterface
{
    use TenantAwareTrait;
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'site_id', type: 'string', length: 36)]
    private string $siteId;

    #[ORM\Column(name: 'assigned_to', type: 'string', length: 36)]
    private string $assignedTo;

    #[ORM\Column(name: 'assigned_by', type: 'string', length: 36)]
    private string $assignedBy;

    #[ORM\Column(type: 'string', length: 300)]
    private string $title;

    #[ORM\Column(type: 'text')]
    private string $description;

    #[ORM\Column(type: 'string', length: 10, enumType: Severity::class)]
    private Severity $priority = Severity::MEDIUM;

    #[ORM\Column(name: 'due_date', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $dueDate = null;

    #[ORM\Column(type: 'string', length: 20, enumType: TaskStatus::class)]
    private TaskStatus $status = TaskStatus::PENDING;

    #[ORM\Column(name: 'completed_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    #[ORM\Column(name: 'completion_notes', type: 'text', nullable: true)]
    private ?string $completionNotes = null;

    #[ORM\Column(type: 'json')]
    private array $attachments = [];

    public function __construct() { $this->id = Uuid::uuid4()->toString(); }

    public function getId(): string { return $this->id; }
    public function getSiteId(): string { return $this->siteId; }
    public function getAssignedTo(): string { return $this->assignedTo; }
    public function getStatus(): TaskStatus { return $this->status; }
    public function getDueDate(): ?\DateTimeImmutable { return $this->dueDate; }

    public function setSiteId(string $id): static { $this->siteId = $id; return $this; }
    public function setAssignedTo(string $id): static { $this->assignedTo = $id; return $this; }
    public function setAssignedBy(string $id): static { $this->assignedBy = $id; return $this; }
    public function setTitle(string $t): static { $this->title = $t; return $this; }
    public function setDescription(string $d): static { $this->description = $d; return $this; }
    public function setPriority(Severity $p): static { $this->priority = $p; return $this; }
    public function setDueDate(?\DateTimeImmutable $d): static { $this->dueDate = $d; return $this; }
    public function setAttachments(array $a): static { $this->attachments = $a; return $this; }

    public function start(): static { $this->status = TaskStatus::IN_PROGRESS; return $this; }
    public function complete(?string $notes = null): static { $this->status = TaskStatus::COMPLETED; $this->completedAt = new \DateTimeImmutable(); $this->completionNotes = $notes; return $this; }
    public function cancel(): static { $this->status = TaskStatus::CANCELLED; return $this; }
    public function markOverdue(): static { $this->status = TaskStatus::OVERDUE; return $this; }

    public function isOverdue(): bool
    {
        return $this->dueDate !== null && $this->dueDate < new \DateTimeImmutable() && $this->status->isActive();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id, 'tenant_id' => $this->tenantId, 'site_id' => $this->siteId,
            'assigned_to' => $this->assignedTo, 'assigned_by' => $this->assignedBy,
            'title' => $this->title, 'description' => $this->description,
            'priority' => $this->priority->value, 'priority_label' => $this->priority->label(),
            'due_date' => $this->dueDate?->format(\DateTimeInterface::ATOM),
            'status' => $this->status->value, 'status_label' => $this->status->label(),
            'is_overdue' => $this->isOverdue(),
            'completed_at' => $this->completedAt?->format(\DateTimeInterface::ATOM),
            'completion_notes' => $this->completionNotes, 'attachments' => $this->attachments,
            'created_at' => $this->createdAt?->format(\DateTimeInterface::ATOM),
        ];
    }
}
