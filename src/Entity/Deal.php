<?php

namespace App\Entity;

use App\Repository\DealRepository;
use App\Traits\UserObjectTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: DealRepository::class)]
class Deal
{

    use UserObjectTrait;
    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['contact:info' , 'deal:info', 'company:info'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'deals')]
    #[Groups(['deal:info'])]
    private ?Contact $contact = null;

    #[ORM\Column(length: 255)]
    #[Groups(['contact:info', 'company:info', 'deal:info'])]

    private ?string $object = null;

    #[ORM\Column(length: 255)]
    #[Groups(['contact:info', 'company:info', 'deal:info'])]
    private ?string $manager = null;

    #[ORM\ManyToOne(inversedBy: 'deals')]
    #[Groups(['contact:info', 'company:info', 'deal:info'])]
    private ?PipelineStep $step = null;

    /**
     * @var Collection<int, Tag>
     */
    #[ORM\ManyToMany(targetEntity: Tag::class, mappedBy: 'deals')]
    #[Groups(['contact:info', 'company:info', 'deal:info'])]
    private Collection $tags;

    /**
     * @var Collection<int, Contact>
     */
    #[ORM\ManyToMany(targetEntity: Contact::class, inversedBy: 'dealsAsParticipant')]
    #[Groups(['deal:info'])]
    private Collection $participants;

    /**
     * @var Collection<int, Activity>
     */
    #[ORM\OneToMany(targetEntity: Activity::class, mappedBy: 'deal')]
    #[Groups(['deal:info'])]
    private Collection $activities;

    #[ORM\Column(nullable: true)]
    private ?array $products = null;

    /**
     * @var Collection<int, Asset>
     */
    #[ORM\OneToMany(targetEntity: Asset::class, mappedBy: 'deal')]
    #[Groups(['deal:info'])]
    private Collection $assets;

    /**
     * @var Collection<int, Note>
     */
    #[ORM\OneToMany(targetEntity: Note::class, mappedBy: 'deal')]
    #[Groups(['deal:info'])]
    private Collection $notes;

    

    
    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['contact:info', 'company:info'])]
    public const STATUS_WIN = 'win';
    public const STATUS_LOST = 'lost';


    #[Groups(['contact:info', 'company:info'])]
    private ?string $status = null;

    #[ORM\ManyToOne(inversedBy: 'deals')]
    #[Groups(['deal:info'])]
    private ?Company $company = null;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
        $this->participants = new ArrayCollection();
        $this->activities = new ArrayCollection();
        $this->assets = new ArrayCollection();
        $this->notes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContact(): ?Contact
    {
        return $this->contact;
    }

    public function setContact(?Contact $contact): static
    {
        $this->contact = $contact;

        return $this;
    }

    public function getObject(): ?string
    {
        return $this->object;
    }

    public function setObject(string $object): static
    {
        $this->object = $object;

        return $this;
    }

    public function getManager(): ?string
    {
        return $this->manager;
    }

    public function setManager(string $manager): static
    {
        $this->manager = $manager;

        return $this;
    }

    public function getStep(): ?PipelineStep
    {
        return $this->step;
    }

    public function setStep(?PipelineStep $step): static
    {
        $this->step = $step;

        return $this;
    }

    /**
     * @return Collection<int, Tag>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(Tag $tag): static
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
            $tag->addDeal($this);
        }

        return $this;
    }

    public function removeTag(Tag $tag): static
    {
        if ($this->tags->removeElement($tag)) {
            $tag->removeDeal($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Contact>
     */
    public function getParticipants(): Collection
    {
        return $this->participants;
    }

    public function addParticipant(Contact $participant): static
    {
        if (!$this->participants->contains($participant)) {
            $this->participants->add($participant);
        }

        return $this;
    }

    public function removeParticipant(Contact $participant): static
    {
        $this->participants->removeElement($participant);

        return $this;
    }

    /**
     * @return Collection<int, Activity>
     */
    public function getActivities(): Collection
    {
        return $this->activities;
    }

    public function addActivity(Activity $activity): static
    {
        if (!$this->activities->contains($activity)) {
            $this->activities->add($activity);
            $activity->setDeal($this);
        }

        return $this;
    }

    public function removeActivity(Activity $activity): static
    {
        if ($this->activities->removeElement($activity)) {
            // set the owning side to null (unless already changed)
            if ($activity->getDeal() === $this) {
                $activity->setDeal(null);
            }
        }

        return $this;
    }

    public function getProducts(): ?array
    {
        return $this->products;
    }

    public function setProducts(?array $products): static
    {
        $this->products = $products;

        return $this;
    }

    /**
     * @return Collection<int, Asset>
     */
    public function getAssets(): Collection
    {
        return $this->assets;
    }

    public function addAsset(Asset $asset): static
    {
        if (!$this->assets->contains($asset)) {
            $this->assets->add($asset);
            $asset->setDeal($this);
        }

        return $this;
    }

    public function removeAsset(Asset $asset): static
    {
        if ($this->assets->removeElement($asset)) {
            // set the owning side to null (unless already changed)
            if ($asset->getDeal() === $this) {
                $asset->setDeal(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Note>
     */
    public function getNotes(): Collection
    {
        return $this->notes;
    }

    public function addNote(Note $note): static
    {
        if (!$this->notes->contains($note)) {
            $this->notes->add($note);
            $note->setDeal($this);
        }

        return $this;
    }

    public function removeNote(Note $note): static
    {
        if ($this->notes->removeElement($note)) {
            // set the owning side to null (unless already changed)
            if ($note->getDeal() === $this) {
                $note->setDeal(null);
            }
        }

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): static
    {
        if ($status !== null && !in_array($status, [self::STATUS_WIN, self::STATUS_LOST], true)) {
            throw new \InvalidArgumentException('Le statut doit être soit "win" soit "lost".');
        }
        $this->status = $status;
        return $this;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): static
    {
        $this->company = $company;

        return $this;
    }
}
