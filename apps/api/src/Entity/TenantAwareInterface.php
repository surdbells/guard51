<?php

declare(strict_types=1);

namespace Guard51\Entity;

/**
 * Interface for entities that belong to a specific tenant.
 * Used by the TenantFilter to automatically scope queries.
 */
interface TenantAwareInterface
{
    public function getTenantId(): ?string;
    public function setTenantId(string $tenantId): static;
}
