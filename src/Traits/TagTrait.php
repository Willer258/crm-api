<?php


namespace App\Traits;


use App\Entity\Tag;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

trait TagTrait
{


    #[ORM\ManyToMany(targetEntity: Tag::class, cascade: ['persist'])]
    #[Groups(['infos'])]
    private $tags;

    public function hasTag($string)
    {
        if ($string instanceof Tag) {
            if (!empty($string->getCode())) {
                $string = $string->getCode();
            } elseif (!empty($string->getLabel())) {
                $string = $string->getLabel();
            }
        }
        /** @var Tag $tag */
        foreach ($this->getTags() as $tag) {
            if (strtoupper($tag->getLabel()) === strtoupper($string) || strtoupper($tag->getCode()) === strtoupper($string)) {
                return true;
            }
        }
        return false;
    }

    public function getTags()
    {
        return $this->tags ?? [];
    }

    public function addTag(Tag $tag): self
    {
        $tag->setLabel(strtoupper($tag->getLabel()));
        $contain = false;
        if (!$this->tags) {
            $this->tags = [];
        }
        /** @var Tag $item */
        foreach ($this->getTags() as $item) {
            if (strtoupper($tag->getLabel()) === strtoupper($item->getLabel())) {
                $contain = true;
            }
        }
        if (!$contain) {
            $this->tags[] = $tag;
        }

        return $this;
    }

    public function removeTag(Tag $tag): self
    {
        $this->tags->removeElement($tag);

        return $this;
    }
}
