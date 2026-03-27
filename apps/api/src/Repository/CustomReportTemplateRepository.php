<?php
declare(strict_types=1);
namespace Guard51\Repository;

use Guard51\Entity\CustomReportTemplate;

/** @extends BaseRepository<CustomReportTemplate> */
class CustomReportTemplateRepository extends BaseRepository
{
    protected function getEntityClass(): string { return CustomReportTemplate::class; }
    public function findByTenant(string $tenantId): array { return $this->findBy(['tenantId' => $tenantId], ['name' => 'ASC']); }
    public function findActiveByTenant(string $tenantId): array { return $this->findBy(['tenantId' => $tenantId, 'isActive' => true], ['name' => 'ASC']); }
}
