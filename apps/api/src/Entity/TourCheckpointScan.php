<?php
declare(strict_types=1);
namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'tour_checkpoint_scans')]
#[ORM\Index(name: 'idx_tcs_session', columns: ['session_id'])]
class TourCheckpointScan
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $sessionId;

    #[ORM\Column(type: 'string', length: 36)]
    private string $checkpointId;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $scannedAt;

    #[ORM\Column(type: 'string', length: 20, enumType: ScanMethod::class)]
    private ScanMethod $scanMethod;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 8)]
    private string $latitude;

    #[ORM\Column(type: 'decimal', precision: 11, scale: 8)]
    private string $longitude;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $photoUrl = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct() { $this->id = Uuid::uuid4()->toString(); $this->scannedAt = new \DateTimeImmutable(); $this->createdAt = new \DateTimeImmutable(); }

    public function getId(): string { return $this->id; }
    public function getSessionId(): string { return $this->sessionId; }
    public function getCheckpointId(): string { return $this->checkpointId; }

    public function setSessionId(string $id): static { $this->sessionId = $id; return $this; }
    public function setCheckpointId(string $id): static { $this->checkpointId = $id; return $this; }
    public function setScannedAt(\DateTimeImmutable $t): static { $this->scannedAt = $t; return $this; }
    public function setScanMethod(ScanMethod $m): static { $this->scanMethod = $m; return $this; }
    public function setLatitude(float $v): static { $this->latitude = (string) $v; return $this; }
    public function setLongitude(float $v): static { $this->longitude = (string) $v; return $this; }
    public function setNotes(?string $n): static { $this->notes = $n; return $this; }
    public function setPhotoUrl(?string $u): static { $this->photoUrl = $u; return $this; }

    public function toArray(): array
    {
        return [
            'id' => $this->id, 'session_id' => $this->sessionId, 'checkpoint_id' => $this->checkpointId,
            'scanned_at' => $this->scannedAt->format(\DateTimeInterface::ATOM),
            'scan_method' => $this->scanMethod->value, 'scan_method_label' => $this->scanMethod->label(),
            'lat' => (float) $this->latitude, 'lng' => (float) $this->longitude,
            'notes' => $this->notes, 'photo_url' => $this->photoUrl,
        ];
    }
}
