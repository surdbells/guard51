<?php

declare(strict_types=1);

namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Trait for entities that belong to a specific tenant.
 * Provides the tenant_id column, getter, and setter.
 */
trait TenantAwareTrait
{
    #[ORM\Column(name: 'tenant_id', type: 'string', length: 36, nullable: false)]
    private ?string $tenantId = null;

    public function getTenantId(): ?string
    {
        return $this->tenantId;
    }

    public function setTenantId(string $tenantId): static
    {
        $this->tenantId = $tenantId;
        return $this;
    }
}
