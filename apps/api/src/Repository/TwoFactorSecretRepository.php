<?php
declare(strict_types=1);
namespace Guard51\Repository;
use Guard51\Entity\TwoFactorSecret;

/** @extends BaseRepository<TwoFactorSecret> */
class TwoFactorSecretRepository extends BaseRepository
{
    protected function getEntityClass(): string { return TwoFactorSecret::class; }
}
