<?php
declare(strict_types=1);
namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'tour_checkpoints')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'idx_tcp_tenant', columns: ['tenant_id'])]
#[ORM\Index(name: 'idx_tcp_site', columns: ['site_id'])]
class TourCheckpoint implements TenantAwareInterface
{
    use TenantAwareTrait;
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'site_id', type: 'string', length: 36)]
    private string $siteId;

    #[ORM\Column(type: 'string', length: 200)]
    private string $name;

    #[ORM\Column(name: 'checkpoint_type', type: 'string', length: 20, enumType: CheckpointType::class)]
    private CheckpointType $checkpointType;

    #[ORM\Column(name: 'qr_code_value', type: 'string', length: 255, nullable: true)]
    private ?string $qrCodeValue = null;

    #[ORM\Column(name: 'nfc_tag_id', type: 'string', length: 255, nullable: true)]
    private ?string $nfcTagId = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 8, nullable: true)]
    private ?string $latitude = null;

    #[ORM\Column(type: 'decimal', precision: 11, scale: 8, nullable: true)]
    private ?string $longitude = null;

    #[ORM\Column(name: 'virtual_radius', type: 'integer', nullable: true)]
    private ?int $virtualRadius = null;

    #[ORM\Column(name: 'sequence_order', type: 'integer', options: ['default' => 0])]
    private int $sequenceOrder = 0;

    #[ORM\Column(name: 'is_required', type: 'boolean', options: ['default' => true])]
    private bool $isRequired = true;

    #[ORM\Column(name: 'is_active', type: 'boolean', options: ['default' => true])]
    private bool $isActive = true;

    public function __construct() { $this->id = Uuid::uuid4()->toString(); }

    public function getId(): string { return $this->id; }
    public function getSiteId(): string { return $this->siteId; }
    public function getName(): string { return $this->name; }
    public function getCheckpointType(): CheckpointType { return $this->checkpointType; }
    public function getQrCodeValue(): ?string { return $this->qrCodeValue; }
    public function getNfcTagId(): ?string { return $this->nfcTagId; }
    public function getLatitude(): ?float { return $this->latitude !== null ? (float) $this->latitude : null; }
    public function getLongitude(): ?float { return $this->longitude !== null ? (float) $this->longitude : null; }
    public function getVirtualRadius(): ?int { return $this->virtualRadius; }
    public function getSequenceOrder(): int { return $this->sequenceOrder; }
    public function isRequired(): bool { return $this->isRequired; }
    public function isActive(): bool { return $this->isActive; }

    public function setSiteId(string $id): static { $this->siteId = $id; return $this; }
    public function setName(string $n): static { $this->name = $n; return $this; }
    public function setCheckpointType(CheckpointType $t): static { $this->checkpointType = $t; return $this; }
    public function setQrCodeValue(?string $v): static { $this->qrCodeValue = $v; return $this; }
    public function setNfcTagId(?string $v): static { $this->nfcTagId = $v; return $this; }
    public function setLatitude(?float $v): static { $this->latitude = $v !== null ? (string) $v : null; return $this; }
    public function setLongitude(?float $v): static { $this->longitude = $v !== null ? (string) $v : null; return $this; }
    public function setVirtualRadius(?int $v): static { $this->virtualRadius = $v; return $this; }
    public function setSequenceOrder(int $v): static { $this->sequenceOrder = $v; return $this; }
    public function setIsRequired(bool $v): static { $this->isRequired = $v; return $this; }
    public function setIsActive(bool $v): static { $this->isActive = $v; return $this; }

    public function toArray(): array
    {
        return [
            'id' => $this->id, 'tenant_id' => $this->tenantId, 'site_id' => $this->siteId,
            'name' => $this->name, 'checkpoint_type' => $this->checkpointType->value,
            'checkpoint_type_label' => $this->checkpointType->label(),
            'qr_code_value' => $this->qrCodeValue, 'nfc_tag_id' => $this->nfcTagId,
            'lat' => $this->getLatitude(), 'lng' => $this->getLongitude(),
            'virtual_radius' => $this->virtualRadius, 'sequence_order' => $this->sequenceOrder,
            'is_required' => $this->isRequired, 'is_active' => $this->isActive,
            'created_at' => $this->createdAt?->format(\DateTimeInterface::ATOM),
        ];
    }
}
