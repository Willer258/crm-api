<?php

namespace App\Managers;

use App\Entity\Company;
use App\Entity\Contact;
use App\Entity\PhoneNumber;
use Doctrine\ORM\EntityManagerInterface;

class PhoneNumberManager
{
    public function __construct(private EntityManagerInterface $em) {}


    public function edit(array $data): ?PhoneNumber
    {
        $phoneNumber = null;
        if (isset($data['id'])) {
            $phoneNumber = $this->em->getRepository(PhoneNumber::class)->find($data['id']);
        }
        if (!($phoneNumber instanceof PhoneNumber)) {
            $phoneNumber = new PhoneNumber();
        }
        if (isset($data['number'])) {
            $phoneNumber->setNumber($data['number']);
        }
        $this->em->persist($phoneNumber);
        return $phoneNumber;
    }

    public function createFromArray(array $data): ?PhoneNumber
    {
        $phoneNumber = new PhoneNumber();
        if (isset($data['number'])) {
            $phoneNumber->setNumber($data['number']);
        }
        if (isset($data['contactId'])) {
            $contact = $this->em->getRepository(Contact::class)->find($data['contactId']);
            if ($contact instanceof Contact) {
                $phoneNumber->setContact($contact);
            }
        }
        if (isset($data['companyId'])) {
            $company = $this->em->getRepository(Company::class)->find($data['companyId']);
            if ($company instanceof Company) {
                $phoneNumber->setCompany($company);
            }
        }

        if(isset($data['type'])){
            $phoneNumber->setType($data['type']);
        }

        $this->em->persist($phoneNumber);
        $this->em->flush();
        return $phoneNumber;
    }

    public function updateFromArray(PhoneNumber $phoneNumber, array $data): ?PhoneNumber
    {
        if (isset($data['number'])) {
            $phoneNumber->setNumber($data['number']);
        }
        if (isset($data['contactId'])) {
            $contact = $this->em->getRepository(Contact::class)->find($data['contactId']);
            if ($contact instanceof Contact) {
                $phoneNumber->setContact($contact);
            }
        }
        if (isset($data['companyId'])) {
            $company = $this->em->getRepository(Company::class)->find($data['companyId']);
            if ($company instanceof Company) {
                $phoneNumber->setCompany($company);
            }
        }

        if(isset($data['type'])){
            $phoneNumber->setType($data['type']);
        }

        $this->em->flush();
        return $phoneNumber;
    }

    public function delete(?PhoneNumber $phoneNumber): void
    {
        if ($phoneNumber) {
            $this->em->remove($phoneNumber);
            $this->em->flush();
        }
    }
}

