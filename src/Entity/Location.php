<?php

namespace App\Entity;

use App\Repository\LocationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: LocationRepository::class)]
class Location
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['manager'])]
    private $country;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['manager'])]
    private $city;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['manager'])]
    private $region;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['manager'])]
    private $company;

    #[ORM\Column(type: 'boolean', nullable: true)]
    #[Groups(['manager'])]
    private $vpn;

    #[ORM\Column(type: 'boolean', nullable: true)]
    #[Groups(['manager'])]
    private $proxy;

    #[ORM\Column(type: 'boolean', nullable: true)]
    #[Groups(['manager'])]
    private $tor;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['manager'])]
    private $ip;

//    #[ORM\ManyToOne(targetEntity: Device::class, inversedBy: 'locations')]
//    #[ORM\JoinColumn(nullable: false)]
//    private $device;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $countryCode;

//    #[ORM\OneToMany(targetEntity: ResponseGroup::class, mappedBy: 'location')]
//    private $responseGroups;

    public function __construct()
    {
        $this->responseGroups = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): self
    {
        $this->country = $country;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function setRegion(?string $region): self
    {
        $this->region = $region;

        return $this;
    }

    public function getCompany(): ?string
    {
        return $this->company;
    }

    public function setCompany(?string $company): self
    {
        $this->company = $company;

        return $this;
    }

    public function getVpn(): ?bool
    {
        return $this->vpn;
    }

    public function setVpn(?bool $vpn): self
    {
        $this->vpn = $vpn;

        return $this;
    }

    public function getProxy(): ?bool
    {
        return $this->proxy;
    }

    public function setProxy(?bool $proxy): self
    {
        $this->proxy = $proxy;

        return $this;
    }

    public function getTor(): ?bool
    {
        return $this->tor;
    }

    public function setTor(?bool $tor): self
    {
        $this->tor = $tor;

        return $this;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(string $ip): self
    {
        $this->ip = $ip;

        return $this;
    }

    public function getDevice(): ?Device
    {
        return $this->device;
    }

    public function setDevice(?Device $device): self
    {
        $this->device = $device;

        return $this;
    }

    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }

    public function setCountryCode(?string $countryCode): self
    {
        $this->countryCode = $countryCode;

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
            $responseGroup->setLocation($this);
        }

        return $this;
    }

    public function removeResponseGroup(ResponseGroup $responseGroup): self
    {
        if ($this->responseGroups->removeElement($responseGroup)) {
            // set the owning side to null (unless already changed)
            if ($responseGroup->getLocation() === $this) {
                $responseGroup->setLocation(null);
            }
        }

        return $this;
    }
}
