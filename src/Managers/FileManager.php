<?php

namespace App\Managers;

use App\Entity\File;
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
    public function createFromArray(array $data): File
    {
        $file = new File();
        $this->hydrate($file, $data, create: true);
        $this->em->persist($file);
        $this->em->flush();
        return $file;
    }

    /**
     * Met à jour un fichier existant à partir d'un tableau de données
     */
    public function updateFromArray(File $file, array $data): File
    {
        $this->hydrate($file, $data);
        $this->em->flush();
        return $file;
    }

    public function delete(File $file): void
    {
        if ($file instanceof File) {
            $this->em->remove($file);
            $this->em->flush();
        }
    }

    private function hydrate(File $file, array $data, bool $create = false): void
    {
        if (isset($data['src'])) $file->setSrc($data['src']);

        // Gestion du type de fichier avec validation sur les constantes
        if (isset($data['type'])) {
            $validTypes = [
                File::TYPE_IMAGE,
                File::TYPE_PDF,
                File::TYPE_DOC,
                File::TYPE_XLS,
                File::TYPE_OTHER,
            ];
            $file->setType(in_array($data['type'], $validTypes, true) ? $data['type'] : File::TYPE_OTHER);
        }

        if ($create && (isset($data['company']) || isset($data['contact']) || isset($data['deal']))) {
            if (isset($data['company'])) {
                $company = $this->em->find(Company::class, $data['company']);
                if ($company instanceof Company) {
                    $file->setCompany($company);
                }
            }
            if (isset($data['contact'])) {
                $contact = $this->em->find(Contact::class, $data['contact']);
                if ($contact instanceof Contact) {
                    $file->setContact($contact);
                }
            }
            if (isset($data['deal'])) {
                $deal = $this->em->find(Deal::class, $data['deal']);
                if ($deal instanceof Deal) {
                    $file->setDeal($deal);
                }
            }
        }
    }
}
