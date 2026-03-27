<?php
declare(strict_types=1);
namespace Guard51\Repository;

use Guard51\Entity\IncidentEscalation;

/** @extends BaseRepository<IncidentEscalation> */
class IncidentEscalationRepository extends BaseRepository
{
    protected function getEntityClass(): string { return IncidentEscalation::class; }
    public function findByIncident(string $incidentId): array { return $this->findBy(['incidentId' => $incidentId], ['escalatedAt' => 'ASC']); }
}
