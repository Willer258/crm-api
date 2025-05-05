<?php

namespace App\Entity;

use App\Repository\PipelineStepRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PipelineStepRepository::class)]
class PipelineStep
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
    private ?float $successProbabity = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lostDate = null;

    #[ORM\Column]
    private ?int $rank = null;

    #[ORM\ManyToOne(inversedBy: 'pipelineSteps')]
    private ?Pipeline $pipeline = null;

    /**
     * @var Collection<int, Deal>
     */
    #[ORM\OneToMany(targetEntity: Deal::class, mappedBy: 'step')]
    private Collection $deals;

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

    public function getSuccessProbabity(): ?float
    {
        return $this->successProbabity;
    }

    public function setSuccessProbabity(?float $successProbabity): static
    {
        $this->successProbabity = $successProbabity;

        return $this;
    }

    public function getLostDate(): ?\DateTimeInterface
    {
        return $this->lostDate;
    }

    public function setLostDate(?\DateTimeInterface $lostDate): static
    {
        $this->lostDate = $lostDate;

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
}
