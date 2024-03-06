<?php

namespace App\Entity\Therapy;

use App\Repository\Therapy\LabelRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LabelRepository::class)]
class Label
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id;

    #[ORM\Column(type: 'string', length: 50)]
    private ?string $shortName;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $reportName;

    #[ORM\ManyToMany(targetEntity: Stub::class, inversedBy: 'labels')]
    private ?Collection $stubs;

    public function __construct()
    {
        $this->stubs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getShortName(): ?string
    {
        return $this->shortName;
    }

    public function setShortName(string $shortName): self
    {
        $this->shortName = $shortName;

        return $this;
    }

    public function getReportName(): ?string
    {
        return $this->reportName;
    }

    public function setReportName(?string $reportName): self
    {
        $this->reportName = $reportName;

        return $this;
    }

    public function getStubs(): Collection
    {
        return $this->stubs;
    }

    public function addStub(Stub $stub): self
    {
        if (!$this->stubs->contains($stub)) {
            $this->stubs[] = $stub;
        }

        return $this;
    }

    public function removeStub(Stub $stub): self
    {
        $this->stubs->removeElement($stub);

        return $this;
    }

    public function __toString(): string
    {
        return $this->getShortName();
    }
}
