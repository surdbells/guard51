<?php
declare(strict_types=1);
namespace Guard51\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'help_articles')]
class HelpArticle
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 200)]
    private string $title;

    #[ORM\Column(type: 'string', length: 100)]
    private string $category;

    #[ORM\Column(type: 'text')]
    private string $content;

    #[ORM\Column(name: 'sort_order', type: 'integer')]
    private int $sortOrder = 0;

    #[ORM\Column(name: 'is_published', type: 'boolean')]
    private bool $isPublished = true;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->id = Uuid::uuid4()->toString();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): string { return $this->id; }
    public function getTitle(): string { return $this->title; }
    public function setTitle(string $v): static { $this->title = $v; return $this; }
    public function getCategory(): string { return $this->category; }
    public function setCategory(string $v): static { $this->category = $v; return $this; }
    public function getContent(): string { return $this->content; }
    public function setContent(string $v): static { $this->content = $v; return $this; }
    public function getSortOrder(): int { return $this->sortOrder; }
    public function setSortOrder(int $v): static { $this->sortOrder = $v; return $this; }
    public function getIsPublished(): bool { return $this->isPublished; }
    public function setIsPublished(bool $v): static { $this->isPublished = $v; return $this; }

    public function toArray(): array
    {
        return [
            'id' => $this->id, 'title' => $this->title, 'category' => $this->category,
            'content' => $this->content, 'sort_order' => $this->sortOrder,
            'is_published' => $this->isPublished,
            'created_at' => $this->createdAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
