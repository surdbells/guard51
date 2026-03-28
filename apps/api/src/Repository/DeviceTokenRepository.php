<?php
declare(strict_types=1);
namespace Guard51\Repository;
use Guard51\Entity\DeviceToken;

/** @extends BaseRepository<DeviceToken> */
class DeviceTokenRepository extends BaseRepository
{
    protected function getEntityClass(): string { return DeviceToken::class; }
    public function findActiveByUser(string $userId): array { return $this->findBy(['userId' => $userId, 'isActive' => true]); }
}
