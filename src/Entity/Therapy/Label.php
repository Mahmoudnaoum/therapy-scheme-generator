<?php

namespace App\Entity\Therapy;

use App\Repository\Therapy\LabelRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
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

    #[ORM\OneToMany(mappedBy: "label", targetEntity: LabelStub::class, cascade: ["persist", "remove"])]
    private ?Collection $labelStubs;

    public function __construct()
    {
        $this->labelStubs = new ArrayCollection();
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

    public function getLabelStubs(): ?Collection
    {
        return $this->labelStubs;
    }

    public function addLabelStub(LabelStub $labelStub): self
    {
        if (!$this->labelStubs->contains($labelStub)) {
            $this->labelStubs[] = $labelStub;
            $labelStub->setLabel($this);
        }

        return $this;
    }

    public function removeLabelStub(LabelStub $labelStub): self
    {
        if ($this->labelStubs->removeElement($labelStub)) {
            // set the owning side to null (unless already changed)
            if ($labelStub->getLabel() === $this) {
                $labelStub->setLabel(null);
            }
        }

        return $this;
    }

    public function getStubs(): Collection
    {
        $stubs = new ArrayCollection();
        foreach ($this->labelStubs as $labelStub) {
            $stubs[] = $labelStub->getStub();
        }

        return $stubs;
    }

    public function getStubsSortedByPosition(): Collection
    {
        $labelStubs = $this->labelStubs->toArray();

        usort($labelStubs, function ($a, $b) {
            // Order by position if both positions are not zero.
            if ($a->getPosition() !== 0 && $b->getPosition() !== 0) {
                return $a->getPosition() <=> $b->getPosition();
            }
    
            // If one has position not equal to zero, prioritize it.
            if ($a->getPosition() !== 0) {
                return -1;
            }
    
            if ($b->getPosition() !== 0) {
                return 1;
            }
    
            // If both have position equal to zero, maintain their original order.
            return 0;
        });

        $stubs = new ArrayCollection();
        foreach ($labelStubs as $labelStub) {
            $stubs[] = $labelStub->getStub();
        }

        return $stubs;
    }

    public function addStub(Stub $stub): void
    {
        foreach ($this->labelStubs as $labelStub) {
            if ($labelStub->getStub() === $stub) {
                return;
            }
        }
        $labelStub = new LabelStub();
        $labelStub->setLabel($this);
        $labelStub->setStub($stub);
        $this->labelStubs[] = $labelStub;        
    }

    public function deleteStub(Stub $stub, EntityManagerInterface $entityManager): void
    {
        // Remove the LabelStub entities associated with the given Stub
        
        foreach ($this->labelStubs as $labelStub) {
            if ($labelStub->getStub() === $stub) {
                $entityManager->remove($labelStub);
            }
        }
    
        // Flush the changes to the database
        $entityManager->flush();
    }

    public function __toString(): string
    {
        return $this->getShortName();
    }
}
