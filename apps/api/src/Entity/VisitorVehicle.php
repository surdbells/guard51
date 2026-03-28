<?php
declare(strict_types=1);
namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'visitor_vehicles')]
#[ORM\Index(name: 'idx_vv_visitor', columns: ['visitor_id'])]
class VisitorVehicle
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $visitorId;

    #[ORM\Column(type: 'string', length: 20)]
    private string $plateNumber;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $make = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $model = null;

    #[ORM\Column(type: 'string', length: 30, nullable: true)]
    private ?string $color = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct() { $this->id = Uuid::uuid4()->toString(); $this->createdAt = new \DateTimeImmutable(); }

    public function getId(): string { return $this->id; }
    public function setVisitorId(string $id): static { $this->visitorId = $id; return $this; }
    public function setPlateNumber(string $p): static { $this->plateNumber = $p; return $this; }
    public function setMake(?string $m): static { $this->make = $m; return $this; }
    public function setModel(?string $m): static { $this->model = $m; return $this; }
    public function setColor(?string $c): static { $this->color = $c; return $this; }

    public function toArray(): array
    {
        return [
            'id' => $this->id, 'visitor_id' => $this->visitorId, 'plate_number' => $this->plateNumber,
            'make' => $this->make, 'model' => $this->model, 'color' => $this->color,
        ];
    }
}
