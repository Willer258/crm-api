<?php

namespace App\Entity;

use App\Repository\ContactRepository;
use App\Traits\UserObjectTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ContactRepository::class)]

class Contact
{
    use UserObjectTrait;

    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['contact:edit', 'contact:list','deal:info' , 'deal:edit'])]
    private ?int $id = null;

    /**
     * @var Collection<int, Property>
     */
    #[ORM\OneToMany(targetEntity: Property::class, mappedBy: 'contact')]
    #[Groups(['contact:edit' , 'contact:list','deal:info' , 'deal:edit'])]
    private Collection $properties;

    /**
     * @var Collection<int, Deal>
     */
    #[ORM\OneToMany(targetEntity: Deal::class, mappedBy: 'contact')]
    #[Groups(['contact:edit' , 'contact:list'])]
    private Collection $deals;

    #[ORM\Column(length: 255)]
    #[Groups(['contact:edit','contact:list' , 'deal:info' , 'deal:edit'])]
    private ?string $source = null;

    #[ORM\ManyToOne(inversedBy: 'contacts')]
    #[Groups(['contact:edit','contact:list' , 'deal:info' , 'deal:edit'])]
    private ?Company $company = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['contact:edit','contact:list' , 'deal:info' , 'deal:edit'])]
    private ?string $manager = null;

    #[ORM\ManyToOne(inversedBy: 'contacts')]
    #[Groups(['contact:edit','contact:list' , 'deal:info'])]
    private ?ItemType $itemType = null;

    /**
     * @var Collection<int, Deal>
     */
    #[ORM\ManyToMany(targetEntity: Deal::class, mappedBy: 'participants')]
    #[Groups(['contact:edit' , 'contact:list','deal:info'])]
    private Collection $dealsAsParticipant;

    /**
     * @var Collection<int, File>
     */
    #[ORM\OneToMany(targetEntity: File::class, mappedBy: 'contact')]
    #[Groups(['contact:edit' , 'contact:list','deal:info'])]
    private Collection $files;

    /**
     * @var Collection<int, Mail>
     */
    #[ORM\OneToMany(targetEntity: Mail::class, mappedBy: 'contact')]
    #[Groups(['contact:edit' , 'contact:list','deal:info'])]
    private Collection $mails;

    /**
     * @var Collection<int, Note>
     */
    #[ORM\OneToMany(targetEntity: Note::class, mappedBy: 'contact')]
    #[Groups(['contact:edit' , 'contact:list','deal:info'])]
        private Collection $notes;

    /**
     * @var Collection<int, Tag>
     */
    #[ORM\ManyToMany(targetEntity: Tag::class, mappedBy: 'contacts')]
    #[Groups(['contact:edit' , 'contact:list','deal:info'])]
    private Collection $tags;


    public function __construct()
    {
        $this->properties = new ArrayCollection();
        $this->deals = new ArrayCollection();
        $this->dealsAsParticipant = new ArrayCollection();
        $this->files = new ArrayCollection();
        $this->mails = new ArrayCollection();
        $this->notes = new ArrayCollection();
        $this->tags = new ArrayCollection();
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
            $file->setContact($this);
        }

        return $this;
    }

    public function removeFile(File $file): static
    {
        if ($this->files->removeElement($file)) {
            // set the owning side to null (unless already changed)
            if ($file->getContact() === $this) {
                $file->setContact(null);
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

}
