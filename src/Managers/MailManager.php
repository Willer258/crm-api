<?php

namespace App\Managers;

use App\Entity\Mail;
use Doctrine\ORM\EntityManagerInterface;

class MailManager
{
    public function __construct(private EntityManagerInterface $em) {}

    public function createFromArray(array $data): Mail
    {
        $mail = new Mail();
        $mail->setEmail($data['email'] ?? '');
        // Associer Contact ou Company ici si besoin
        $this->em->persist($mail);
        $this->em->flush();
        return $mail;
    }

    public function updateFromArray(Mail $mail, array $data): Mail
    {
        $mail->setEmail($data['email'] ?? $mail->getEmail());
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
