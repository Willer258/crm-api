<?php

namespace App\Managers;

use App\Entity\Company;
use App\Entity\Deal;
use App\Entity\Contact;
use App\Entity\PipelineStep;
use App\Entity\Tag;
use Doctrine\Persistence\ManagerRegistry;
use Exception;

class DealManager
{
    private ManagerRegistry $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Crée une nouvelle affaire (Deal)
     * @param array $data
     * @return Deal
     */
    public function editDeal(array $data): Deal
    {
        $em = $this->registry->getManager();


        $deal = new Deal();


        if (isset($data['id'])) {
            $deal = $em->getRepository(Deal::class)->findOneBy(['id' => $data['id']]);
        }


        if (!($deal instanceof Deal)) {
            $deal = new Deal(); 
        }
        if (isset($data['object'])) {
            $deal->setObject($data['object']);
        }


        
        if (isset($data['manager'])) {
            $deal->setManager($data['manager']);
        } elseif (!isset($data['id'])) {
            // Seulement définir 'unknown' lors de la création (pas de mise à jour)
            $deal->setManager('unknown');
        }






        if (isset($data['contact']) && !empty($data['contact']) ) {
            $contact = $em->getRepository(Contact::class)->find($data['contact']);
            if ($contact instanceof Contact) {
                $deal->setContact($contact);
            }
            else{
                throw new Exception('Il faut un contact a l\'affaire');
            }
        } 

        if (isset($data['company']) && !empty($data['company'])) {
            $company = $em->getRepository(Company::class)->find($data['company']);
            if ($company instanceof Company) {
                $deal->setCompany($company);
            }
            else{
                throw new Exception('Il faut une entreprise a l\'affaire');
            }
        } 
        if (isset($data['step'])) {
            $step = $em->getRepository(PipelineStep::class)->find($data['step']);
            if ($step instanceof PipelineStep) {
                $deal->setStep($step);
            }
            else{
                throw new Exception('Il faut un etape de pipeline a l\'affaire');
            }
        }
        if (!empty($data['tags'])) {
            foreach ($data['tags'] as $t) {
               $tag = $this->registry->getManager()->getRepository(Tag::class)->find($t['id']);
                if (isset($tag)) {
                    $deal->addTag($tag);
                }
            }
        }


        $em->persist($deal);
        $em->flush();

        return $deal;
    }

    public function delete(int $id, bool $cascade = true): void
    {
        $em = $this->registry->getManager();
        $deal = $em->getRepository(Deal::class)->find($id);

        if (!$deal instanceof Deal) {
            throw new Exception('Opportunité introuvable');
        }

        if ($deal->getRemoveAt() instanceof \DateTime) {
            throw new Exception('Opportunité déjà supprimée');
        }

        $deal->setRemoveAt(new \DateTime());
        $em->persist($deal);

        if ($cascade) {
            $this->cascadeDelete($deal);
        }

        $em->flush();
    }

    private function cascadeDelete(Deal $deal): void
    {
        // Supprimer toutes les Activities du Deal
        foreach ($deal->getActivities() as $activity) {
            if (!$activity->getRemoveAt()) {
                $activity->setRemoveAt($deal->getRemoveAt());
                $this->registry->getManager()->persist($activity);
            }
        }
    }

    public function restore(int $id, bool $cascade = true): void
    {
        $em = $this->registry->getManager();
        $deal = $em->getRepository(Deal::class)->find($id);

        if (!$deal instanceof Deal) {
            throw new Exception('Opportunité introuvable');
        }

        if (!$deal->getRemoveAt() instanceof \DateTime) {
            throw new Exception('Opportunité non supprimée');
        }

        $dealRemoveAt = $deal->getRemoveAt();
        $deal->setRemoveAt(null);
        $deal->setRestoredAt(new \DateTime());
        $em->persist($deal);

        if ($cascade) {
            $this->cascadeRestore($deal, $dealRemoveAt);
        }

        $em->flush();
    }

    private function cascadeRestore(Deal $deal, \DateTime $dealRemoveAt): void
    {
        // Restaurer toutes les Activities du Deal supprimées en même temps ou après
        foreach ($deal->getActivities() as $activity) {
            if ($activity->getRemoveAt() && $activity->getRemoveAt() >= $dealRemoveAt) {
                $activity->setRemoveAt(null);
                $activity->setRestoredAt(new \DateTime());
                $this->registry->getManager()->persist($activity);
            }
        }
    }

}
