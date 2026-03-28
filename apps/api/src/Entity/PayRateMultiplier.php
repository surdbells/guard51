<?php
declare(strict_types=1);
namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'pay_rate_multipliers')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'idx_prm_tenant', columns: ['tenant_id'])]
class PayRateMultiplier implements TenantAwareInterface
{
    use TenantAwareTrait;
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 100)]
    private string $name;

    #[ORM\Column(type: 'decimal', precision: 4, scale: 2)]
    private string $multiplier;

    #[ORM\Column(type: 'string', length: 20, enumType: PayRateAppliesTo::class)]
    private PayRateAppliesTo $appliesTo;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $isActive = true;

    public function __construct() { $this->id = Uuid::uuid4()->toString(); }

    public function getId(): string { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getMultiplier(): float { return (float) $this->multiplier; }
    public function getAppliesTo(): PayRateAppliesTo { return $this->appliesTo; }

    public function setName(string $n): static { $this->name = $n; return $this; }
    public function setMultiplier(float $m): static { $this->multiplier = (string) $m; return $this; }
    public function setAppliesTo(PayRateAppliesTo $a): static { $this->appliesTo = $a; return $this; }
    public function setIsActive(bool $v): static { $this->isActive = $v; return $this; }

    public function toArray(): array
    {
        return [
            'id' => $this->id, 'tenant_id' => $this->tenantId, 'name' => $this->name,
            'multiplier' => $this->getMultiplier(), 'applies_to' => $this->appliesTo->value,
            'applies_to_label' => $this->appliesTo->label(), 'is_active' => $this->isActive,
            'created_at' => $this->createdAt?->format(\DateTimeInterface::ATOM),
        ];
    }
}
