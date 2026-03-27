<?php

declare(strict_types=1);

namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'break_logs')]
#[ORM\Index(name: 'idx_bl_tc', columns: ['time_clock_id'])]
class BreakLog
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $timeClockId;

    #[ORM\Column(type: 'string', length: 36)]
    private string $breakConfigId;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $startTime;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $endTime = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $durationMinutes = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->id = Uuid::uuid4()->toString();
        $this->startTime = new \DateTimeImmutable();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): string { return $this->id; }
    public function getTimeClockId(): string { return $this->timeClockId; }
    public function getBreakConfigId(): string { return $this->breakConfigId; }
    public function getStartTime(): \DateTimeImmutable { return $this->startTime; }
    public function getEndTime(): ?\DateTimeImmutable { return $this->endTime; }
    public function getDurationMinutes(): ?int { return $this->durationMinutes; }
    public function isOnBreak(): bool { return $this->endTime === null; }

    public function setTimeClockId(string $id): static { $this->timeClockId = $id; return $this; }
    public function setBreakConfigId(string $id): static { $this->breakConfigId = $id; return $this; }

    public function endBreak(): static
    {
        $this->endTime = new \DateTimeImmutable();
        $this->durationMinutes = (int) round(($this->endTime->getTimestamp() - $this->startTime->getTimestamp()) / 60);
        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id, 'time_clock_id' => $this->timeClockId, 'break_config_id' => $this->breakConfigId,
            'start_time' => $this->startTime->format(\DateTimeInterface::ATOM),
            'end_time' => $this->endTime?->format(\DateTimeInterface::ATOM),
            'duration_minutes' => $this->durationMinutes, 'is_on_break' => $this->isOnBreak(),
            'created_at' => $this->createdAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
