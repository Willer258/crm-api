<?php

namespace App\Managers;

use App\Entity\Contact;
use App\Entity\Company;
use App\Entity\Mail;
use Doctrine\ORM\EntityManagerInterface;

class MailManager
{
    public function __construct(private EntityManagerInterface $em) {}


    public function edit($data): ?Mail
    {
        $mail = null;
        if (isset($data['id'])) {
            $mail = $this->em->getRepository(Mail::class)->find($data['id']);
        }
        if (!($mail instanceof Mail)) {
            $mail = new Mail();
        }
        // Accepter soit 'email' soit 'address' (format du Form)
        $emailValue = $data['email'] ?? $data['address'] ?? null;
        if ($emailValue) {
            $mail->setEmail($emailValue);
        }
        // Gérer le type si fourni
        if (isset($data['type'])) {
            $mail->setType($data['type']);
        }
        $this->em->persist($mail);
        return $mail;
    }

    public function createFromArray(array $data): Mail
    {
        $mail = new Mail();
        if (isset($data['email'])) {
            $mail->setEmail($data['email']);
        }else{
            throw new \Exception('Email is required');
        }
        if (isset($data['contactId'])) {
            $contact = $this->em->getRepository(Contact::class)->find($data['contactId']);
            if ($contact instanceof Contact) {
                $mail->setContact($contact);
            }
        }
        if (isset($data['companyId'])) {
            $company = $this->em->getRepository(Company::class)->find($data['companyId']);
            if ($company instanceof Company) {
                $mail->setCompany($company);
            }
        }
        if(isset($data['type'])){
            $mail->setType($data['type']);
        }
        $this->em->persist($mail);
        $this->em->flush();
        return $mail;
    }

    public function updateFromArray(Mail $mail, array $data): Mail
    {
        if (isset($data['email'])) {
            $mail->setEmail($data['email']);
        }else{
            throw new \Exception('Email is required');
        }
        if (isset($data['contactId'])) {
            $contact = $this->em->getRepository(Contact::class)->find($data['contactId']);
            if ($contact instanceof Contact) {
                $mail->setContact($contact);
            }
        }
        if (isset($data['companyId'])) {
            $company = $this->em->getRepository(Company::class)->find($data['companyId']);
            if ($company instanceof Company) {
                $mail->setCompany($company);
            }
        }
        if(isset($data['type'])){
            $mail->setType($data['type']);
        }
        $this->em->flush();
        return $mail;
    }

    public function delete(?Mail $mail): void
    {
        if ($mail) {
            $this->em->remove($mail);
            $this->em->flush();
        }
    }
}
