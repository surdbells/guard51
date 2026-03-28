<?php
declare(strict_types=1);
namespace Guard51\Repository;
use Guard51\Entity\GuardPerformanceIndex;

/** @extends BaseRepository<GuardPerformanceIndex> */
class GuardPerformanceIndexRepository extends BaseRepository
{
    protected function getEntityClass(): string { return GuardPerformanceIndex::class; }
}
