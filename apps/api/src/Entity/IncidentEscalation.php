<?php
declare(strict_types=1);
namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'incident_escalations')]
#[ORM\Index(name: 'idx_ie_incident', columns: ['incident_id'])]
class IncidentEscalation
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $incidentId;

    #[ORM\Column(type: 'string', length: 36)]
    private string $escalatedTo;

    #[ORM\Column(type: 'string', length: 36)]
    private string $escalatedBy;

    #[ORM\Column(type: 'text')]
    private string $reason;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $escalatedAt;

    public function __construct() { $this->id = Uuid::uuid4()->toString(); $this->escalatedAt = new \DateTimeImmutable(); }

    public function getId(): string { return $this->id; }
    public function setIncidentId(string $id): static { $this->incidentId = $id; return $this; }
    public function setEscalatedTo(string $id): static { $this->escalatedTo = $id; return $this; }
    public function setEscalatedBy(string $id): static { $this->escalatedBy = $id; return $this; }
    public function setReason(string $r): static { $this->reason = $r; return $this; }

    public function toArray(): array
    {
        return [
            'id' => $this->id, 'incident_id' => $this->incidentId,
            'escalated_to' => $this->escalatedTo, 'escalated_by' => $this->escalatedBy,
            'reason' => $this->reason, 'escalated_at' => $this->escalatedAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
