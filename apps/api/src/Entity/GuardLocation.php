<?php
declare(strict_types=1);
namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'guard_locations')]
#[ORM\Index(name: 'idx_gl_tenant', columns: ['tenant_id'])]
#[ORM\Index(name: 'idx_gl_guard', columns: ['guard_id'])]
#[ORM\Index(name: 'idx_gl_recorded', columns: ['recorded_at'])]
class GuardLocation implements TenantAwareInterface
{
    use TenantAwareTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'guard_id', type: 'string', length: 36)]
    private string $guardId;

    #[ORM\Column(name: 'site_id', type: 'string', length: 36, nullable: true)]
    private ?string $siteId = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 8)]
    private string $latitude;

    #[ORM\Column(type: 'decimal', precision: 11, scale: 8)]
    private string $longitude;

    #[ORM\Column(type: 'decimal', precision: 6, scale: 2)]
    private string $accuracy;

    #[ORM\Column(type: 'decimal', precision: 6, scale: 2, nullable: true)]
    private ?string $speed = null;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2, nullable: true)]
    private ?string $heading = null;

    #[ORM\Column(type: 'decimal', precision: 8, scale: 2, nullable: true)]
    private ?string $altitude = null;

    #[ORM\Column(name: 'battery_level', type: 'integer', nullable: true)]
    private ?int $batteryLevel = null;

    #[ORM\Column(name: 'is_moving', type: 'boolean', options: ['default' => true])]
    private bool $isMoving = true;

    #[ORM\Column(type: 'string', length: 20, enumType: LocationSource::class)]
    private LocationSource $source;

    #[ORM\Column(name: 'recorded_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $recordedAt;

    #[ORM\Column(name: 'received_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $receivedAt;

    public function __construct()
    {
        $this->id = Uuid::uuid4()->toString();
        $this->receivedAt = new \DateTimeImmutable();
    }

    public function getId(): string { return $this->id; }
    public function getGuardId(): string { return $this->guardId; }
    public function getSiteId(): ?string { return $this->siteId; }
    public function getLatitude(): float { return (float) $this->latitude; }
    public function getLongitude(): float { return (float) $this->longitude; }
    public function getAccuracy(): float { return (float) $this->accuracy; }
    public function getSpeed(): ?float { return $this->speed !== null ? (float) $this->speed : null; }
    public function getHeading(): ?float { return $this->heading !== null ? (float) $this->heading : null; }
    public function getBatteryLevel(): ?int { return $this->batteryLevel; }
    public function isMoving(): bool { return $this->isMoving; }
    public function getSource(): LocationSource { return $this->source; }
    public function getRecordedAt(): \DateTimeImmutable { return $this->recordedAt; }

    public function setGuardId(string $id): static { $this->guardId = $id; return $this; }
    public function setSiteId(?string $id): static { $this->siteId = $id; return $this; }
    public function setLatitude(float $v): static { $this->latitude = (string) $v; return $this; }
    public function setLongitude(float $v): static { $this->longitude = (string) $v; return $this; }
    public function setAccuracy(float $v): static { $this->accuracy = (string) $v; return $this; }
    public function setSpeed(?float $v): static { $this->speed = $v !== null ? (string) $v : null; return $this; }
    public function setHeading(?float $v): static { $this->heading = $v !== null ? (string) $v : null; return $this; }
    public function setAltitude(?float $v): static { $this->altitude = $v !== null ? (string) $v : null; return $this; }
    public function setBatteryLevel(?int $v): static { $this->batteryLevel = $v; return $this; }
    public function setIsMoving(bool $v): static { $this->isMoving = $v; return $this; }
    public function setSource(LocationSource $s): static { $this->source = $s; return $this; }
    public function setRecordedAt(\DateTimeImmutable $t): static { $this->recordedAt = $t; return $this; }

    public function toArray(): array
    {
        return [
            'id' => $this->id, 'tenant_id' => $this->tenantId, 'guard_id' => $this->guardId,
            'site_id' => $this->siteId,
            'lat' => $this->getLatitude(), 'lng' => $this->getLongitude(),
            'accuracy' => $this->getAccuracy(), 'speed' => $this->getSpeed(),
            'heading' => $this->getHeading(), 'battery_level' => $this->batteryLevel,
            'is_moving' => $this->isMoving, 'source' => $this->source->value,
            'recorded_at' => $this->recordedAt->format(\DateTimeInterface::ATOM),
            'received_at' => $this->receivedAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
