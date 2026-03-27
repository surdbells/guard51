<?php

declare(strict_types=1);

namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'shift_swap_requests')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'idx_ssr_tenant', columns: ['tenant_id'])]
#[ORM\Index(name: 'idx_ssr_shift', columns: ['shift_id'])]
#[ORM\Index(name: 'idx_ssr_status', columns: ['status'])]
class ShiftSwapRequest implements TenantAwareInterface
{
    use TenantAwareTrait;
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $requestingGuardId;

    #[ORM\Column(type: 'string', length: 36)]
    private string $targetGuardId;

    #[ORM\Column(type: 'string', length: 36)]
    private string $shiftId;

    #[ORM\Column(type: 'text')]
    private string $reason;

    #[ORM\Column(type: 'string', length: 20, enumType: SwapRequestStatus::class)]
    private SwapRequestStatus $status = SwapRequestStatus::PENDING;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $reviewedBy = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $reviewedAt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $reviewNotes = null;

    public function __construct() { $this->id = Uuid::uuid4()->toString(); }

    public function getId(): string { return $this->id; }
    public function getRequestingGuardId(): string { return $this->requestingGuardId; }
    public function getTargetGuardId(): string { return $this->targetGuardId; }
    public function getShiftId(): string { return $this->shiftId; }
    public function getReason(): string { return $this->reason; }
    public function getStatus(): SwapRequestStatus { return $this->status; }
    public function getReviewedBy(): ?string { return $this->reviewedBy; }
    public function getReviewedAt(): ?\DateTimeImmutable { return $this->reviewedAt; }

    public function setRequestingGuardId(string $id): static { $this->requestingGuardId = $id; return $this; }
    public function setTargetGuardId(string $id): static { $this->targetGuardId = $id; return $this; }
    public function setShiftId(string $id): static { $this->shiftId = $id; return $this; }
    public function setReason(string $reason): static { $this->reason = $reason; return $this; }

    public function approve(string $reviewerId, ?string $notes = null): static
    {
        $this->status = SwapRequestStatus::APPROVED;
        $this->reviewedBy = $reviewerId;
        $this->reviewedAt = new \DateTimeImmutable();
        $this->reviewNotes = $notes;
        return $this;
    }

    public function reject(string $reviewerId, ?string $notes = null): static
    {
        $this->status = SwapRequestStatus::REJECTED;
        $this->reviewedBy = $reviewerId;
        $this->reviewedAt = new \DateTimeImmutable();
        $this->reviewNotes = $notes;
        return $this;
    }

    public function cancel(): static { $this->status = SwapRequestStatus::CANCELLED; return $this; }
    public function isPending(): bool { return $this->status->isPending(); }

    public function toArray(): array
    {
        return [
            'id' => $this->id, 'tenant_id' => $this->tenantId,
            'requesting_guard_id' => $this->requestingGuardId, 'target_guard_id' => $this->targetGuardId,
            'shift_id' => $this->shiftId, 'reason' => $this->reason,
            'status' => $this->status->value, 'status_label' => $this->status->label(),
            'reviewed_by' => $this->reviewedBy, 'reviewed_at' => $this->reviewedAt?->format(\DateTimeInterface::ATOM),
            'review_notes' => $this->reviewNotes,
            'created_at' => $this->createdAt?->format(\DateTimeInterface::ATOM),
        ];
    }
}
