<?php

declare(strict_types=1);

namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

/**
 * Tracks resource usage per tenant for plan limit enforcement.
 * Updated on every guard/site/client creation, recalculated nightly.
 */
#[ORM\Entity]
#[ORM\Table(name: 'tenant_usage_metrics')]
#[ORM\HasLifecycleCallbacks]
#[ORM\UniqueConstraint(name: 'uq_tum_tenant', columns: ['tenant_id'])]
class TenantUsageMetric implements TenantAwareInterface
{
    use TenantAwareTrait;
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'guards_count', type: 'integer', options: ['default' => 0])]
    private int $guardsCount = 0;

    #[ORM\Column(name: 'sites_count', type: 'integer', options: ['default' => 0])]
    private int $sitesCount = 0;

    #[ORM\Column(name: 'clients_count', type: 'integer', options: ['default' => 0])]
    private int $clientsCount = 0;

    #[ORM\Column(name: 'staff_count', type: 'integer', options: ['default' => 0])]
    private int $staffCount = 0;

    #[ORM\Column(name: 'reports_this_month', type: 'integer', options: ['default' => 0])]
    private int $reportsThisMonth = 0;

    #[ORM\Column(name: 'storage_used_bytes', type: 'integer', options: ['default' => 0])]
    private int $storageUsedBytes = 0;

    #[ORM\Column(name: 'sms_used_this_month', type: 'integer', options: ['default' => 0])]
    private int $smsUsedThisMonth = 0;

    #[ORM\Column(name: 'last_recalculated_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastRecalculatedAt = null;

    public function __construct()
    {
        $this->id = Uuid::uuid4()->toString();
    }

    public function getId(): string { return $this->id; }
    public function getGuardsCount(): int { return $this->guardsCount; }
    public function getSitesCount(): int { return $this->sitesCount; }
    public function getClientsCount(): int { return $this->clientsCount; }
    public function getStaffCount(): int { return $this->staffCount; }
    public function getReportsThisMonth(): int { return $this->reportsThisMonth; }
    public function getStorageUsedBytes(): int { return $this->storageUsedBytes; }
    public function getSmsUsedThisMonth(): int { return $this->smsUsedThisMonth; }

    public function setGuardsCount(int $count): static { $this->guardsCount = $count; return $this; }
    public function setSitesCount(int $count): static { $this->sitesCount = $count; return $this; }
    public function setClientsCount(int $count): static { $this->clientsCount = $count; return $this; }
    public function setStaffCount(int $count): static { $this->staffCount = $count; return $this; }
    public function setReportsThisMonth(int $count): static { $this->reportsThisMonth = $count; return $this; }
    public function setStorageUsedBytes(int $bytes): static { $this->storageUsedBytes = $bytes; return $this; }
    public function setSmsUsedThisMonth(int $count): static { $this->smsUsedThisMonth = $count; return $this; }

    public function incrementGuards(): static { $this->guardsCount++; return $this; }
    public function decrementGuards(): static { $this->guardsCount = max(0, $this->guardsCount - 1); return $this; }
    public function incrementSites(): static { $this->sitesCount++; return $this; }
    public function decrementSites(): static { $this->sitesCount = max(0, $this->sitesCount - 1); return $this; }
    public function incrementClients(): static { $this->clientsCount++; return $this; }
    public function decrementClients(): static { $this->clientsCount = max(0, $this->clientsCount - 1); return $this; }

    public function markRecalculated(): static
    {
        $this->lastRecalculatedAt = new \DateTimeImmutable();
        return $this;
    }

    /**
     * Check if adding one more guard would exceed the plan limit.
     */
    public function wouldExceedGuardLimit(int $planMax): bool
    {
        return $this->guardsCount >= $planMax;
    }

    public function wouldExceedSiteLimit(int $planMax): bool
    {
        return $this->sitesCount >= $planMax;
    }

    public function wouldExceedClientLimit(int $planMax): bool
    {
        return $this->clientsCount >= $planMax;
    }

    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'guards_count' => $this->guardsCount,
            'sites_count' => $this->sitesCount,
            'clients_count' => $this->clientsCount,
            'staff_count' => $this->staffCount,
            'reports_this_month' => $this->reportsThisMonth,
            'storage_used_bytes' => $this->storageUsedBytes,
            'sms_used_this_month' => $this->smsUsedThisMonth,
            'last_recalculated_at' => $this->lastRecalculatedAt?->format(\DateTimeInterface::ATOM),
            'updated_at' => $this->updatedAt?->format(\DateTimeInterface::ATOM),
        ];
    }
}
