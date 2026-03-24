<?php

declare(strict_types=1);

namespace Guard51\Filter;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;
use Guard51\Entity\TenantAwareInterface;

/**
 * Doctrine SQL filter that automatically appends WHERE tenant_id = :tenant_id
 * to all queries on entities implementing TenantAwareInterface.
 *
 * Activated per-request by TenantMiddleware after resolving the tenant.
 * Super admin requests disable this filter to see all tenants.
 */
class TenantFilter extends SQLFilter
{
    public function addFilterConstraint(ClassMetadata $targetEntity, string $targetTableAlias): string
    {
        if (!$targetEntity->reflClass?->implementsInterface(TenantAwareInterface::class)) {
            return '';
        }

        try {
            $tenantId = $this->getParameter('tenant_id');
        } catch (\InvalidArgumentException) {
            return '';
        }

        return sprintf('%s.tenant_id = %s', $targetTableAlias, $tenantId);
    }
}
