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
        }else{
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


}
