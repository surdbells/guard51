<?php

declare(strict_types=1);

namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

/**
 * Standing instructions for guards at a specific site.
 * Post orders define what guards should do, patrol routes, access control rules, etc.
 */
#[ORM\Entity]
#[ORM\Table(name: 'post_orders')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'idx_po_tenant', columns: ['tenant_id'])]
#[ORM\Index(name: 'idx_po_site', columns: ['site_id'])]
#[ORM\Index(name: 'idx_po_active', columns: ['is_active'])]
class PostOrder implements TenantAwareInterface
{
    use TenantAwareTrait;
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'site_id', type: 'string', length: 36)]
    private string $siteId;

    #[ORM\Column(type: 'string', length: 200)]
    private string $title;

    #[ORM\Column(type: 'text')]
    private string $instructions;

    #[ORM\Column(type: 'string', length: 20, enumType: PostOrderPriority::class)]
    private PostOrderPriority $priority = PostOrderPriority::MEDIUM;

    #[ORM\Column(type: 'string', length: 30, enumType: PostOrderCategory::class)]
    private PostOrderCategory $category = PostOrderCategory::GENERAL;

    #[ORM\Column(name: 'effective_from', type: 'date_immutable')]
    private \DateTimeImmutable $effectiveFrom;

    #[ORM\Column(name: 'effective_to', type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $effectiveTo = null;

    #[ORM\Column(name: 'is_active', type: 'boolean', nullable: false, options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(name: 'created_by', type: 'string', length: 36)]
    private string $createdBy;

    #[ORM\Column(name: 'last_updated_by', type: 'string', length: 36, nullable: true)]
    private ?string $lastUpdatedBy = null;

    #[ORM\Column(type: 'integer', options: ['default' => 1])]
    private int $version = 1;

    public function __construct()
    {
        $this->id = Uuid::uuid4()->toString();
        $this->effectiveFrom = new \DateTimeImmutable();
    }

    // ── Getters ──────────────────────────────────────

    public function getId(): string { return $this->id; }
    public function getSiteId(): string { return $this->siteId; }
    public function getTitle(): string { return $this->title; }
    public function getInstructions(): string { return $this->instructions; }
    public function getPriority(): PostOrderPriority { return $this->priority; }
    public function getCategory(): PostOrderCategory { return $this->category; }
    public function getEffectiveFrom(): \DateTimeImmutable { return $this->effectiveFrom; }
    public function getEffectiveTo(): ?\DateTimeImmutable { return $this->effectiveTo; }
    public function isActive(): bool { return $this->isActive; }
    public function getCreatedBy(): string { return $this->createdBy; }
    public function getLastUpdatedBy(): ?string { return $this->lastUpdatedBy; }
    public function getVersion(): int { return $this->version; }

    // ── Setters ──────────────────────────────────────

    public function setSiteId(string $siteId): static { $this->siteId = $siteId; return $this; }
    public function setTitle(string $title): static { $this->title = $title; return $this; }
    public function setInstructions(string $instructions): static { $this->instructions = $instructions; return $this; }
    public function setPriority(PostOrderPriority $priority): static { $this->priority = $priority; return $this; }
    public function setCategory(PostOrderCategory $category): static { $this->category = $category; return $this; }
    public function setEffectiveFrom(\DateTimeImmutable $date): static { $this->effectiveFrom = $date; return $this; }
    public function setEffectiveTo(?\DateTimeImmutable $date): static { $this->effectiveTo = $date; return $this; }
    public function setIsActive(bool $active): static { $this->isActive = $active; return $this; }
    public function setCreatedBy(string $userId): static { $this->createdBy = $userId; return $this; }

    // ── Business Logic ───────────────────────────────

    public function updateContent(string $title, string $instructions, string $updatedBy): static
    {
        $this->title = $title;
        $this->instructions = $instructions;
        $this->lastUpdatedBy = $updatedBy;
        $this->version++;
        return $this;
    }

    public function isExpired(): bool
    {
        if ($this->effectiveTo === null) return false;
        return $this->effectiveTo < new \DateTimeImmutable();
    }

    public function isCurrentlyEffective(): bool
    {
        $now = new \DateTimeImmutable();
        if ($this->effectiveFrom > $now) return false;
        if ($this->effectiveTo !== null && $this->effectiveTo < $now) return false;
        return $this->isActive;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenantId,
            'site_id' => $this->siteId,
            'title' => $this->title,
            'instructions' => $this->instructions,
            'priority' => $this->priority->value,
            'priority_label' => $this->priority->label(),
            'category' => $this->category->value,
            'category_label' => $this->category->label(),
            'effective_from' => $this->effectiveFrom->format('Y-m-d'),
            'effective_to' => $this->effectiveTo?->format('Y-m-d'),
            'is_active' => $this->isActive,
            'is_currently_effective' => $this->isCurrentlyEffective(),
            'version' => $this->version,
            'created_by' => $this->createdBy,
            'last_updated_by' => $this->lastUpdatedBy,
            'created_at' => $this->createdAt?->format(\DateTimeInterface::ATOM),
            'updated_at' => $this->updatedAt?->format(\DateTimeInterface::ATOM),
        ];
    }
}
