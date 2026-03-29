<?php

declare(strict_types=1);

namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'time_clocks')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'idx_tc_tenant', columns: ['tenant_id'])]
#[ORM\Index(name: 'idx_tc_guard', columns: ['guard_id'])]
#[ORM\Index(name: 'idx_tc_site', columns: ['site_id'])]
#[ORM\Index(name: 'idx_tc_status', columns: ['status'])]
class TimeClock implements TenantAwareInterface
{
    use TenantAwareTrait;
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'guard_id', type: 'string', length: 36)]
    private string $guardId;

    #[ORM\Column(name: 'shift_id', type: 'string', length: 36, nullable: true)]
    private ?string $shiftId = null;

    #[ORM\Column(name: 'site_id', type: 'string', length: 36)]
    private string $siteId;

    #[ORM\Column(name: 'clock_in_time', type: 'datetime_immutable')]
    private \DateTimeImmutable $clockInTime;

    #[ORM\Column(name: 'clock_in_lat', type: 'decimal', precision: 10, scale: 8)]
    private string $clockInLat;

    #[ORM\Column(name: 'clock_in_lng', type: 'decimal', precision: 11, scale: 8)]
    private string $clockInLng;

    #[ORM\Column(name: 'clock_in_method', type: 'string', length: 20, enumType: ClockMethod::class)]
    private ClockMethod $clockInMethod;

    #[ORM\Column(name: 'clock_in_photo_url', type: 'string', length: 500, nullable: true)]
    private ?string $clockInPhotoUrl = null;

    #[ORM\Column(name: 'clock_out_time', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $clockOutTime = null;

    #[ORM\Column(name: 'clock_out_lat', type: 'decimal', precision: 10, scale: 8, nullable: true)]
    private ?string $clockOutLat = null;

    #[ORM\Column(name: 'clock_out_lng', type: 'decimal', precision: 11, scale: 8, nullable: true)]
    private ?string $clockOutLng = null;

    #[ORM\Column(name: 'clock_out_method', type: 'string', length: 20, enumType: ClockMethod::class, nullable: true)]
    private ?ClockMethod $clockOutMethod = null;

    #[ORM\Column(name: 'total_hours', type: 'decimal', precision: 5, scale: 2, nullable: true)]
    private ?string $totalHours = null;

    #[ORM\Column(type: 'string', length: 20, enumType: TimeClockStatus::class)]
    private TimeClockStatus $status = TimeClockStatus::CLOCKED_IN;

    #[ORM\Column(name: 'is_within_geofence_in', type: 'boolean')]
    private bool $isWithinGeofenceIn;

    #[ORM\Column(name: 'is_within_geofence_out', type: 'boolean', nullable: true)]
    private ?bool $isWithinGeofenceOut = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    public function __construct()
    {
        $this->id = Uuid::uuid4()->toString();
        $this->clockInTime = new \DateTimeImmutable();
    }

    public function getId(): string { return $this->id; }
    public function getGuardId(): string { return $this->guardId; }
    public function getShiftId(): ?string { return $this->shiftId; }
    public function getSiteId(): string { return $this->siteId; }
    public function getClockInTime(): \DateTimeImmutable { return $this->clockInTime; }
    public function getClockOutTime(): ?\DateTimeImmutable { return $this->clockOutTime; }
    public function getTotalHours(): ?float { return $this->totalHours !== null ? (float) $this->totalHours : null; }
    public function getStatus(): TimeClockStatus { return $this->status; }
    public function isWithinGeofenceIn(): bool { return $this->isWithinGeofenceIn; }
    public function isWithinGeofenceOut(): ?bool { return $this->isWithinGeofenceOut; }

    public function setGuardId(string $id): static { $this->guardId = $id; return $this; }
    public function setShiftId(?string $id): static { $this->shiftId = $id; return $this; }
    public function setSiteId(string $id): static { $this->siteId = $id; return $this; }
    public function setClockInTime(\DateTimeImmutable $t): static { $this->clockInTime = $t; return $this; }
    public function setClockInLat(float $v): static { $this->clockInLat = (string) $v; return $this; }
    public function setClockInLng(float $v): static { $this->clockInLng = (string) $v; return $this; }
    public function setClockInMethod(ClockMethod $m): static { $this->clockInMethod = $m; return $this; }
    public function setClockInPhotoUrl(?string $url): static { $this->clockInPhotoUrl = $url; return $this; }
    public function setIsWithinGeofenceIn(bool $v): static { $this->isWithinGeofenceIn = $v; return $this; }
    public function setNotes(?string $n): static { $this->notes = $n; return $this; }

    public function clockOut(float $lat, float $lng, ClockMethod $method, bool $withinGeofence): static
    {
        $this->clockOutTime = new \DateTimeImmutable();
        $this->clockOutLat = (string) $lat;
        $this->clockOutLng = (string) $lng;
        $this->clockOutMethod = $method;
        $this->isWithinGeofenceOut = $withinGeofence;
        $this->status = TimeClockStatus::CLOCKED_OUT;
        $this->totalHours = (string) round(
            ($this->clockOutTime->getTimestamp() - $this->clockInTime->getTimestamp()) / 3600, 2
        );
        return $this;
    }

    public function autoClockOut(): static
    {
        $this->clockOutTime = new \DateTimeImmutable();
        $this->status = TimeClockStatus::AUTO_CLOCKED_OUT;
        $this->totalHours = (string) round(
            ($this->clockOutTime->getTimestamp() - $this->clockInTime->getTimestamp()) / 3600, 2
        );
        return $this;
    }

    public function isClockedIn(): bool { return $this->status === TimeClockStatus::CLOCKED_IN; }

    public function toArray(): array
    {
        return [
            'id' => $this->id, 'tenant_id' => $this->tenantId, 'guard_id' => $this->guardId,
            'shift_id' => $this->shiftId, 'site_id' => $this->siteId,
            'clock_in_time' => $this->clockInTime->format(\DateTimeInterface::ATOM),
            'clock_in_method' => $this->clockInMethod->value,
            'clock_out_time' => $this->clockOutTime?->format(\DateTimeInterface::ATOM),
            'clock_out_method' => $this->clockOutMethod?->value,
            'total_hours' => $this->getTotalHours(),
            'status' => $this->status->value, 'status_label' => $this->status->label(),
            'is_within_geofence_in' => $this->isWithinGeofenceIn,
            'is_within_geofence_out' => $this->isWithinGeofenceOut,
            'notes' => $this->notes,
            'created_at' => $this->createdAt?->format(\DateTimeInterface::ATOM),
        ];
    }
}
