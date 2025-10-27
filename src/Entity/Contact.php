<?php

namespace App\Entity;

use App\Repository\ContactRepository;
use App\Traits\UserObjectTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;

#[ORM\Entity(repositoryClass: ContactRepository::class)]

class Contact
{
    use UserObjectTrait;

    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['contact:edit', 'contact:list', 'contact:info', 'company:info', 'deal:info'])]
    private ?int $id = null;

    /**
     * @var Collection<int, Property>
     */
    #[ORM\OneToMany(targetEntity: Property::class, mappedBy: 'contact', cascade: ['persist'])]
    #[Groups(['contact:edit' , 'contact:list','deal:info', 'contact:info', 'company:info' ,'pipeline:info'])]
    private Collection $properties;

    /**
     * @var Collection<int, Deal>
     */
    #[ORM\OneToMany(targetEntity: Deal::class, mappedBy: 'contact')]
    #[Groups(['contact:info'])]
    private Collection $deals;

    #[ORM\Column(length: 255)]
    #[Groups(['contact:edit','contact:list', 'contact:info'])]
    private ?string $source = null;

    #[ORM\ManyToOne(inversedBy: 'contacts')]
    #[Groups(['contact:edit','contact:list', 'contact:info'])]
    private ?Company $company = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['contact:edit','contact:list', 'contact:info', 'deal:info'])]
    private ?string $manager = null;

    #[ORM\ManyToOne(inversedBy: 'contacts')]
    #[Groups(['contact:edit','contact:list', 'contact:info'])]
    private ?ItemType $itemType = null;

    /**
     * @var Collection<int, Deal>
     */
    #[ORM\ManyToMany(targetEntity: Deal::class, mappedBy: 'participants')]
    #[Groups(['contact:edit' , 'contact:list'])]
    #[MaxDepth(1)]
    private Collection $dealsAsParticipant;

    /**
     * @var Collection<int, Asset>
     */
    #[ORM\OneToMany(targetEntity: Asset::class, mappedBy: 'contact')]
    #[Groups(['contact:edit' , 'contact:list','contact:info' , 'deal:info'])]
    #[MaxDepth(1)]
    private Collection $assets;

    /**
     * @var Collection<int, PhoneNumber>
     */
    #[ORM\OneToMany(targetEntity: PhoneNumber::class, mappedBy: 'contact', cascade: ['persist', 'remove'])]
    #[Groups(['contact:edit', 'contact:list', 'contact:info' , 'deal:info'])]
    private Collection $phones;

    /**
     * @var Collection<int, Mail>
     */
    #[ORM\OneToMany(targetEntity: Mail::class, mappedBy: 'contact')]
    #[Groups(['contact:edit' , 'contact:list','contact:info' , 'deal:info'])]
    private Collection $mails;

    /**
     * @var Collection<int, Note>
     */
    #[ORM\OneToMany(targetEntity: Note::class, mappedBy: 'contact')]
    #[Groups(['contact:edit' , 'contact:list','contact:info'])]
        private Collection $notes;

    /**
     * @var Collection<int, Tag>
     */
    #[ORM\ManyToMany(targetEntity: Tag::class, mappedBy: 'contacts')]
    #[Groups(['contact:edit' , 'contact:list', 'contact:info'])]
    #[MaxDepth(1)]
    private Collection $tags;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['contact:edit' , 'contact:list','contact:info' , 'pipeline:info'])]
    private ?string $photo = null;

    /**
     * @var Collection<int, Activity>
     */
    #[ORM\OneToMany(targetEntity: Activity::class, mappedBy: 'contact')]
    #[Groups(['contact:edit' , 'contact:list','contact:info'])]
    #[MaxDepth(1)]
    private Collection $activities;


    public function __construct()
    {
        $this->properties = new ArrayCollection();
        $this->deals = new ArrayCollection();
        $this->dealsAsParticipant = new ArrayCollection();
        $this->assets = new ArrayCollection();
        $this->phones = new ArrayCollection();
        $this->mails = new ArrayCollection();
        $this->notes = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->activities = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, Property>
     */
    public function getProperties(): Collection
    {
        return $this->properties;
    }

    public function addProperty(Property $property): static
    {
        if (!$this->properties->contains($property)) {
            $this->properties->add($property);
            $property->setContact($this);
        }

        return $this;
    }

    public function removeProperty(Property $property): static
    {
        if ($this->properties->removeElement($property)) {
            // set the owning side to null (unless already changed)
            if ($property->getContact() === $this) {
                $property->setContact(null);
            }
        }

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
            $deal->setContact($this);
        }

        return $this;
    }

    public function removeDeal(Deal $deal): static
    {
        if ($this->deals->removeElement($deal)) {
            // set the owning side to null (unless already changed)
            if ($deal->getContact() === $this) {
                $deal->setContact(null);
            }
        }

        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(string $source): static
    {
        $this->source = $source;

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

    public function getManager(): ?string
    {
        return $this->manager;
    }

    public function setManager(?string $manager): static
    {
        $this->manager = $manager;

        return $this;
    }

    public function getItemType(): ?ItemType
    {
        return $this->itemType;
    }

    public function setItemType(?ItemType $itemType): static
    {
        $this->itemType = $itemType;

        return $this;
    }

    /**
     * @return Collection<int, Deal>
     */
    public function getDealsAsParticipant(): Collection
    {
        return $this->dealsAsParticipant;
    }

    public function addDealsAsParticipant(Deal $dealsAsParticipant): static
    {
        if (!$this->dealsAsParticipant->contains($dealsAsParticipant)) {
            $this->dealsAsParticipant->add($dealsAsParticipant);
            $dealsAsParticipant->addParticipant($this);
        }

        return $this;
    }

    public function removeDealsAsParticipant(Deal $dealsAsParticipant): static
    {
        if ($this->dealsAsParticipant->removeElement($dealsAsParticipant)) {
            $dealsAsParticipant->removeParticipant($this);
        }

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
            $asset->setContact($this);
        }

        return $this;
    }

    public function removeAsset(Asset $asset): static
    {
        if ($this->assets->removeElement($asset)) {
            // set the owning side to null (unless already changed)
            if ($asset->getContact() === $this) {
                $asset->setContact(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, PhoneNumber>
     */
    public function getPhones(): Collection
    {
        return $this->phones;
    }

    public function addPhone(PhoneNumber $phone): static
    {
        if (!$this->phones->contains($phone)) {
            $this->phones->add($phone);
            $phone->setContact($this);
        }
        return $this;
    }

    public function removePhone(PhoneNumber $phone): static
    {
        if ($this->phones->removeElement($phone)) {
            if ($phone->getContact() === $this) {
                $phone->setContact(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Mail>
     */
    public function getMails(): Collection
    {
        return $this->mails;
    }

    public function addMail(Mail $mail): static
    {
        if (!$this->mails->contains($mail)) {
            $this->mails->add($mail);
            $mail->setContact($this);
        }

        return $this;
    }

    public function removeMail(Mail $mail): static
    {
        if ($this->mails->removeElement($mail)) {
            // set the owning side to null (unless already changed)
            if ($mail->getContact() === $this) {
                $mail->setContact(null);
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
            $note->setContact($this);
        }

        return $this;
    }

    public function removeNote(Note $note): static
    {
        if ($this->notes->removeElement($note)) {
            // set the owning side to null (unless already changed)
            if ($note->getContact() === $this) {
                $note->setContact(null);
            }
        }

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
            $tag->addContact($this);
        }

        return $this;
    }

    public function removeTag(Tag $tag): static
    {
        if ($this->tags->removeElement($tag)) {
            $tag->removeContact($this);
        }

        return $this;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): static
    {
        $this->photo = $photo;

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
            $activity->setContact($this);
        }

        return $this;
    }

    public function removeActivity(Activity $activity): static
    {
        if ($this->activities->removeElement($activity)) {
            // set the owning side to null (unless already changed)
            if ($activity->getContact() === $this) {
                $activity->setContact(null);
            }
        }

        return $this;
    }

}
