<?php

namespace App\Managers;

use App\Entity\Asset;
use App\Entity\Company;
use App\Entity\Contact;
use App\Entity\Deal;
use Doctrine\ORM\EntityManagerInterface;

class FileManager
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Crée un fichier à partir d'un tableau de données et le lie à une entité si précisé
     */
    public function createFromArray(array $data):Asset
    {
        $asset = new Asset();
        $this->hydrate($asset, $data, create: true);
        $this->em->persist($asset);
        $this->em->flush();
        return $asset;
    }

    /**
     * Met à jour un fichier existant à partir d'un tableau de données
     */
    public function updateFromArray(Asset $asset, array $data): Asset
    {
        $this->hydrate($asset, $data);
        $this->em->flush();
        return $asset;
    }

    public function delete(Asset $asset): void
    {
        if ($asset instanceof Asset) {
            $this->em->remove($asset);
            $this->em->flush();
        }
    }

    private function hydrate(Asset $asset, array $data, bool $create = false): void
    {
        if (isset($data['src'])) $asset->setSrc($data['src']);


        if (isset($data['name'])) $asset->setName($data['name']);


        // Gestion du type de fichier avec validation sur les constantes
        if (isset($data['type'])) {
            $validTypes = [
                Asset::TYPE_IMAGE,
                Asset::TYPE_PDF,
                Asset::TYPE_DOC,
                Asset::TYPE_XLS,
                Asset::TYPE_OTHER,
            ];
            $asset->setType(in_array($data['type'], $validTypes, true) ? $data['type'] : Asset::TYPE_OTHER);
        } else {
            $asset->setType(Asset::TYPE_OTHER);
        }

        if ($create && (isset($data['company']) || isset($data['contact']) || isset($data['deal']))) {
            if (isset($data['company'])) {
                $company = $this->em->find(Company::class, $data['company']);
                if ($company instanceof Company) {
                    $asset->setCompany($company);
                }
            }
            if (isset($data['contact'])) {
                $contact = $this->em->find(Contact::class, $data['contact']);
                if ($contact instanceof Contact) {
                    $asset->setContact($contact);
                }
            }
            if (isset($data['deal'])) {
                $deal = $this->em->find(Deal::class, $data['deal']);
                if ($deal instanceof Deal) {
                    $asset->setDeal($deal);
                }
            }
        }
    }
}
