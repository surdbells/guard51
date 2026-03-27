<?php

declare(strict_types=1);

namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'shifts')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'idx_shift_tenant', columns: ['tenant_id'])]
#[ORM\Index(name: 'idx_shift_site', columns: ['site_id'])]
#[ORM\Index(name: 'idx_shift_guard', columns: ['guard_id'])]
#[ORM\Index(name: 'idx_shift_date', columns: ['shift_date'])]
#[ORM\Index(name: 'idx_shift_status', columns: ['status'])]
class Shift implements TenantAwareInterface
{
    use TenantAwareTrait;
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $siteId;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $templateId = null;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $guardId = null;

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $shiftDate;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $startTime;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $endTime;

    #[ORM\Column(type: 'string', length: 20, enumType: ShiftStatus::class)]
    private ShiftStatus $status = ShiftStatus::DRAFT;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isOpen = false;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'string', length: 36)]
    private string $createdBy;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $confirmedAt = null;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $confirmedBy = null;

    public function __construct()
    {
        $this->id = Uuid::uuid4()->toString();
        $this->shiftDate = new \DateTimeImmutable();
        $this->startTime = new \DateTimeImmutable();
        $this->endTime = new \DateTimeImmutable('+12 hours');
    }

    public function getId(): string { return $this->id; }
    public function getSiteId(): string { return $this->siteId; }
    public function getTemplateId(): ?string { return $this->templateId; }
    public function getGuardId(): ?string { return $this->guardId; }
    public function getShiftDate(): \DateTimeImmutable { return $this->shiftDate; }
    public function getStartTime(): \DateTimeImmutable { return $this->startTime; }
    public function getEndTime(): \DateTimeImmutable { return $this->endTime; }
    public function getStatus(): ShiftStatus { return $this->status; }
    public function isOpen(): bool { return $this->isOpen; }
    public function getNotes(): ?string { return $this->notes; }
    public function getCreatedBy(): string { return $this->createdBy; }
    public function getConfirmedAt(): ?\DateTimeImmutable { return $this->confirmedAt; }
    public function getConfirmedBy(): ?string { return $this->confirmedBy; }

    public function setSiteId(string $id): static { $this->siteId = $id; return $this; }
    public function setTemplateId(?string $id): static { $this->templateId = $id; return $this; }
    public function setGuardId(?string $id): static { $this->guardId = $id; return $this; }
    public function setShiftDate(\DateTimeImmutable $d): static { $this->shiftDate = $d; return $this; }
    public function setStartTime(\DateTimeImmutable $t): static { $this->startTime = $t; return $this; }
    public function setEndTime(\DateTimeImmutable $t): static { $this->endTime = $t; return $this; }
    public function setStatus(ShiftStatus $s): static { $this->status = $s; return $this; }
    public function setIsOpen(bool $v): static { $this->isOpen = $v; return $this; }
    public function setNotes(?string $n): static { $this->notes = $n; return $this; }
    public function setCreatedBy(string $id): static { $this->createdBy = $id; return $this; }

    public function isAssigned(): bool { return $this->guardId !== null; }

    public function publish(): static
    {
        $this->status = ShiftStatus::PUBLISHED;
        return $this;
    }

    public function confirm(string $guardId): static
    {
        $this->status = ShiftStatus::CONFIRMED;
        $this->confirmedAt = new \DateTimeImmutable();
        $this->confirmedBy = $guardId;
        if (!$this->guardId) $this->guardId = $guardId;
        $this->isOpen = false;
        return $this;
    }

    public function startShift(): static { $this->status = ShiftStatus::IN_PROGRESS; return $this; }
    public function complete(): static { $this->status = ShiftStatus::COMPLETED; return $this; }
    public function markMissed(): static { $this->status = ShiftStatus::MISSED; return $this; }
    public function cancel(): static { $this->status = ShiftStatus::CANCELLED; return $this; }

    public function getDurationHours(): float
    {
        $diff = $this->endTime->getTimestamp() - $this->startTime->getTimestamp();
        return round($diff / 3600, 2);
    }

    public function hasConflict(\DateTimeImmutable $otherStart, \DateTimeImmutable $otherEnd): bool
    {
        return $this->startTime < $otherEnd && $this->endTime > $otherStart;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id, 'tenant_id' => $this->tenantId, 'site_id' => $this->siteId,
            'template_id' => $this->templateId, 'guard_id' => $this->guardId,
            'shift_date' => $this->shiftDate->format('Y-m-d'),
            'start_time' => $this->startTime->format(\DateTimeInterface::ATOM),
            'end_time' => $this->endTime->format(\DateTimeInterface::ATOM),
            'duration_hours' => $this->getDurationHours(),
            'status' => $this->status->value, 'status_label' => $this->status->label(),
            'is_open' => $this->isOpen, 'is_assigned' => $this->isAssigned(),
            'notes' => $this->notes, 'created_by' => $this->createdBy,
            'confirmed_at' => $this->confirmedAt?->format(\DateTimeInterface::ATOM),
            'confirmed_by' => $this->confirmedBy,
            'created_at' => $this->createdAt?->format(\DateTimeInterface::ATOM),
            'updated_at' => $this->updatedAt?->format(\DateTimeInterface::ATOM),
        ];
    }
}
