<?php

namespace App\Entity\Therapy;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\Therapy\SchemeRepository;

#[ORM\Entity(repositoryClass: SchemeRepository::class)]
class Scheme
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column]
    private array $targets = [];

    #[ORM\Column]
    private array $comments = [];

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column]
    private ?bool $suppress = null;

    #[ORM\Column]
    private ?bool $excerpt = null;

    #[ORM\Column]
    private array $selectedLabels = [];

    public function __toString(): string
    {
        return $this->getName();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getTargets(): array
    {
        return $this->targets;
    }

    public function setTargets(array $targets): self
    {
        $this->targets = $targets;

        return $this;
    }

    public function getComments(): array
    {
        return $this->comments;
    }

    public function setComments(array $comments): self
    {
        $this->comments = $comments;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function isSuppress(): ?bool
    {
        return $this->suppress;
    }

    public function setSuppress(bool $suppress): self
    {
        $this->suppress = $suppress;

        return $this;
    }

    public function isExcerpt(): ?bool
    {
        return $this->excerpt;
    }

    public function setExcerpt(bool $excerpt): self
    {
        $this->excerpt = $excerpt;

        return $this;
    }

    public function getSelectedLabels(): array
    {
        return $this->selectedLabels;
    }

    public function setSelectedLabels(array $selectedLabels): self
    {
        $this->selectedLabels = $selectedLabels;

        return $this;
    }

}
