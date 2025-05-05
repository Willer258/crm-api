<?php

namespace App\Managers;

use App\Entity\Company;
use App\Entity\Contact;
use Doctrine\Persistence\ManagerRegistry;
use Exception;

class ContactManager extends Manager
{


    public function __construct(private ManagerRegistry $registry, private PropertyManager $propertyManager) {}

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

        $this->registry->getManager()->persist($contact);

        if (!empty($data['properties'])) {
            foreach ($data['properties'] as $p) {
                $property = $this->propertyManager->edit($p);
                if (isset($property)) {
                    $contact->addProperty($property);
                }
            }
        }


        $this->registry->getManager()->flush();

        return $contact;
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
