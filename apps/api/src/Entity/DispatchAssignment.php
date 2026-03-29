<?php
declare(strict_types=1);
namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'dispatch_assignments')]
#[ORM\Index(name: 'idx_da_dispatch', columns: ['dispatch_id'])]
class DispatchAssignment
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'dispatch_id', type: 'string', length: 36)]
    private string $dispatchId;

    #[ORM\Column(name: 'guard_id', type: 'string', length: 36)]
    private string $guardId;

    #[ORM\Column(name: 'assigned_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $assignedAt;

    #[ORM\Column(name: 'acknowledged_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $acknowledgedAt = null;

    #[ORM\Column(name: 'arrived_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $arrivedAt = null;

    #[ORM\Column(name: 'completed_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    #[ORM\Column(type: 'string', length: 20, enumType: DispatchAssignmentStatus::class)]
    private DispatchAssignmentStatus $status = DispatchAssignmentStatus::ASSIGNED;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct() { $this->id = Uuid::uuid4()->toString(); $this->assignedAt = new \DateTimeImmutable(); $this->createdAt = new \DateTimeImmutable(); }

    public function getId(): string { return $this->id; }
    public function getDispatchId(): string { return $this->dispatchId; }
    public function getGuardId(): string { return $this->guardId; }
    public function getStatus(): DispatchAssignmentStatus { return $this->status; }

    public function setDispatchId(string $id): static { $this->dispatchId = $id; return $this; }
    public function setGuardId(string $id): static { $this->guardId = $id; return $this; }
    public function setNotes(?string $n): static { $this->notes = $n; return $this; }

    public function acknowledge(): static { $this->status = DispatchAssignmentStatus::ACKNOWLEDGED; $this->acknowledgedAt = new \DateTimeImmutable(); return $this; }
    public function markEnRoute(): static { $this->status = DispatchAssignmentStatus::EN_ROUTE; return $this; }
    public function markOnScene(): static { $this->status = DispatchAssignmentStatus::ON_SCENE; $this->arrivedAt = new \DateTimeImmutable(); return $this; }
    public function complete(?string $notes = null): static { $this->status = DispatchAssignmentStatus::COMPLETED; $this->completedAt = new \DateTimeImmutable(); if ($notes) $this->notes = $notes; return $this; }

    public function toArray(): array
    {
        return [
            'id' => $this->id, 'dispatch_id' => $this->dispatchId, 'guard_id' => $this->guardId,
            'assigned_at' => $this->assignedAt->format(\DateTimeInterface::ATOM),
            'acknowledged_at' => $this->acknowledgedAt?->format(\DateTimeInterface::ATOM),
            'arrived_at' => $this->arrivedAt?->format(\DateTimeInterface::ATOM),
            'completed_at' => $this->completedAt?->format(\DateTimeInterface::ATOM),
            'status' => $this->status->value, 'status_label' => $this->status->label(),
            'notes' => $this->notes,
        ];
    }
}
