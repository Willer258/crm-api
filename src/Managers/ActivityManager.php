<?php

namespace App\Managers;

use App\Entity\Activity;
use App\Entity\Company;
use App\Entity\Contact;
use App\Entity\Deal;
use Doctrine\ORM\EntityManagerInterface;

class ActivityManager
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em ,  private NoteManager $noteManager)
    {
        $this->em = $em;
    }

    public function createFromArray(array $data): Activity
    {
        $activity = new Activity();
        $this->hydrate($activity, $data);
        
        $this->em->flush();
        return $activity;
    }

    public function updateFromArray(Activity $activity, array $data): Activity
    {
        $this->hydrate($activity, $data);
        $this->em->persist($activity);
        $this->em->flush();
        return $activity;
    }

    private function hydrate(Activity $activity, array $data): void
    {
        
        if (isset($data['type'])) $activity->setType($data['type']);

        if (isset($data['name'])) $activity->setName($data['name']);
        if (isset($data['startDate'])) $activity->setStartDate(new \DateTime($data['startDate']));
        if (isset($data['endDate'])) $activity->setEndDate(new \DateTime($data['endDate']));
        if (isset($data['location'])) $activity->setLocation($data['location']);
        (isset($data['performed'])) ? $activity->setPerformed($data['performed']) : $activity->setPerformed(false);
        (isset($data['notify'])) ? $activity->setNotify($data['notify']) : $activity->setNotify(false);
        if (isset($data['notifyDate'])) $activity->setNotifyDate(new \DateTime($data['notifyDate']));
        if (isset($data['description'])) $activity->setDescription($data['description']);
        if (isset($data['manager'])) $activity->setManager($data['manager']);

        if (isset($data['deal']) || isset($data ['contact']) || isset($data ['company'])) {
            if (isset($data['deal'])){
                $deal = $this->em->find(Deal::class, $data['deal']);
                if ($deal instanceof Deal) {
                    $activity->setDeal($deal);
                }else{
                    throw new \Exception("L'affaire n'existe pas");
                }
            }
            if (isset($data['contact'])) {
                $contact = $this->em->find(Contact::class, $data['contact']);
                if ($contact instanceof Contact) {
                    $activity->setContact($contact);
                }else{
                    throw new \Exception("Le contact n'existe pas");
                }
            }
            
            if (isset($data['company'])) {
                $company = $this->em->find(Company::class, $data['company']);
                if ($company instanceof Company) {
                    $activity->setCompany($company);
                }else{
                    throw new \Exception("L'entreprise n'existe pas");
                }
            }
        }
        else{
            throw new \Exception("Une activité doit etre liée à une affaire, un contact ou une entreprise");
        }
        if (isset($data['notes'])) {
          foreach ($data['notes'] as $note) {
            $note = $this->noteManager->createFromArray($note);
            $activity->addNote($note);
          }
        }
    }

    public function delete(int $id): void
    {
        $activity = $this->em->getRepository(Activity::class)->findOneBy(['id' => $id]);
        if (!$activity instanceof Activity) {
            throw new \Exception("L'activité n'existe pas");
        }

        $activity->setRemoveAt(new \DateTime());
        $this->em->persist($activity);
        $this->em->flush();
    }
}
