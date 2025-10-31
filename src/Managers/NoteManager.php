<?php

namespace App\Managers;

use App\Entity\Note;
use App\Entity\Activity;
use App\Entity\Company;
use App\Entity\Contact;
use App\Entity\Deal;
use Doctrine\ORM\EntityManagerInterface;

class NoteManager
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Crée une note à partir d'un tableau de données et la lie à une activité si précisé
     */
    public function createFromArray(array $data): Note
    {
        $note = new Note();
        $this->hydrate($note, $data , create: true);
        $this->em->persist($note);
        $this->em->flush();
        return $note;
    }

    public function edit($data): Note
    {
      $note = $this->em->find(Note::class, $data['id']);
      if (!$note instanceof Note) {
        throw new \Exception('Note non trouvée');
      }
      $this->hydrate($note, $data);
      $this->em->flush();
      return $note;
    }

    /**
     * Met à jour une note existante à partir d'un tableau de données
     */
    public function updateFromArray(Note $note, array $data): Note
    {
        $this->hydrate($note, $data);
        $this->em->flush();
        return $note;
    }

    public function delete(int $id, bool $cascade = true): void
    {
        $note = $this->em->getRepository(Note::class)->find($id);

        if (!$note instanceof Note) {
            throw new \Exception('Note introuvable');
        }

        if ($note->getRemoveAt() instanceof \DateTime) {
            throw new \Exception('Note déjà supprimée');
        }

        $note->setRemoveAt(new \DateTime());
        $this->em->persist($note);
        $this->em->flush();
    }

    public function restore(int $id, bool $cascade = true): void
    {
        $note = $this->em->getRepository(Note::class)->find($id);

        if (!$note instanceof Note) {
            throw new \Exception('Note introuvable');
        }

        if (!$note->getRemoveAt() instanceof \DateTime) {
            throw new \Exception('Note non supprimée');
        }

        $note->setRemoveAt(null);
        $note->setRestoredAt(new \DateTime());
        $this->em->persist($note);
        $this->em->flush();
    }

    private function hydrate(Note $note, array $data, bool $create = false): void
    {
        if (isset($data['content'])) $note->setContent($data['content']);

        
        
        if ($create && (isset($data['company']) || isset($data['contact']) || isset($data['deal']) || isset($data['activity']))) {
            if (isset($data['company'])) {
                $company = $this->em->find(Company::class, $data['company']);
                if ($company instanceof Company) {
                    $note->setCompany($company);
                }
                else{
                    throw new \Exception('L\'entreprise n\'existe pas');
                }
            }
            if (isset($data['contact'])) {
                $contact = $this->em->find(Contact::class, $data['contact']);
                if ($contact instanceof Contact) {
                    $note->setContact($contact);
                }
                else{
                    throw new \Exception('Le contact n\'existe pas');
                }
            }
            if (isset($data['deal'])) {
                $deal = $this->em->find(Deal::class, $data['deal']);
                if ($deal instanceof Deal) {
                    $note->setDeal($deal);
                }
                else{
                    throw new \Exception('L\'affaire n\'existe pas');
                }
            }
            if (isset($data['activity'])) {
                $activity = $this->em->find(Activity::class, $data['activity']);
                if ($activity instanceof Activity) {
                    $note->setActivity($activity);
                }
                else{
                    throw new \Exception('L\'activite n\'existe pas');
                }
            }


            
        }
        elseif(!$note->getActivity() && !$note->getCompany() && !$note->getContact() && !$note->getDeal()){
            throw new \Exception('Ta note doit etre liée à une activité, une entreprise, un contact ou une affaire');
        }
        
       
    }
}
