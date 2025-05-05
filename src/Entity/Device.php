<?php

namespace App\Entity;

use App\Repository\DeviceRepository;
use DeviceDetector\Parser\Device\AbstractDeviceParser;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: DeviceRepository::class)]
class Device
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['manager'])]
    private $mac;


    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['manager'])]
    private $brand;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['manager'])]
    private $type;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['manager'])]
    private $os;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['manager'])]
    private $browser;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['manager'])]
    private $model;

//    #[ORM\OneToMany(targetEntity: ResponseGroup::class, mappedBy: 'device')]
//    private $responseGroups;
//
//    #[ORM\ManyToMany(targetEntity: Prospect::class, mappedBy: 'devices')]
//    private $prospects;
//
//    #[ORM\OneToMany(targetEntity: Location::class, mappedBy: 'device', orphanRemoval: true)]
//    private $locations;


    public function __construct()
    {
        $this->responseGroups = new ArrayCollection();
        $this->prospects = new ArrayCollection();
        $this->locations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMac(): ?string
    {
        return $this->mac;
    }

    public function setMac(?string $mac): self
    {
        $this->mac = $mac;

        return $this;
    }


    public function getBrand(): ?string
    {
        return $this->brand;
    }

    public function setBrand(?string $brand): self
    {
        $this->brand = $brand;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getTypeCode()
    {
        foreach (AbstractDeviceParser::getAvailableDeviceTypes() as $key => $code) {
            if ($key === $this->type) {
                return $code;
            }
        }
        return null;
    }

    public function isMobile()
    {
        return in_array($this->type, ['smartphone', 'tablet', 'feature phone', 'phablet']);
    }

    public function isPersonal()
    {
        return in_array($this->type, ['smartphone', 'tablet', 'feature phone', 'console', 'car browser']);
    }

    public function getOs(): ?string
    {
        return $this->os;
    }

    public function getPrincipalUser(): ?Prospect
    {
        /** @var Prospect $prospect */
        foreach ($this->prospects as $prospect) {
            if (!empty($prospect->getEmail())) {
                return $prospect;
            }
        }
        if (!$this->getProspects()->isEmpty()) {
            return $this->getProspects()->first();
        }
    }

    public function setOs(?string $os): self
    {
        $this->os = $os;

        return $this;
    }

    public function getBrowser(): ?string
    {
        return $this->browser;
    }

    public function setBrowser(?string $browser): self
    {
        $this->browser = $browser;

        return $this;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function setModel(?string $model): self
    {
        $this->model = $model;

        return $this;
    }

    /**
     * @return Collection|ResponseGroup[]
     */
    public function getResponseGroups(): Collection
    {
        return $this->responseGroups;
    }

    public function addResponseGroup(ResponseGroup $responseGroup): self
    {
        if (!$this->responseGroups->contains($responseGroup)) {
            $this->responseGroups[] = $responseGroup;
//            $responseGroup->setDevice($this);
            if (!$this->prospects->contains($responseGroup->getProspect()) && !empty($responseGroup->getProspect()->getEmail())) {
                $responseGroup->getProspect()->addDevice($this);
                $this->addProspect($responseGroup->getProspect());
            }
        }

        return $this;
    }

    public function removeResponseGroup(ResponseGroup $responseGroup): self
    {
        if ($this->responseGroups->removeElement($responseGroup)) {
            // set the owning side to null (unless already changed)
//            if ($responseGroup->getDevice() === $this) {
//                $responseGroup->setDevice(null);
//            }
        }

        return $this;
    }

    /**
     * @return Collection|Prospect[]
     */
    public function getProspects(): Collection
    {
        return $this->prospects;
    }

    public function addProspect(Prospect $prospect): self
    {
        if (!$this->prospects->contains($prospect)) {
            $this->prospects[] = $prospect;
            $prospect->addDevice($this);
        }

        return $this;
    }

    public function removeProspect(Prospect $prospect): self
    {
        if ($this->prospects->removeElement($prospect)) {
            $prospect->removeDevice($this);
        }

        return $this;
    }

    /**
     * @return Collection|Location[]
     */
    public function getLocations(): Collection
    {
        return $this->locations;
    }

    public function addLocation(Location $location): self
    {
        if (!$this->locations->contains($location)) {
            $this->locations[] = $location;
            $location->setDevice($this);
        }

        return $this;
    }

    public function removeLocation(Location $location): self
    {
        if ($this->locations->removeElement($location)) {
            // set the owning side to null (unless already changed)
            if ($location->getDevice() === $this) {
                $location->setDevice(null);
            }
        }

        return $this;
    }


}
