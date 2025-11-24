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
        $isExistingContact = false;

        if (isset($data['id'])) {
            $contact = $this->registry->getManager()->getRepository(Contact::class)->findOneBy(['id' => $data['id']]);
            if ($contact instanceof Contact) {
                $isExistingContact = true;
            }
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
            // Si c'est une mise à jour (contact existait déjà en base), utiliser addPropertiesIfNotExists
            // pour ne pas écraser les propriétés existantes
            if ($isExistingContact) {
                $this->addPropertiesIfNotExists($contact, $data['properties']);
            } else {
                // Si c'est une création (nouveau contact), utiliser la méthode classique
                // Mais d'abord, s'assurer que tous les PropertyModels existent
                foreach ($data['properties'] as $p) {
                    if (isset($p['propertyModel'])) {
                        // Chercher le PropertyModel
                        $propertyModel = $this->registry->getManager()->getRepository(\App\Entity\PropertyModel::class)->findOneBy([
                            'class' => $p['propertyModel'],
                            'itemType' => $itemType
                        ]);

                        // Si pas trouvé par class, chercher par label
                        if (!($propertyModel instanceof \App\Entity\PropertyModel)) {
                            $propertyModel = $this->registry->getManager()->getRepository(\App\Entity\PropertyModel::class)->findOneBy([
                                'label' => $p['propertyModel'],
                                'itemType' => $itemType
                            ]);
                        }

                        // Si toujours pas trouvé, créer le PropertyModel
                        if (!($propertyModel instanceof \App\Entity\PropertyModel)) {
                            $propertyModel = new \App\Entity\PropertyModel();
                            $propertyModel->setLabel($p['propertyModel']);
                            $propertyModel->setClass($p['propertyModel']);
                            $propertyModel->setType('text'); // Type par défaut
                            $propertyModel->setItemType($itemType);
                            $propertyModel->setIdentifier(false);
                            $this->registry->getManager()->persist($propertyModel);
                            $this->registry->getManager()->flush(); // Flush immédiatement pour avoir l'ID
                        }
                    }

                    // Maintenant créer la Property avec PropertyManager
                    $property = $this->propertyManager->edit($p , $itemType);
                    if (isset($property)) {
                        $contact->addProperty($property);
                    }
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

    /**
     * Ajoute des PropertyModel à un Contact sans écraser les existantes
     * Si une PropertyModel existe déjà, elle n'est PAS mise à jour
     * Seules les nouvelles PropertyModel sont ajoutées
     */
    public function addPropertiesIfNotExists(Contact $contact, array $properties): void
    {
        if (empty($properties)) {
            return;
        }

        $itemType = $contact->getItemType();

        if (!($itemType instanceof ItemType)) {
            throw new Exception('ItemType du contact non défini');
        }

        foreach ($properties as $propData) {
            // Chercher le PropertyModel
            $propertyModel = null;

            if (isset($propData['propertyModel'])) {
                // Chercher par class d'abord
                $propertyModel = $this->registry->getManager()->getRepository(\App\Entity\PropertyModel::class)->findOneBy([
                    'class' => $propData['propertyModel'],
                    'itemType' => $itemType
                ]);

                // Si pas trouvé par class, chercher par label
                if (!($propertyModel instanceof \App\Entity\PropertyModel)) {
                    $propertyModel = $this->registry->getManager()->getRepository(\App\Entity\PropertyModel::class)->findOneBy([
                        'label' => $propData['propertyModel'],
                        'itemType' => $itemType
                    ]);
                }

                // Si toujours pas trouvé, créer le PropertyModel
                if (!($propertyModel instanceof \App\Entity\PropertyModel)) {
                    $propertyModel = new \App\Entity\PropertyModel();
                    $propertyModel->setLabel($propData['propertyModel']);
                    $propertyModel->setClass($propData['propertyModel']);
                    $propertyModel->setType('text'); // Type par défaut
                    $propertyModel->setItemType($itemType);
                    $propertyModel->setIdentifier(false);
                    $this->registry->getManager()->persist($propertyModel);
                }
            }

            if (!($propertyModel instanceof \App\Entity\PropertyModel)) {
                continue; // Skip si PropertyModel introuvable
            }

            // Vérifier si la Property existe déjà pour ce Contact et ce PropertyModel
            $existingProperty = null;
            foreach ($contact->getProperties() as $prop) {
                if ($prop->getPropertyModel()?->getId() === $propertyModel->getId()) {
                    $existingProperty = $prop;
                    break;
                }
            }

            // Si la Property n'existe pas, la créer
            if (!$existingProperty) {
                $property = new \App\Entity\Property();
                $property->setContact($contact);
                $property->setPropertyModel($propertyModel);
                $property->setValue($propData['value'] ?? '');
                $this->registry->getManager()->persist($property);
                $contact->addProperty($property);
            }
            // Si elle existe, on ne fait RIEN (pas d'écrasement)
        }

        $this->registry->getManager()->flush();
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
