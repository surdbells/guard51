<?php
declare(strict_types=1);
namespace Guard51\Repository;

use Guard51\Entity\TourCheckpoint;

/** @extends BaseRepository<TourCheckpoint> */
class TourCheckpointRepository extends BaseRepository
{
    protected function getEntityClass(): string { return TourCheckpoint::class; }
    public function findBySite(string $siteId): array { return $this->findBy(['siteId' => $siteId, 'isActive' => true], ['sequenceOrder' => 'ASC']); }
    public function countBySite(string $siteId): int { return $this->count(['siteId' => $siteId, 'isActive' => true]); }
}
