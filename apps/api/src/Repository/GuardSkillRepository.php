<?php

declare(strict_types=1);

namespace Guard51\Repository;

use Guard51\Entity\GuardSkill;

/** @extends BaseRepository<GuardSkill> */
class GuardSkillRepository extends BaseRepository
{
    protected function getEntityClass(): string { return GuardSkill::class; }

    /** @return GuardSkill[] */
    public function findByTenant(string $tenantId): array { return $this->findBy(['tenantId' => $tenantId], ['name' => 'ASC']); }
    public function findByName(string $tenantId, string $name): ?GuardSkill { return $this->findOneBy(['tenantId' => $tenantId, 'name' => $name]); }
}
