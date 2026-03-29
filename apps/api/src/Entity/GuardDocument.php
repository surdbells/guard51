<?php

declare(strict_types=1);

namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'guard_documents')]
#[ORM\Index(name: 'idx_gd_guard', columns: ['guard_id'])]
#[ORM\Index(name: 'idx_gd_expiry', columns: ['expiry_date'])]
class GuardDocument
{
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'guard_id', type: 'string', length: 36)]
    private string $guardId;

    #[ORM\Column(name: 'document_type', type: 'string', length: 30, enumType: DocumentType::class)]
    private DocumentType $documentType;

    #[ORM\Column(type: 'string', length: 200)]
    private string $title;

    #[ORM\Column(name: 'file_url', type: 'string', length: 500)]
    private string $fileUrl;

    #[ORM\Column(name: 'issue_date', type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $issueDate = null;

    #[ORM\Column(name: 'expiry_date', type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $expiryDate = null;

    #[ORM\Column(name: 'is_verified', type: 'boolean', options: ['default' => false])]
    private bool $isVerified = false;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    public function __construct() { $this->id = Uuid::uuid4()->toString(); }

    public function getId(): string { return $this->id; }
    public function getGuardId(): string { return $this->guardId; }
    public function getDocumentType(): DocumentType { return $this->documentType; }
    public function getTitle(): string { return $this->title; }
    public function getFileUrl(): string { return $this->fileUrl; }
    public function getIssueDate(): ?\DateTimeImmutable { return $this->issueDate; }
    public function getExpiryDate(): ?\DateTimeImmutable { return $this->expiryDate; }
    public function isVerified(): bool { return $this->isVerified; }
    public function getNotes(): ?string { return $this->notes; }

    public function setGuardId(string $id): static { $this->guardId = $id; return $this; }
    public function setDocumentType(DocumentType $type): static { $this->documentType = $type; return $this; }
    public function setTitle(string $title): static { $this->title = $title; return $this; }
    public function setFileUrl(string $url): static { $this->fileUrl = $url; return $this; }
    public function setIssueDate(?\DateTimeImmutable $d): static { $this->issueDate = $d; return $this; }
    public function setExpiryDate(?\DateTimeImmutable $d): static { $this->expiryDate = $d; return $this; }
    public function setIsVerified(bool $v): static { $this->isVerified = $v; return $this; }
    public function setNotes(?string $notes): static { $this->notes = $notes; return $this; }

    public function isExpired(): bool { return $this->expiryDate !== null && $this->expiryDate < new \DateTimeImmutable(); }
    public function isExpiringSoon(int $days = 30): bool
    {
        if ($this->expiryDate === null) return false;
        $threshold = new \DateTimeImmutable("+{$days} days");
        return $this->expiryDate <= $threshold && !$this->isExpired();
    }
    public function verify(): static { $this->isVerified = true; return $this; }

    public function toArray(): array
    {
        return [
            'id' => $this->id, 'guard_id' => $this->guardId,
            'document_type' => $this->documentType->value, 'document_type_label' => $this->documentType->label(),
            'title' => $this->title, 'file_url' => $this->fileUrl,
            'issue_date' => $this->issueDate?->format('Y-m-d'), 'expiry_date' => $this->expiryDate?->format('Y-m-d'),
            'is_verified' => $this->isVerified, 'is_expired' => $this->isExpired(), 'is_expiring_soon' => $this->isExpiringSoon(),
            'notes' => $this->notes, 'created_at' => $this->createdAt?->format(\DateTimeInterface::ATOM),
        ];
    }
}
