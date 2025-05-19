<?php

namespace App\Managers;

// À compléter après création de l'entité PhoneNumber
use Doctrine\ORM\EntityManagerInterface;

class PhoneNumberManager
{
    public function __construct(private EntityManagerInterface $em) {}

    public function createFromArray(array $data): ?\App\Entity\PhoneNumber
    {
        $phoneNumber = new \App\Entity\PhoneNumber();
        if (isset($data['number'])) {
            $phoneNumber->setNumber($data['number']);
        }
        $this->em->persist($phoneNumber);
        $this->em->flush();
        return $phoneNumber;
    }

    public function updateFromArray(\App\Entity\PhoneNumber $phoneNumber, array $data): ?\App\Entity\PhoneNumber
    {
        if (isset($data['number'])) {
            $phoneNumber->setNumber($data['number']);
        }
        $this->em->flush();
        return $phoneNumber;
    }

    public function delete(?\App\Entity\PhoneNumber $phoneNumber): void
    {
        if ($phoneNumber) {
            $this->em->remove($phoneNumber);
            $this->em->flush();
        }
    }
}

