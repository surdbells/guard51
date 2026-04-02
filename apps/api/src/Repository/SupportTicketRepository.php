<?php
declare(strict_types=1);
namespace Guard51\Repository;
use Guard51\Entity\SupportTicket;

/** @extends BaseRepository<SupportTicket> */
class SupportTicketRepository extends BaseRepository
{
    protected function getEntityClass(): string { return SupportTicket::class; }

    public function findByTenant(string $tenantId, ?string $status = null): array
    {
        $c = ['tenantId' => $tenantId];
        if ($status) $c['status'] = $status;
        return $this->findBy($c, ['createdAt' => 'DESC']);
    }

    public function findAll(): array { return $this->repository->findBy([], ['createdAt' => 'DESC']); }

    public function countOpen(): int { return $this->count(['status' => 'open']); }
}
