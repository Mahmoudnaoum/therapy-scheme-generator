<?php

namespace App\Entity\Therapy;

use App\Repository\Therapy\StubRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StubRepository::class)]
class Stub
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $name;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $excerpt;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $background;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $isDeleted;

    #[ORM\OneToMany(mappedBy: "stub", targetEntity: LabelStub::class, cascade: ["persist", "remove"])]
    private ?Collection $labelStubs;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->labelStubs = new ArrayCollection();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription($description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getExcerpt(): ?string
    {
        return $this->excerpt;
    }

    public function setExcerpt($excerpt): self
    {
        $this->excerpt = $excerpt;

        return $this;
    }

    public function getBackground(): ?string
    {
        return $this->background;
    }

    public function setBackground($background): self
    {
        $this->background = $background;

        return $this;
    }

    public function getIsDeleted(): ?bool
    {
        return $this->isDeleted;
    }

    public function setIsDeleted(bool $isDeleted): self
    {
        $this->isDeleted = $isDeleted;

        return $this;
    }

    public function getLabelStubs(): ?Collection
    {
        return $this->labelStubs;
    }

    public function addLabelStub(LabelStub $labelStub): self
    {
        if (!$this->labelStubs->contains($labelStub)) {
            $this->labelStubs[] = $labelStub;
            $labelStub->setStub($this);
        }

        return $this;
    }

    public function removeLabelStub(LabelStub $labelStub): self
    {
        if ($this->labelStubs->removeElement($labelStub)) {
            // set the owning side to null (unless already changed)
            if ($labelStub->getStub() === $this) {
                $labelStub->setStub(null);
            }
        }

        return $this;
    }

    public function getLabels(): Collection
    {
        $labels = new ArrayCollection();
        foreach ($this->labelStubs as $labelStub) {
            $labels[] = $labelStub->getLabel();
        }

        return $labels;
    }

    public function addLabel(Label $label): void
    {
        $labelStub = new LabelStub();
        $labelStub->setLabel($label);
        $labelStub->setStub($this);

        $this->labelStubs[] = $labelStub;
    }

    public function removeLabel(Label $label): void
    {
        foreach ($this->labelStubs as $labelStub) {
            if ($labelStub->getLabel() === $label) {
                $this->labelStubs->removeElement($labelStub);
                break;
            }
        }
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

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updatedDateTime(): void
    {
        $dateTimeNow = new \DateTimeImmutable('now');

        $this->setUpdatedAt($dateTimeNow);

        if ($this->getCreatedAt() === null) {
            $this->setCreatedAt($dateTimeNow);
        }
    }
}
