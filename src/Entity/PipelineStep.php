<?php

namespace App\Entity;

use App\Repository\PipelineStepRepository;
use App\Traits\UserObjectTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: PipelineStepRepository::class)]
class PipelineStep
{

    use UserObjectTrait;
    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['pipelineStep:list', 'pipelineStep:edit','pipeline:list','pipeline:info','deal:edit' , 'contact:info' , 'company:info' , 'deal:info'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['pipelineStep:list', 'pipelineStep:edit','pipeline:list','pipeline:info','deal:edit' , 'contact:info', 'company:info', 'deal:info'])]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['pipelineStep:list', 'pipelineStep:edit','pipeline:info'])]
    private ?string $description = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['pipelineStep:list', 'pipelineStep:edit','pipeline:info'])]
    private ?float $successProbability = null;

    #[ORM\ManyToOne(inversedBy: 'pipelineSteps')]
    #[Groups(['deal:info'])]
        private ?Pipeline $pipeline = null;

    /**
     * @var Collection<int, Deal>
     */
    #[ORM\OneToMany(targetEntity: Deal::class, mappedBy: 'step')]
    #[Groups(['pipelineStep:list','pipeline:info'])]
    private Collection $deals;

    #[ORM\Column(length: 255)]
    #[Groups(['pipelineStep:list', 'pipelineStep:edit','pipeline:info'])]
    private ?string $color = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['pipelineStep:list', 'pipelineStep:edit','pipeline:info'])]
    private ?string $ranking = null;

    public function __construct()
    {
        $this->deals = new ArrayCollection();
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

    public function getSuccessProbability(): ?float
    {
        return $this->successProbability;
    }

    public function setSuccessProbability(?float $successProbability): static
    {
        $this->successProbability = $successProbability;

        return $this;
    }

    public function getPipeline(): ?Pipeline
    {
        return $this->pipeline;
    }

    public function setPipeline(?Pipeline $pipeline): static
    {
        $this->pipeline = $pipeline;

        return $this;
    }

    /**
     * @return Collection<int, Deal>
     */
    public function getDeals(): Collection
    {
        return $this->deals;
    }

    public function addDeal(Deal $deal): static
    {
        if (!$this->deals->contains($deal)) {
            $this->deals->add($deal);
            $deal->setStep($this);
        }

        return $this;
    }

    public function removeDeal(Deal $deal): static
    {
        if ($this->deals->removeElement($deal)) {
            // set the owning side to null (unless already changed)
            if ($deal->getStep() === $this) {
                $deal->setStep(null);
            }
        }

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(string $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function getRanking(): ?string
    {
        return $this->ranking;
    }

    public function setRanking(?string $ranking): static
    {
        $this->ranking = $ranking;

        return $this;
    }
}
