<?php

namespace App\Managers;

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
        if (isset($data['objet'])) {
            $deal->setObjet($data['objet']);
        }
        if (isset($data['manager'])) {
            $deal->setManager($data['manager']);
        }else{
            $deal->setManager('unknown');
        }
        if (isset($data['contact_id'])) {
            $contact = $em->getRepository(Contact::class)->find($data['contact_id']);
            if ($contact instanceof Contact) {
                $deal->setContact($contact);
            }
            else{
                throw new Exception('Il faut un contact a l\'affaire');
            }
        } 
        if (isset($data['step_id'])) {
            $step = $em->getRepository(PipelineStep::class)->find($data['step_id']);
            if ($step instanceof PipelineStep) {
                $deal->setStep($step);
            }
            else{
                throw new Exception('Il faut un etape de pipeline a l\'affaire');
            }
        }
        if (isset($data['tag_ids']) && is_array($data['tag_ids'])) {
            foreach ($data['tag_ids'] as $tagId) {
                $tag = $em->getRepository(Tag::class)->find($tagId);
                if ($tag instanceof Tag) {
                    $deal->getTags()->add($tag);
                }
                else{
                    throw new Exception('Il faut des tags a l\'affaire');
                }
            }
        }


        $em->persist($deal);
        $em->flush();

        return $deal;
    }


}
