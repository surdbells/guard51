<?php
declare(strict_types=1);
namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'vehicle_patrol_hits')]
#[ORM\Index(name: 'idx_vph_tenant', columns: ['tenant_id'])]
#[ORM\Index(name: 'idx_vph_route', columns: ['route_id'])]
class VehiclePatrolHit implements TenantAwareInterface
{
    use TenantAwareTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $routeId;

    #[ORM\Column(type: 'string', length: 36)]
    private string $vehicleId;

    #[ORM\Column(type: 'string', length: 36)]
    private string $guardId;

    #[ORM\Column(type: 'string', length: 36)]
    private string $siteId;

    #[ORM\Column(type: 'integer')]
    private int $hitNumber;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 8)]
    private string $latitude;

    #[ORM\Column(type: 'decimal', precision: 11, scale: 8)]
    private string $longitude;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $photoUrl = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $recordedAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct() { $this->id = Uuid::uuid4()->toString(); $this->recordedAt = new \DateTimeImmutable(); $this->createdAt = new \DateTimeImmutable(); }

    public function getId(): string { return $this->id; }
    public function setRouteId(string $id): static { $this->routeId = $id; return $this; }
    public function setVehicleId(string $id): static { $this->vehicleId = $id; return $this; }
    public function setGuardId(string $id): static { $this->guardId = $id; return $this; }
    public function setSiteId(string $id): static { $this->siteId = $id; return $this; }
    public function setHitNumber(int $n): static { $this->hitNumber = $n; return $this; }
    public function setLatitude(float $v): static { $this->latitude = (string) $v; return $this; }
    public function setLongitude(float $v): static { $this->longitude = (string) $v; return $this; }
    public function setNotes(?string $n): static { $this->notes = $n; return $this; }
    public function setPhotoUrl(?string $u): static { $this->photoUrl = $u; return $this; }

    public function toArray(): array
    {
        return [
            'id' => $this->id, 'tenant_id' => $this->tenantId, 'route_id' => $this->routeId,
            'vehicle_id' => $this->vehicleId, 'guard_id' => $this->guardId, 'site_id' => $this->siteId,
            'hit_number' => $this->hitNumber, 'lat' => (float) $this->latitude, 'lng' => (float) $this->longitude,
            'notes' => $this->notes, 'photo_url' => $this->photoUrl,
            'recorded_at' => $this->recordedAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
