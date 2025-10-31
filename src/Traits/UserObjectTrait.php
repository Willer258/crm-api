<?php

namespace App\Traits;

use App\Entity\User;
use Ramsey\Uuid\UuidInterface;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\ORM\Mapping as ORM;

trait  UserObjectTrait
{
    #[ORM\Column(type: 'uuid', length: 255, nullable: true)]
    #[Groups(['infos', 'survey', 'form','uuid'])]
    private $uuid;


    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups(['userManagement', 'infos'])]
    private $createdAt;


    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups(['userManagement', 'infos'])]
    private $updatedAt;

    #[ORM\Column(type: 'string', nullable: true)]
    #[Groups(['userManagement','prospect'])]
    private $createBy;


    #[ORM\Column(type: 'string', nullable: true)]
    #[Groups(['userManagement'])]
    private $updateBy;



    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups(['userManagement', 'infos', 'with_deleted_info'])]
    private $removeAt;

    #[ORM\Column(type: 'string', nullable: true)]
    #[Groups(['userManagement', 'with_deleted_info'])]
    private $removeBy;

    #[ORM\Column(type: 'string', length: 45, nullable: true)]
    private $createdFromIp;

    #[ORM\Column(type: 'string', length: 45, nullable: true)]
    private $updatedFromIp;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups(['userManagement', 'infos', 'with_deleted_info'])]
    private $restoredAt;

    #[ORM\Column(type: 'string', nullable: true)]
    #[Groups(['userManagement', 'with_deleted_info'])]
    private $restoredBy;


    public function setUuid($uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getUuid()
    {
        return $this->uuid ? (string)$this->uuid : null;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getRemoveAt(): ?\DateTimeInterface
    {
        return $this->removeAt;
    }

    public function setRemoveAt(?\DateTimeInterface $removeAt): self
    {
        $this->removeAt = $removeAt;

        return $this;
    }

    public function getCreateBy()
    {
        return $this->createBy;
    }

    public function setCreateBy(?User $createBy): self
    {
        $this->createBy = $createBy !== null ? $createBy->getUserIdentifier() : null;
        return $this;
    }

    public function isUserAllowed(User $user)
    {
        return $user->getId() === $this->createBy->getId();
    }

    public function getUpdateBy()
    {
        return $this->updateBy;
    }

    public function setUpdateBy(?User $updateBy): self
    {
        $this->updateBy = $updateBy !== null ? $updateBy->getUserIdentifier() : null;

        return $this;
    }


    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }


    public function getRemoveBy(): ?string
    {
        return $this->removeBy;
    }

    public function setRemoveBy(?User $removeBy): self
    {
        $this->removeBy = $removeBy !== null ? $removeBy->getUserIdentifier() : null;

        return $this;
    }

    /**
     * @return string
     */
    public function getCreatedFromIp(): ?string
    {
        return $this->createdFromIp;
    }

    /**
     * @param string $createdFromIp
     */
    public function setCreatedFromIp(string $createdFromIp): void
    {
        $this->createdFromIp = $createdFromIp;
    }

    /**
     * @return string
     */
    public function getUpdatedFromIp(): ?string
    {
        return $this->updatedFromIp;
    }

    /**
     * @param string $updatedFromIp
     */
    public function setUpdatedFromIp(string $updatedFromIp): void
    {
        $this->updatedFromIp = $updatedFromIp;
    }

    public function getRestoredAt(): ?\DateTimeInterface
    {
        return $this->restoredAt;
    }

    public function setRestoredAt(?\DateTimeInterface $restoredAt): self
    {
        $this->restoredAt = $restoredAt;
        return $this;
    }

    public function getRestoredBy(): ?string
    {
        return $this->restoredBy;
    }

    public function setRestoredBy(?string $restoredBy): self
    {
        $this->restoredBy = $restoredBy;
        return $this;
    }

    #[Groups(['contact:list', 'contact:info', 'company:list', 'company:info', 'deal:list', 'deal:info', 'activity:read', 'pipeline:list', 'with_deleted_info'])]
    public function isDeleted(): bool
    {
        return $this->removeAt instanceof \DateTimeInterface;
    }
}
