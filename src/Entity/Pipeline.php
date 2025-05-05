<?php

namespace App\Entity;

use App\Repository\PipelineRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PipelineRepository::class)]
class Pipeline
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(nullable: true)]
    private ?array $roles = null;

    #[ORM\Column]
    private ?int $rank = null;

    /**
     * @var Collection<int, PipelineStep>
     */
    #[ORM\OneToMany(targetEntity: PipelineStep::class, mappedBy: 'pipeline')]
    private Collection $pipelineSteps;

    public function __construct()
    {
        $this->pipelineSteps = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getRoles(): ?array
    {
        return $this->roles;
    }

    public function setRoles(?array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function getRank(): ?int
    {
        return $this->rank;
    }

    public function setRank(int $rank): static
    {
        $this->rank = $rank;

        return $this;
    }

    /**
     * @return Collection<int, PipelineStep>
     */
    public function getPipelineSteps(): Collection
    {
        return $this->pipelineSteps;
    }

    public function addPipelineStep(PipelineStep $pipelineStep): static
    {
        if (!$this->pipelineSteps->contains($pipelineStep)) {
            $this->pipelineSteps->add($pipelineStep);
            $pipelineStep->setPipeline($this);
        }

        return $this;
    }

    public function removePipelineStep(PipelineStep $pipelineStep): static
    {
        if ($this->pipelineSteps->removeElement($pipelineStep)) {
            // set the owning side to null (unless already changed)
            if ($pipelineStep->getPipeline() === $this) {
                $pipelineStep->setPipeline(null);
            }
        }

        return $this;
    }
}
