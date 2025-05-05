<?php

namespace App\Entity;

use App\Repository\PropertyModelRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: PropertyModelRepository::class)]
class PropertyModel
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['property_model:list', 'property_model:edit' , 'contact:edit', 'contact:list'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['property_model:list', 'property_model:edit' , 'contact:edit' ,  'contact:list'])]
    private ?string $label = null;

    #[ORM\Column]
    #[Groups(['property_model:list',' property_model:edit'])]
    private ?bool $identifier = null;

    #[ORM\Column(length: 255)]
    #[Groups(['property_model:list', 'property_model:edit'])]
    private ?string $type = null;

    /**
     * @var Collection<int, Property>
     */
    #[ORM\OneToMany(targetEntity: Property::class, mappedBy: 'propertyModel')]
    private Collection $properties;

    public function __construct()
    {
        $this->properties = new ArrayCollection();
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

    public function isIdentifier(): ?bool
    {
        return $this->identifier;
    }

    public function setIdentifier(bool $identifier): static
    {
        $this->identifier = $identifier;

        return $this;
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
            $property->setPropertyModel($this);
        }

        return $this;
    }

    public function removeProperty(Property $property): static
    {
        if ($this->properties->removeElement($property)) {
            // set the owning side to null (unless already changed)
            if ($property->getPropertyModel() === $this) {
                $property->setPropertyModel(null);
            }
        }

        return $this;
    }
}
