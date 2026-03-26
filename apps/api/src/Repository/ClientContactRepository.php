<?php

declare(strict_types=1);

namespace Guard51\Repository;

use Guard51\Entity\ClientContact;

/** @extends BaseRepository<ClientContact> */
class ClientContactRepository extends BaseRepository
{
    protected function getEntityClass(): string { return ClientContact::class; }
    public function findByClient(string $clientId): array { return $this->findBy(['clientId' => $clientId], ['isPrimary' => 'DESC', 'name' => 'ASC']); }
}
