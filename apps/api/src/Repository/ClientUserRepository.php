<?php
declare(strict_types=1);
namespace Guard51\Repository;
use Guard51\Entity\ClientUser;

/** @extends BaseRepository<ClientUser> */
class ClientUserRepository extends BaseRepository
{
    protected function getEntityClass(): string { return ClientUser::class; }
    public function findByUserId(string $userId): ?ClientUser { return $this->findOneBy(['userId' => $userId]); }
    public function findByClient(string $clientId): array { return $this->findBy(['clientId' => $clientId]); }
}
