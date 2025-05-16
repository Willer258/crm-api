<?php

namespace App\Entity;

use App\Repository\TagRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: TagRepository::class)]
class Tag
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['tag:list', 'tag:edit'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['tag:list', 'tag:edit'])]
    private ?string $label = null;

    #[ORM\Column(length: 255)]
    #[Groups(['tag:list', 'tag:edit'])]
    private ?string $code = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['tag:list', 'tag:edit'])]
    private ?string $description = null;

    /**
     * @var Collection<int, Deal>
     */
    #[ORM\ManyToMany(targetEntity: Deal::class, inversedBy: 'tags')]
    #[Groups(['tag:edit'])]
    private Collection $deals;

    #[ORM\Column(length: 255)]
    #[Groups(['tag:list', 'tag:edit'])]
    private ?string $color = null;

    /**
     * @var Collection<int, Contact>
     */
    #[ORM\ManyToMany(targetEntity: Contact::class, inversedBy: 'tags')]
    #[Groups(['tag:edit'])]
    private Collection $contacts;

    /**
     * @var Collection<int, Company>
     */
    #[ORM\ManyToMany(targetEntity: Company::class, inversedBy: 'tags')]
    #[Groups(['tag:edit'])]
    private Collection $companies;

    public function __construct()
    {
        $this->deals = new ArrayCollection();
        $this->contacts = new ArrayCollection();
        $this->companies = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

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
        }

        return $this;
    }

    public function removeDeal(Deal $deal): static
    {
        $this->deals->removeElement($deal);

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

    /**
     * @return Collection<int, Contact>
     */
    public function getContacts(): Collection
    {
        return $this->contacts;
    }

    public function addContact(Contact $contact): static
    {
        if (!$this->contacts->contains($contact)) {
            $this->contacts->add($contact);
        }

        return $this;
    }

    public function removeContact(Contact $contact): static
    {
        $this->contacts->removeElement($contact);

        return $this;
    }

    /**
     * @return Collection<int, Company>
     */
    public function getCompanies(): Collection
    {
        return $this->companies;
    }

    public function addCompany(Company $company): static
    {
        if (!$this->companies->contains($company)) {
            $this->companies->add($company);
        }

        return $this;
    }

    public function removeCompany(Company $company): static
    {
        $this->companies->removeElement($company);

        return $this;
    }

}
