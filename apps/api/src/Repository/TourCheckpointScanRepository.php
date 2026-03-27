<?php
declare(strict_types=1);
namespace Guard51\Repository;

use Guard51\Entity\TourCheckpointScan;

/** @extends BaseRepository<TourCheckpointScan> */
class TourCheckpointScanRepository extends BaseRepository
{
    protected function getEntityClass(): string { return TourCheckpointScan::class; }
    public function findBySession(string $sessionId): array { return $this->findBy(['sessionId' => $sessionId], ['scannedAt' => 'ASC']); }
}
