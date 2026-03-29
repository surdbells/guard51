<?php
declare(strict_types=1);
namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'parking_areas')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'idx_pa_tenant', columns: ['tenant_id'])]
class ParkingArea implements TenantAwareInterface
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

    #[ORM\Column(name: 'total_spaces', type: 'integer')]
    private int $totalSpaces;

    #[ORM\Column(type: 'string', length: 10, enumType: AreaStatus::class)]
    private AreaStatus $status = AreaStatus::ACTIVE;

    public function __construct() { $this->id = Uuid::uuid4()->toString(); }

    public function getId(): string { return $this->id; }
    public function setSiteId(string $id): static { $this->siteId = $id; return $this; }
    public function setName(string $n): static { $this->name = $n; return $this; }
    public function setTotalSpaces(int $s): static { $this->totalSpaces = $s; return $this; }
    public function setStatus(AreaStatus $s): static { $this->status = $s; return $this; }

    public function toArray(): array
    {
        return [
            'id' => $this->id, 'tenant_id' => $this->tenantId, 'site_id' => $this->siteId,
            'name' => $this->name, 'total_spaces' => $this->totalSpaces,
            'status' => $this->status->value, 'status_label' => $this->status->label(),
            'created_at' => $this->createdAt?->format(\DateTimeInterface::ATOM),
        ];
    }
}
