<?php

namespace App\Managers;

use App\Entity\Company;
use App\Entity\Contact;
use App\Entity\ItemType;
use App\Entity\Tag;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Exception;

use App\Managers\PhoneNumberManager;
use App\Managers\MailManager;
use App\Managers\TagManager;

class ContactManager extends Manager
{
    private PhoneNumberManager $phoneManager;
    private MailManager $mailManager;
    private TagManager $tagManager;

    public function __construct(
        private ManagerRegistry $registry,
        private PropertyManager $propertyManager,
        PhoneNumberManager $phoneManager,
        EntityManagerInterface $em,
        MailManager $mailManager,
        TagManager $tagManager
    ) {
        $this->em = $em;
        $this->phoneManager = $phoneManager;
        $this->mailManager = $mailManager;
        $this->tagManager = $tagManager;
    }

    public function edit($data): ?Contact
    {
        $contact = null;

        if (isset($data['id'])) {
            $contact = $this->registry->getManager()->getRepository(Contact::class)->findOneBy(['id' => $data['id']]);
        }

        if (!($contact instanceof Contact)) {
            $contact = new Contact();
        }

        $contact->setSource($data['source'] ?? 'manuel');



        if (isset($data['company'])) {
            $companyId = basename($data['company']);
            $company = $this->registry->getManager()->getRepository(Company::class)->find($companyId);
            $contact->setCompany($company);
        }

        $itemType = $this->registry->getManager()->getRepository(ItemType::class)->findOneBy(['code' => 'contact']);
        $contact->setItemType($itemType);

        $this->registry->getManager()->persist($contact);

        if (!empty($data['properties'])) {
            foreach ($data['properties'] as $p) {
                $property = $this->propertyManager->edit($p , $itemType);
                if (isset($property)) {
                    $contact->addProperty($property);
                }
            }
        }

        // Le manager peut être défini manuellement ou sera auto-rempli par UserEvent si absent
        if (isset($data['manager'])) {
            $contact->setManager($data['manager']);
        }

        if (!empty($data['phones'])) {
            foreach ($data['phones'] as $p) {
                $phone = $this->phoneManager->edit($p);
                if (isset($phone)) {
                    $contact->addPhone($phone);
                }
            }
        }


        if (!empty($data['mails'])) {
            foreach ($data['mails'] as $m) {
                $mail = $this->mailManager->edit($m);
                if (isset($mail)) {
                    $contact->addMail($mail);
                }
            }
        }

        if (!empty($data['tags'])) {
            foreach ($data['tags'] as $t) {
               $tag = $this->registry->getManager()->getRepository(Tag::class)->find($t['id']);
                if (isset($tag)) {
                    $contact->addTag($tag);
                }
            }
        }




        $this->registry->getManager()->flush();

        return $contact;
    }



    public function merge(Contact $source, Contact $target): void
    {
        $this->em->beginTransaction();
        try {
            
            // 1. Transfert des propriétés
            foreach ($source->getProperties() as $property) {
                $property->setContact($target);
                $this->em->persist($property);
            }


             // ". Transfert des cotations et contrats
            

            // 2. Suppression du contact source
            $this->em->remove($source);

            // 3. Enregistrement en base
            $this->em->flush();
            $this->em->commit();
        } catch (\Throwable $e) {
            $this->em->rollback();
            throw $e;
        }
    }

    public function addPhoto($data)
    {
        $contact = $this->registry->getManager()->getRepository(Contact::class)->findOneBy(['id' => $data['contact']]);
        if (!$contact instanceof Contact) {
            throw new Exception('Contact introuvable');
        }
        $contact->setPhoto($data['photo']);

        $this->registry->getManager()->persist($contact);
        $this->registry->getManager()->flush();
        return true;
    }

    public function delete(int $id, bool $cascade = true): void
    {
        $contact = $this->registry->getManager()->getRepository(Contact::class)->find($id);

        if (!$contact instanceof Contact) {
            throw new Exception('Contact introuvable');
        }

        if ($contact->getRemoveAt() instanceof \DateTime) {
            throw new Exception('Contact déjà supprimé');
        }

        $contact->setRemoveAt(new \DateTime());
        $this->registry->getManager()->persist($contact);

        if ($cascade) {
            $this->cascadeDelete($contact);
        }

        $this->registry->getManager()->flush();
    }

    private function cascadeDelete(Contact $contact): void
    {
        // Supprimer tous les Deals du Contact
        foreach ($contact->getDeals() as $deal) {
            if (!$deal->getRemoveAt()) {
                $deal->setRemoveAt($contact->getRemoveAt());
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

        // Supprimer Activities liées directement au Contact (sans Deal)
        $activities = $this->registry->getManager()->getRepository(\App\Entity\Activity::class)
            ->findBy(['contact' => $contact, 'deal' => null]);

        foreach ($activities as $activity) {
            if (!$activity->getRemoveAt()) {
                $activity->setRemoveAt($contact->getRemoveAt());
                $this->registry->getManager()->persist($activity);
            }
        }

        // Mail et PhoneNumber : PAS DE SUPPRESSION (restent orphelins)
    }

    public function restore(int $id, bool $cascade = true): void
    {
        $contact = $this->registry->getManager()->getRepository(Contact::class)->find($id);

        if (!$contact instanceof Contact) {
            throw new Exception('Contact introuvable');
        }

        if (!$contact->getRemoveAt() instanceof \DateTime) {
            throw new Exception('Contact non supprimé');
        }

        $contactRemoveAt = $contact->getRemoveAt();
        $contact->setRemoveAt(null);
        $contact->setRestoredAt(new \DateTime());
        $this->registry->getManager()->persist($contact);

        if ($cascade) {
            $this->cascadeRestore($contact, $contactRemoveAt);
        }

        $this->registry->getManager()->flush();
    }

    private function cascadeRestore(Contact $contact, \DateTime $contactRemoveAt): void
    {
        // Restaurer tous les Deals du Contact supprimés en même temps ou après
        foreach ($contact->getDeals() as $deal) {
            if ($deal->getRemoveAt() && $deal->getRemoveAt() >= $contactRemoveAt) {
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

        // Restaurer Activities liées directement au Contact (sans Deal)
        $activities = $this->registry->getManager()->getRepository(\App\Entity\Activity::class)
            ->findBy(['contact' => $contact, 'deal' => null]);

        foreach ($activities as $activity) {
            if ($activity->getRemoveAt() && $activity->getRemoveAt() >= $contactRemoveAt) {
                $activity->setRemoveAt(null);
                $activity->setRestoredAt(new \DateTime());
                $this->registry->getManager()->persist($activity);
            }
        }
    }

    public function getGroups(): array
    {
        return ['property_model_list'];
    }
}
