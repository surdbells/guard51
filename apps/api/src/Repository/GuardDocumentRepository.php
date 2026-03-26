<?php

declare(strict_types=1);

namespace Guard51\Repository;

use Guard51\Entity\GuardDocument;

/** @extends BaseRepository<GuardDocument> */
class GuardDocumentRepository extends BaseRepository
{
    protected function getEntityClass(): string { return GuardDocument::class; }

    /** @return GuardDocument[] */
    public function findByGuard(string $guardId): array { return $this->findBy(['guardId' => $guardId], ['createdAt' => 'DESC']); }

    /** @return GuardDocument[] Documents expiring within N days */
    public function findExpiringSoon(string $tenantId, int $days = 30): array
    {
        $now = new \DateTimeImmutable();
        $threshold = $now->modify("+{$days} days");
        $conn = $this->em->getConnection();
        $sql = "SELECT gd.* FROM guard_documents gd JOIN guards g ON g.id = gd.guard_id WHERE g.tenant_id = ? AND gd.expiry_date IS NOT NULL AND gd.expiry_date BETWEEN ? AND ? ORDER BY gd.expiry_date ASC";
        return $conn->fetchAllAssociative($sql, [$tenantId, $now->format('Y-m-d'), $threshold->format('Y-m-d')]);
    }
}
