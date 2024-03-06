<?php

namespace App\Entity\Therapy;

use App\Repository\LabelStubRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LabelStubRepository::class)]

class LabelStub
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id;

    #[ORM\ManyToOne(targetEntity: Label::class, inversedBy: "labelStubs")]
    #[ORM\JoinColumn(nullable: false)]
    private ?Label $label;

    #[ORM\ManyToOne(targetEntity: Stub::class, inversedBy: "labelStubs")]
    #[ORM\JoinColumn(nullable: false)]
    private ?Stub $stub;

    #[ORM\Column(type: 'integer', options: ['default' => '0'])]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private ?int $position;

    public function __construct()
    {
        // Set a default position, you can modify this based on your logic
        $this->position = 0;
    }


    // ... additional fields or methods ...

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLabel(): ?Label
    {
        return $this->label;
    }

    public function setLabel(?Label $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function getStub(): ?Stub
    {
        return $this->stub;
    }

    public function setStub(?Stub $stub): self
    {
        $this->stub = $stub;

        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): self
    {
        $this->position = $position;

        return $this;
    }
}
