<?php

declare(strict_types=1);

namespace Guard51\Repository;

use Guard51\Entity\Client;
use Guard51\Entity\ClientStatus;

/** @extends BaseRepository<Client> */
class ClientRepository extends BaseRepository
{
    protected function getEntityClass(): string { return Client::class; }

    public function findByTenant(string $tenantId, ?string $status = null): array
    {
        $c = ['tenantId' => $tenantId];
        if ($status) $c['status'] = ClientStatus::from($status);
        return $this->findBy($c, ['companyName' => 'ASC']);
    }

    public function countByTenant(string $tenantId): int { return $this->count(['tenantId' => $tenantId, 'tenantId' => $tenantId]); }

    public function searchByName(string $tenantId, string $query): array
    {
        $qb = $this->createQueryBuilder('c')
            ->where('c.tenantId = :tid')
            ->andWhere('LOWER(c.companyName) LIKE :q')
            ->setParameter('tid', $tenantId)
            ->setParameter('q', '%' . strtolower($query) . '%')
            ->orderBy('c.companyName', 'ASC');
        return $qb->getQuery()->getResult();
    }
}
