<?php
declare(strict_types=1);
namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'parking_lots')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'idx_pl_area', columns: ['parking_area_id'])]
class ParkingLot
{
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $parkingAreaId;

    #[ORM\Column(type: 'string', length: 100)]
    private string $name;

    #[ORM\Column(type: 'integer')]
    private int $capacity;

    #[ORM\Column(type: 'string', length: 10, enumType: ParkingLotType::class)]
    private ParkingLotType $lotType = ParkingLotType::REGULAR;

    public function __construct() { $this->id = Uuid::uuid4()->toString(); }

    public function getId(): string { return $this->id; }
    public function setParkingAreaId(string $id): static { $this->parkingAreaId = $id; return $this; }
    public function setName(string $n): static { $this->name = $n; return $this; }
    public function setCapacity(int $c): static { $this->capacity = $c; return $this; }
    public function setLotType(ParkingLotType $t): static { $this->lotType = $t; return $this; }

    public function toArray(): array
    {
        return [
            'id' => $this->id, 'parking_area_id' => $this->parkingAreaId, 'name' => $this->name,
            'capacity' => $this->capacity, 'lot_type' => $this->lotType->value, 'lot_type_label' => $this->lotType->label(),
            'created_at' => $this->createdAt?->format(\DateTimeInterface::ATOM),
        ];
    }
}
