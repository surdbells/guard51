<?php

declare(strict_types=1);

namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

/**
 * A security site/post — a physical location that guards are assigned to protect.
 * Supports circular (lat/lng + radius) and polygon (PostGIS) geofences.
 */
#[ORM\Entity]
#[ORM\Table(name: 'sites')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'idx_site_tenant', columns: ['tenant_id'])]
#[ORM\Index(name: 'idx_site_client', columns: ['client_id'])]
#[ORM\Index(name: 'idx_site_status', columns: ['status'])]
class Site implements TenantAwareInterface
{
    use TenantAwareTrait;
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'client_id', type: 'string', length: 36, nullable: true)]
    private ?string $clientId = null;

    #[ORM\Column(type: 'string', length: 200)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $address = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $state = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 8, nullable: true)]
    private ?string $latitude = null;

    #[ORM\Column(type: 'decimal', precision: 11, scale: 8, nullable: true)]
    private ?string $longitude = null;

    #[ORM\Column(name: 'geofence_radius', type: 'integer', options: ['default' => 100])]
    private int $geofenceRadius = 100;

    /** PostGIS polygon stored as GeoJSON text — parsed by GeofenceService */
    #[ORM\Column(name: 'geofence_polygon', type: 'text', nullable: true)]
    private ?string $geofencePolygon = null;

    #[ORM\Column(name: 'geofence_type', type: 'string', length: 20, enumType: GeofenceType::class)]
    private GeofenceType $geofenceType = GeofenceType::CIRCLE;

    #[ORM\Column(name: 'contact_name', type: 'string', length: 200, nullable: true)]
    private ?string $contactName = null;

    #[ORM\Column(name: 'contact_phone', type: 'string', length: 50, nullable: true)]
    private ?string $contactPhone = null;

    #[ORM\Column(name: 'contact_email', type: 'string', length: 255, nullable: true)]
    private ?string $contactEmail = null;

    #[ORM\Column(type: 'string', length: 50, options: ['default' => 'Africa/Lagos'])]
    private string $timezone = 'Africa/Lagos';

    #[ORM\Column(type: 'string', length: 20, enumType: SiteStatus::class)]
    private SiteStatus $status = SiteStatus::ACTIVE;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(name: 'photo_url', type: 'string', length: 500, nullable: true)]
    private ?string $photoUrl = null;

    public function __construct()
    {
        $this->id = Uuid::uuid4()->toString();
    }

    // ── Getters ──────────────────────────────────────

    public function getId(): string { return $this->id; }
    public function getClientId(): ?string { return $this->clientId; }
    public function getName(): string { return $this->name; }
    public function getAddress(): ?string { return $this->address; }
    public function getCity(): ?string { return $this->city; }
    public function getState(): ?string { return $this->state; }
    public function getLatitude(): ?float { return $this->latitude !== null ? (float) $this->latitude : null; }
    public function getLongitude(): ?float { return $this->longitude !== null ? (float) $this->longitude : null; }
    public function getGeofenceRadius(): int { return $this->geofenceRadius; }
    public function getGeofencePolygon(): ?string { return $this->geofencePolygon; }
    public function getGeofenceType(): GeofenceType { return $this->geofenceType; }
    public function getContactName(): ?string { return $this->contactName; }
    public function getContactPhone(): ?string { return $this->contactPhone; }
    public function getContactEmail(): ?string { return $this->contactEmail; }
    public function getTimezone(): string { return $this->timezone; }
    public function getStatus(): SiteStatus { return $this->status; }
    public function getNotes(): ?string { return $this->notes; }
    public function getPhotoUrl(): ?string { return $this->photoUrl; }

    // ── Setters ──────────────────────────────────────

    public function setClientId(?string $clientId): static { $this->clientId = $clientId; return $this; }
    public function setName(string $name): static { $this->name = $name; return $this; }
    public function setAddress(?string $address): static { $this->address = $address; return $this; }
    public function setCity(?string $city): static { $this->city = $city; return $this; }
    public function setState(?string $state): static { $this->state = $state; return $this; }
    public function setLatitude(?float $lat): static { $this->latitude = $lat !== null ? (string) $lat : null; return $this; }
    public function setLongitude(?float $lng): static { $this->longitude = $lng !== null ? (string) $lng : null; return $this; }
    public function setGeofenceRadius(int $radius): static { $this->geofenceRadius = $radius; return $this; }
    public function setGeofencePolygon(?string $polygon): static { $this->geofencePolygon = $polygon; return $this; }
    public function setGeofenceType(GeofenceType $type): static { $this->geofenceType = $type; return $this; }
    public function setContactName(?string $name): static { $this->contactName = $name; return $this; }
    public function setContactPhone(?string $phone): static { $this->contactPhone = $phone; return $this; }
    public function setContactEmail(?string $email): static { $this->contactEmail = $email; return $this; }
    public function setTimezone(string $tz): static { $this->timezone = $tz; return $this; }
    public function setStatus(SiteStatus $status): static { $this->status = $status; return $this; }
    public function setNotes(?string $notes): static { $this->notes = $notes; return $this; }
    public function setPhotoUrl(?string $url): static { $this->photoUrl = $url; return $this; }

    // ── Business Logic ───────────────────────────────

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    public function isCircularGeofence(): bool
    {
        return $this->geofenceType === GeofenceType::CIRCLE;
    }

    public function activate(): static { $this->status = SiteStatus::ACTIVE; return $this; }
    public function deactivate(): static { $this->status = SiteStatus::INACTIVE; return $this; }
    public function suspend(): static { $this->status = SiteStatus::SUSPENDED; return $this; }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenantId,
            'client_id' => $this->clientId,
            'name' => $this->name,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'latitude' => $this->getLatitude(),
            'longitude' => $this->getLongitude(),
            'geofence_radius' => $this->geofenceRadius,
            'geofence_type' => $this->geofenceType->value,
            'contact_name' => $this->contactName,
            'contact_phone' => $this->contactPhone,
            'contact_email' => $this->contactEmail,
            'timezone' => $this->timezone,
            'status' => $this->status->value,
            'photo_url' => $this->photoUrl,
            'notes' => $this->notes,
            'created_at' => $this->createdAt?->format(\DateTimeInterface::ATOM),
            'updated_at' => $this->updatedAt?->format(\DateTimeInterface::ATOM),
        ];
    }
}
