<?php

namespace App\Managers;

use App\Entity\Tag;
use App\Entity\Deal;
use App\Entity\Company;
use App\Entity\Contact;
use Doctrine\ORM\EntityManagerInterface;

class TagManager
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Crée un tag à partir d'un tableau de données
     */
    public function createFromArray(array $data): Tag
    {
        $tag = new Tag();
        $this->hydrate($tag, $data);
        $this->em->persist($tag);
        $this->em->flush();
        return $tag;
    }

    /**
     * Assigne un tag à une entité (Deal, Company, Contact)
     */
    public function assignTag(Tag $tag, object $entity): void
    {
        if ($entity instanceof Deal) {
            $entity->addTag($tag);
        } elseif ($entity instanceof Company) {
            $entity->addTag($tag);
        } elseif ($entity instanceof Contact) {
            $entity->addTag($tag);
        } else {
            throw new \InvalidArgumentException('Type d\'entité non supporté pour l\'assignation de tag');
        }
        $this->em->persist($entity);
        $this->em->flush();
    }

    private function hydrate(Tag $tag, array $data): void
    {
        if (isset($data['label'])) {
            $tag->setLabel($data['label']);
            // Génère le code automatiquement à partir du label (minuscule, sans espace)
            $code = strtolower(trim(preg_replace('/\s+/', '_', $data['label'])));
            $tag->setCode($code);
        }
        if (isset($data['description'])) $tag->setDescription($data['description']);
        if (isset($data['color'])) {
            $tag->setColor($data['color']);
        } else {
            // Génère une couleur hexadécimale aléatoire
            $randomColor = sprintf('#%06X', mt_rand(0, 0xFFFFFF));
            $tag->setColor($randomColor);
        }
    }
}
