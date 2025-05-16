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
    #[Groups(['deal:edit', 'deal:list', 'deal:info'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'deals')]
    #[Groups(['deal:edit', 'deal:list', 'deal:info'])]
    private ?Contact $contact = null;

    #[ORM\Column(length: 255)]
    #[Groups(['deal:edit', 'deal:list', 'deal:info'])]
    private ?string $objet = null;

    #[ORM\Column(length: 255)]
    #[Groups(['deal:edit', 'deal:list', 'deal:info'])]
    private ?string $manager = null;

    #[ORM\ManyToOne(inversedBy: 'deals')]
    #[Groups(['deal:edit', 'deal:list', 'deal:info'])]
    private ?PipelineStep $step = null;

    /**
     * @var Collection<int, Tag>
     */
    #[ORM\ManyToMany(targetEntity: Tag::class, mappedBy: 'deals')]
    #[Groups(['deal:edit', 'deal:list', 'deal:info'])]
    private Collection $tags;

    /**
     * @var Collection<int, Contact>
     */
    #[ORM\ManyToMany(targetEntity: Contact::class, inversedBy: 'dealsAsParticipant')]
    #[Groups(['deal:edit', 'deal:list', 'deal:info'])]
    private Collection $participants;

    /**
     * @var Collection<int, Activity>
     */
    #[ORM\OneToMany(targetEntity: Activity::class, mappedBy: 'deal')]
    #[Groups(['deal:edit', 'deal:list', 'deal:info'])]
    private Collection $activities;

    #[ORM\Column(nullable: true)]
    private ?array $products = null;

    /**
     * @var Collection<int, File>
     */
    #[ORM\OneToMany(targetEntity: File::class, mappedBy: 'deal')]
    #[Groups(['deal:edit', 'deal:list', 'deal:info'])]
    private Collection $files;

    /**
     * @var Collection<int, Note>
     */
    #[ORM\OneToMany(targetEntity: Note::class, mappedBy: 'deal')]
    #[Groups(['deal:edit', 'deal:list', 'deal:info'])]
    private Collection $notes;

    

    
    #[ORM\Column(length: 255, nullable: true)]
    public const STATUS_WIN = 'win';
    public const STATUS_LOST = 'lost';


    #[Groups(['deal:edit', 'deal:list', 'deal:info'])]
    private ?string $status = null;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
        $this->participants = new ArrayCollection();
        $this->activities = new ArrayCollection();
        $this->files = new ArrayCollection();
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

    public function getObjet(): ?string
    {
        return $this->objet;
    }

    public function setObjet(string $objet): static
    {
        $this->objet = $objet;

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
     * @return Collection<int, File>
     */
    public function getFiles(): Collection
    {
        return $this->files;
    }

    public function addFile(File $file): static
    {
        if (!$this->files->contains($file)) {
            $this->files->add($file);
            $file->setDeal($this);
        }

        return $this;
    }

    public function removeFile(File $file): static
    {
        if ($this->files->removeElement($file)) {
            // set the owning side to null (unless already changed)
            if ($file->getDeal() === $this) {
                $file->setDeal(null);
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
}
