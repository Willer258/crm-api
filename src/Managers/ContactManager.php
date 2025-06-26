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

    public function delete($idProperty)
    {
        $contact = null;

        $contact = $this->registry->getManager()->getRepository(Contact::class)->findOneBy(['id' => $idProperty]);



        if ($contact instanceof Contact) {

            $contact->setRemoveAt(new \DateTime());
            $this->registry->getManager()->persist($contact);
            $this->registry->getManager()->flush();

            return true;
        } else {
            throw new Exception('Contact introuvable');
        }
    }

    public function getGroups(): array
    {
        return ['property_model_list'];
    }
}
