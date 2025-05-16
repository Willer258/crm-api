<?php

namespace App\Managers;

use App\Entity\Company;
use App\Entity\Contact;
use App\Entity\ItemType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Exception;

class CompanyManager extends Manager
{


    public function __construct(private ManagerRegistry $registry, private PropertyManager $propertyManager ,  EntityManagerInterface $em) {
        $this->em = $em;
    }

    public function edit($data): ?Company
    {
        $company = null;

        if (isset($data['id'])) {
            $company = $this->registry->getManager()->getRepository(Company::class)->findOneBy(['id' => $data['id']]);
        }

        if (!($company instanceof Company)) {
            $company = new Company();
        }

    
            $itemType = $this->registry->getManager()->getRepository(ItemType::class)->findOneBy(['code' => 'company']);
            $company->setItemType($itemType);
            
        $this->registry->getManager()->persist($company);

        if (!empty($data['properties'])) {
            foreach ($data['properties'] as $p) {
                $property = $this->propertyManager->edit($p);
                if (isset($property)) {
                    $company->addProperty($property);
                }
            }
        }


        $this->registry->getManager()->flush();

        return $company;
    }



    public function merge(Company $source, Company $target): void
    {
        $this->em->beginTransaction();
        try {
            
            // 1. Transfert des propriétés
            foreach ($source->getProperties() as $property) {
                $property->setCompany($target);
                $this->em->persist($property);
            }


            foreach ($source->getContacts() as $company) {
                $company->setCompany($target);
                $this->em->persist($company);
            }


             // ". Transfert des cotations et contrats
            

            // 2. Suppression du company source
            $this->em->remove($source);

            // 3. Enregistrement en base
            $this->em->flush();
            $this->em->commit();
        } catch (\Throwable $e) {
            $this->em->rollback();
            throw $e;
        }
    }

    public function delete($idProperty)
    {
        $company = null;

        $company = $this->registry->getManager()->getRepository(Company::class)->findOneBy(['id' => $idProperty]);



        if ($company instanceof Company) {

            $company->setRemoveAt(new \DateTime());
            $this->registry->getManager()->persist($company);
            $this->registry->getManager()->flush();

            return true;
        } else {
            throw new Exception('Entreprise introuvable');
        }
    }

    public function getGroups(): array
    {
        return ['company:list'];
    }
}
