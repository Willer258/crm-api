<?php

namespace App\Managers;

use App\Entity\Company;
use App\Entity\ItemType;
use App\Entity\Tag;
use App\Managers\MailManager;
use App\Managers\PhoneNumberManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Exception;

class CompanyManager extends Manager
{


    public function __construct(private ManagerRegistry $registry, private PropertyManager $propertyManager ,  EntityManagerInterface $em, private PhoneNumberManager $phoneManager, private MailManager $mailManager) {
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


        if (!empty($data['phones'])) {
            foreach ($data['phones'] as $p) {
                $phone = $this->phoneManager->edit($p);
                if (isset($phone)) {
                    $company->addPhone($phone);
                }
            }
        }

        if (!empty($data['mails'])) {
            foreach ($data['mails'] as $m) {
                $mail = $this->mailManager->edit($m);
                if (isset($mail)) {
                    $company->addMail($mail);
                }
            }
        }


        if (!empty($data['tags'])) {
            foreach ($data['tags'] as $t) {
               $tag = $this->registry->getManager()->getRepository(Tag::class)->find($t['id']);
                if (isset($tag)) {
                    $company->addTag($tag);
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

    public function delete(int $id, bool $cascade = true): void
    {
        $company = $this->registry->getManager()->getRepository(Company::class)->find($id);

        if (!$company instanceof Company) {
            throw new Exception('Entreprise introuvable');
        }

        if ($company->getRemoveAt() instanceof \DateTime) {
            throw new Exception('Entreprise déjà supprimée');
        }

        $company->setRemoveAt(new \DateTime());
        $this->registry->getManager()->persist($company);

        if ($cascade) {
            $this->cascadeDelete($company);
        }

        $this->registry->getManager()->flush();
    }

    private function cascadeDelete(Company $company): void
    {
        // Dissocier tous les Contacts (deviennent orphelins, pas supprimés)
        foreach ($company->getContacts() as $contact) {
            $contact->setCompany(null);
            $this->registry->getManager()->persist($contact);
        }

        // Supprimer tous les Deals de la Company
        foreach ($company->getDeals() as $deal) {
            if (!$deal->getRemoveAt()) {
                $deal->setRemoveAt($company->getRemoveAt());
                $this->registry->getManager()->persist($deal);

                // Cascade vers Activities du Deal
                foreach ($deal->getActivities() as $activity) {
                    if (!$activity->getRemoveAt()) {
                        $activity->setRemoveAt($deal->getRemoveAt());
                        $this->registry->getManager()->persist($activity);
                    }
                }
            }
        }

        // Supprimer Activities liées directement à la Company (sans Deal)
        $activities = $this->registry->getManager()->getRepository(\App\Entity\Activity::class)
            ->findBy(['company' => $company, 'deal' => null]);

        foreach ($activities as $activity) {
            if (!$activity->getRemoveAt()) {
                $activity->setRemoveAt($company->getRemoveAt());
                $this->registry->getManager()->persist($activity);
            }
        }
    }

    public function restore(int $id, bool $cascade = true): void
    {
        $company = $this->registry->getManager()->getRepository(Company::class)->find($id);

        if (!$company instanceof Company) {
            throw new Exception('Entreprise introuvable');
        }

        if (!$company->getRemoveAt() instanceof \DateTime) {
            throw new Exception('Entreprise non supprimée');
        }

        $companyRemoveAt = $company->getRemoveAt();
        $company->setRemoveAt(null);
        $company->setRestoredAt(new \DateTime());
        $this->registry->getManager()->persist($company);

        if ($cascade) {
            $this->cascadeRestore($company, $companyRemoveAt);
        }

        $this->registry->getManager()->flush();
    }

    private function cascadeRestore(Company $company, \DateTime $companyRemoveAt): void
    {
        // Restaurer tous les Deals de la Company supprimés en même temps ou après
        foreach ($company->getDeals() as $deal) {
            if ($deal->getRemoveAt() && $deal->getRemoveAt() >= $companyRemoveAt) {
                $dealRemoveAt = $deal->getRemoveAt();
                $deal->setRemoveAt(null);
                $deal->setRestoredAt(new \DateTime());
                $this->registry->getManager()->persist($deal);

                // Cascade vers Activities du Deal
                foreach ($deal->getActivities() as $activity) {
                    if ($activity->getRemoveAt() && $activity->getRemoveAt() >= $dealRemoveAt) {
                        $activity->setRemoveAt(null);
                        $activity->setRestoredAt(new \DateTime());
                        $this->registry->getManager()->persist($activity);
                    }
                }
            }
        }

        // Restaurer Activities liées directement à la Company (sans Deal)
        $activities = $this->registry->getManager()->getRepository(\App\Entity\Activity::class)
            ->findBy(['company' => $company, 'deal' => null]);

        foreach ($activities as $activity) {
            if ($activity->getRemoveAt() && $activity->getRemoveAt() >= $companyRemoveAt) {
                $activity->setRemoveAt(null);
                $activity->setRestoredAt(new \DateTime());
                $this->registry->getManager()->persist($activity);
            }
        }
    }

    public function addPhoto($data)
    {
        $company = $this->registry->getManager()->getRepository(Company::class)->findOneBy(['id' => $data['company']]);
        if (!$company instanceof Company) {
            throw new Exception('Entreprise introuvable');
        }
        $company->setPhoto($data['photo']);
        $this->registry->getManager()->persist($company);
        $this->registry->getManager()->flush();
        return true;
    }

    public function getGroups(): array
    {
        return ['company:list'];
    }
}
