<?php

namespace App\Entity;

use App\Repository\ActivityRepository;
use App\Traits\UserObjectTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ActivityRepository::class)]
class Activity
{

    use UserObjectTrait;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["activity:read", "company:info", "contact:info", "pipeline:info" , "deal:info"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["activity:read", "company:info", "contact:info","pipeline:info" , "deal:info"])]
    private ?string $type = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(["activity:read", "company:info", "contact:info" , "pipeline:info" , "deal:info"])]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(["activity:read", "company:info", "contact:info" , "pipeline:info" , "deal:info"])]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(length: 255)]
    #[Groups(["activity:read", "company:info", "contact:info" , "deal:info"])]
    private ?string $location = null;

    #[ORM\Column]
    #[Groups(["activity:read", "company:info", "contact:info" , "deal:info"])]
    private ?bool $performed = null;

    #[ORM\Column]
    #[Groups(["activity:read", "company:info", "contact:info" , "deal:info"])]
    private ?bool $notify = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(["activity:read", "company:info", "contact:info" , "deal:info"])]
    private ?\DateTimeInterface $notifyDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["activity:read", "company:info", "contact:info" , "deal:info"])]
    private ?string $description = null;

    #[ORM\ManyToOne(inversedBy: 'activities')]
    private ?Deal $deal = null;

    /**
     * @var Collection<int, Note>
     */
    #[ORM\OneToMany(targetEntity: Note::class, mappedBy: 'activity')]
    #[Groups(["activity:read", "company:info", "contact:info" , "deal:info"])]
    private Collection $notes;

    #[ORM\Column(type: Types::JSON)]
    #[Groups(["activity:read", "company:info", "contact:info" , "deal:info"])]
    private array $managers = [];

    #[ORM\ManyToOne(inversedBy: 'activities')]
    private ?Contact $contact = null;

    #[ORM\ManyToOne(inversedBy: 'activities')]
    private ?Company $company = null;

    #[ORM\Column(length: 255)]
    #[Groups(["activity:read", "company:info", "contact:info", "pipeline:info", "deal:info"])]
    private ?string $name = null;

    public function __construct()
    {
        $this->notes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(string $location): static
    {
        $this->location = $location;

        return $this;
    }

    public function isPerformed(): ?bool
    {
        return $this->performed;
    }

    public function setPerformed(bool $performed): static
    {
        $this->performed = $performed;

        return $this;
    }

    public function isNotify(): ?bool
    {
        return $this->notify;
    }

    public function setNotify(bool $notify): static
    {
        $this->notify = $notify;

        return $this;
    }

    public function getNotifyDate(): ?\DateTimeInterface
    {
        return $this->notifyDate;
    }

    public function setNotifyDate(?\DateTimeInterface $notifyDate): static
    {
        $this->notifyDate = $notifyDate;

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

    public function getDeal(): ?Deal
    {
        return $this->deal;
    }

    public function setDeal(?Deal $deal): static
    {
        $this->deal = $deal;

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
            $note->setActivity($this);
        }

        return $this;
    }

    public function removeNote(Note $note): static
    {
        if ($this->notes->removeElement($note)) {
            // set the owning side to null (unless already changed)
            if ($note->getActivity() === $this) {
                $note->setActivity(null);
            }
        }

        return $this;
    }

    public function getManagers(): array
    {
        return $this->managers;
    }

    public function setManagers(array $managers): static
    {
        $this->managers = $managers;

        return $this;
    }

    public function addManager(string $manager): static
    {
        if (!in_array($manager, $this->managers, true)) {
            $this->managers[] = $manager;
        }

        return $this;
    }

    public function removeManager(string $manager): static
    {
        $key = array_search($manager, $this->managers, true);
        if ($key !== false) {
            unset($this->managers[$key]);
            $this->managers = array_values($this->managers); // Réindexer le tableau
        }

        return $this;
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

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): static
    {
        $this->company = $company;

        return $this;
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
}
