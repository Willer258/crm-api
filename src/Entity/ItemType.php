<?php

namespace App\Entity;

use App\Repository\ItemTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ItemTypeRepository::class)]
class ItemType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['itemType:list' , 'itemType:edit', 'itemType:show'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['itemType:list' , 'itemType:edit', 'property_model:list' , 'property_model:edit', 'itemType:show'])]
    private ?string $code = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['itemType:list' , 'itemType:edit', 'itemType:show'])]
    private ?string $description = null;

    /**
     * @var Collection<int, Company>
     */
    #[ORM\OneToMany(targetEntity: Company::class, mappedBy: 'itemType')]
    private Collection $companies;

    /**
     * @var Collection<int, Contact>
     */
    #[ORM\OneToMany(targetEntity: Contact::class, mappedBy: 'itemType')]
    private Collection $contacts;

    /**
     * @var Collection<int, PropertyModel>
     */
    #[ORM\OneToMany(targetEntity: PropertyModel::class, mappedBy: 'itemType')]
    #[Groups(['itemType:list' , 'itemType:edit', 'itemType:show'])]
    private Collection $propertyModels;

    public function __construct()
    {
        $this->companies = new ArrayCollection();
        $this->contacts = new ArrayCollection();
        $this->propertyModels = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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
            $company->setItemType($this);
        }

        return $this;
    }

    public function removeCompany(Company $company): static
    {
        if ($this->companies->removeElement($company)) {
            // set the owning side to null (unless already changed)
            if ($company->getItemType() === $this) {
                $company->setItemType(null);
            }
        }

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
            $contact->setItemType($this);
        }

        return $this;
    }

    public function removeContact(Contact $contact): static
    {
        if ($this->contacts->removeElement($contact)) {
            // set the owning side to null (unless already changed)
            if ($contact->getItemType() === $this) {
                $contact->setItemType(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, PropertyModel>
     */
    public function getPropertyModels(): Collection
    {
        return $this->propertyModels;
    }

    public function addPropertyModel(PropertyModel $propertyModel): static
    {
        if (!$this->propertyModels->contains($propertyModel)) {
            $this->propertyModels->add($propertyModel);
            $propertyModel->setItemType($this);
        }

        return $this;
    }

    public function removePropertyModel(PropertyModel $propertyModel): static
    {
        if ($this->propertyModels->removeElement($propertyModel)) {
            // set the owning side to null (unless already changed)
            if ($propertyModel->getItemType() === $this) {
                $propertyModel->setItemType(null);
            }
        }

        return $this;
    }
}
