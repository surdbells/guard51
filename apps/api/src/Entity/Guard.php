<?php

declare(strict_types=1);

namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

/**
 * Guard profile — the operational record for a security guard.
 * Links to User entity via user_id for authentication.
 */
#[ORM\Entity]
#[ORM\Table(name: 'guards')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'idx_guard_tenant', columns: ['tenant_id'])]
#[ORM\Index(name: 'idx_guard_user', columns: ['user_id'])]
#[ORM\Index(name: 'idx_guard_status', columns: ['status'])]
#[ORM\UniqueConstraint(name: 'uq_guard_employee', columns: ['tenant_id', 'employee_number'])]
class Guard implements TenantAwareInterface
{
    use TenantAwareTrait;
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'user_id', type: 'string', length: 36, nullable: true)]
    private ?string $userId = null;

    #[ORM\Column(name: 'employee_number', type: 'string', length: 50)]
    private string $employeeNumber;

    #[ORM\Column(name: 'first_name', type: 'string', length: 100)]
    private string $firstName;

    #[ORM\Column(name: 'last_name', type: 'string', length: 100)]
    private string $lastName;

    #[ORM\Column(type: 'string', length: 50)]
    private string $phone;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(name: 'date_of_birth', type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $dateOfBirth = null;

    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    private ?string $gender = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $address = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $state = null;

    #[ORM\Column(name: 'photo_url', type: 'string', length: 500, nullable: true)]
    private ?string $photoUrl = null;

    #[ORM\Column(name: 'emergency_contact_name', type: 'string', length: 200, nullable: true)]
    private ?string $emergencyContactName = null;

    #[ORM\Column(name: 'emergency_contact_phone', type: 'string', length: 50, nullable: true)]
    private ?string $emergencyContactPhone = null;

    #[ORM\Column(name: 'hire_date', type: 'date_immutable')]
    private \DateTimeImmutable $hireDate;

    #[ORM\Column(type: 'string', length: 20, enumType: GuardStatus::class)]
    private GuardStatus $status = GuardStatus::ACTIVE;

    #[ORM\Column(name: 'pay_type', type: 'string', length: 20, enumType: PayType::class, nullable: true)]
    private ?PayType $payType = null;

    #[ORM\Column(name: 'pay_rate', type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $payRate = null;

    #[ORM\Column(name: 'bank_name', type: 'string', length: 100, nullable: true)]
    private ?string $bankName = null;

    #[ORM\Column(name: 'bank_account_number', type: 'string', length: 20, nullable: true)]
    private ?string $bankAccountNumber = null;

    #[ORM\Column(name: 'bank_account_name', type: 'string', length: 200, nullable: true)]
    private ?string $bankAccountName = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    public function __construct()
    {
        $this->id = Uuid::uuid4()->toString();
        $this->hireDate = new \DateTimeImmutable();
    }

    // ── Getters ──────────────────────────────────────

    public function getId(): string { return $this->id; }
    public function getUserId(): ?string { return $this->userId; }
    public function getEmployeeNumber(): string { return $this->employeeNumber; }
    public function getFirstName(): string { return $this->firstName; }
    public function getLastName(): string { return $this->lastName; }
    public function getFullName(): string { return "{$this->firstName} {$this->lastName}"; }
    public function getPhone(): string { return $this->phone; }
    public function getEmail(): ?string { return $this->email; }
    public function getDateOfBirth(): ?\DateTimeImmutable { return $this->dateOfBirth; }
    public function getGender(): ?string { return $this->gender; }
    public function getAddress(): ?string { return $this->address; }
    public function getCity(): ?string { return $this->city; }
    public function getState(): ?string { return $this->state; }
    public function getPhotoUrl(): ?string { return $this->photoUrl; }
    public function getEmergencyContactName(): ?string { return $this->emergencyContactName; }
    public function getEmergencyContactPhone(): ?string { return $this->emergencyContactPhone; }
    public function getHireDate(): \DateTimeImmutable { return $this->hireDate; }
    public function getStatus(): GuardStatus { return $this->status; }
    public function getPayType(): ?PayType { return $this->payType; }
    public function getPayRate(): ?float { return $this->payRate !== null ? (float) $this->payRate : null; }
    public function getBankName(): ?string { return $this->bankName; }
    public function getBankAccountNumber(): ?string { return $this->bankAccountNumber; }
    public function getBankAccountName(): ?string { return $this->bankAccountName; }
    public function getNotes(): ?string { return $this->notes; }

    // ── Setters ──────────────────────────────────────

    public function setUserId(?string $userId): static { $this->userId = $userId; return $this; }
    public function setEmployeeNumber(string $num): static { $this->employeeNumber = $num; return $this; }
    public function setFirstName(string $name): static { $this->firstName = $name; return $this; }
    public function setLastName(string $name): static { $this->lastName = $name; return $this; }
    public function setPhone(string $phone): static { $this->phone = $phone; return $this; }
    public function setEmail(?string $email): static { $this->email = $email; return $this; }
    public function setDateOfBirth(?\DateTimeImmutable $dob): static { $this->dateOfBirth = $dob; return $this; }
    public function setGender(?string $gender): static { $this->gender = $gender; return $this; }
    public function setAddress(?string $addr): static { $this->address = $addr; return $this; }
    public function setCity(?string $city): static { $this->city = $city; return $this; }
    public function setState(?string $state): static { $this->state = $state; return $this; }
    public function setPhotoUrl(?string $url): static { $this->photoUrl = $url; return $this; }
    public function setEmergencyContactName(?string $name): static { $this->emergencyContactName = $name; return $this; }
    public function setEmergencyContactPhone(?string $phone): static { $this->emergencyContactPhone = $phone; return $this; }
    public function setHireDate(\DateTimeImmutable $date): static { $this->hireDate = $date; return $this; }
    public function setStatus(GuardStatus $status): static { $this->status = $status; return $this; }
    public function setPayType(?PayType $payType): static { $this->payType = $payType; return $this; }
    public function setPayRate(?float $rate): static { $this->payRate = $rate !== null ? (string) $rate : null; return $this; }
    public function setBankName(?string $name): static { $this->bankName = $name; return $this; }
    public function setBankAccountNumber(?string $num): static { $this->bankAccountNumber = $num; return $this; }
    public function setBankAccountName(?string $name): static { $this->bankAccountName = $name; return $this; }
    public function setNotes(?string $notes): static { $this->notes = $notes; return $this; }

    // ── Business Logic ───────────────────────────────

    public function activate(): static { $this->status = GuardStatus::ACTIVE; return $this; }
    public function suspend(): static { $this->status = GuardStatus::SUSPENDED; return $this; }
    public function terminate(): static { $this->status = GuardStatus::TERMINATED; return $this; }
    public function canBeAssigned(): bool { return $this->status->canBeAssigned(); }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenantId,
            'user_id' => $this->userId,
            'employee_number' => $this->employeeNumber,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'full_name' => $this->getFullName(),
            'phone' => $this->phone,
            'email' => $this->email,
            'date_of_birth' => $this->dateOfBirth?->format('Y-m-d'),
            'gender' => $this->gender,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'photo_url' => $this->photoUrl,
            'emergency_contact_name' => $this->emergencyContactName,
            'emergency_contact_phone' => $this->emergencyContactPhone,
            'hire_date' => $this->hireDate->format('Y-m-d'),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'pay_type' => $this->payType?->value,
            'pay_rate' => $this->getPayRate(),
            'bank_name' => $this->bankName,
            'bank_account_number' => $this->bankAccountNumber,
            'bank_account_name' => $this->bankAccountName,
            'notes' => $this->notes,
            'created_at' => $this->createdAt?->format(\DateTimeInterface::ATOM),
            'updated_at' => $this->updatedAt?->format(\DateTimeInterface::ATOM),
        ];
    }
}
