<?php

namespace App\Controller;

use App\Entity\Activity;
use App\Entity\Deal;
use App\Entity\PipelineStep;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use App\Managers\DealManager;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Exception;

#[Route('/deal', name: 'app_deal')]
final class DealController extends AbstractController
{
    private DealManager $dealManager;

    public function __construct(DealManager $dealManager, private ManagerRegistry $managerRegistry)
    {
        $this->dealManager = $dealManager;
    }




    #[Route('/edit', name: 'deal_edit', methods: ['POST'], options: ['description' => 'Edite une opportunité'])]
    public function edit(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data) {
            throw new Exception('Invalid data');
        }

        // Seulement définir le manager par défaut si non fourni ET c'est une création
        if ((!isset($data['manager']) || $data['manager'] === '') && !isset($data['id'])) {
            if ($this->getUser()) {
                $data['manager'] = $this->getUser()->getUserIdentifier();
            } else {
                $data['manager'] = 'unknown';
            }
        }

        $deal = $this->dealManager->editDeal($data);
        
        if ($deal instanceof Deal) {
            return $this->json([
                'status' => 'success',
                'deal' => $deal
            ], 200, [], ['groups' => 'contact:info']);
        }
        return $this->json([
            'status' => 'error',
            'message' => 'Impossible de creer ou modifier une affaire'
        ], 500, [], ['groups' => 'deal:edit']);
    }


    #[Route('/info/{id}', name: 'deal_info', methods: ['GET'], options: ['description' => 'Affiche les informations d\'une opportunité'])]
    public function info(int $id): JsonResponse
    {
        $deal = $this->managerRegistry->getManager()->getRepository(Deal::class)->find($id);
        if ($deal instanceof Deal) {
            return $this->json([
                'status' => 'success',
                'deal' => $deal
            ], 200, [], ['groups' => ['deal:info' , 'userManagement', 'infos']]);
        }
        return $this->json([
            'status' => 'error',
            'message' => 'Impossible de recuperer une affaire'
        ], 500, [], ['groups' => ['deal:info' , 'userManagement', 'infos']]);
    }


    #[Route('/change/step/{id}', name: 'deal_change_pipeline_step', methods: ['PATCH'], options: ['description' => 'Change l\'étape d\'une opportunité'])]
    public function changePipelineStep(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data) {
            throw new Exception('Invalid data');
        }
        $deal = $this->managerRegistry->getManager()->getRepository(Deal::class)->find($id);
        if ($deal instanceof Deal) {
            $pipelineStep = $this->managerRegistry->getManager()->getRepository(PipelineStep::class)->find($data['step_id']);
            if ($pipelineStep instanceof PipelineStep) {
                $deal->setStep($pipelineStep);
            }
            $this->managerRegistry->getManager()->persist($deal);
            $this->managerRegistry->getManager()->flush();
            return $this->json([
                'status' => 'success',
                'deal' => $deal
            ], 200, [], ['groups' => 'deal:edit']);
        }
        return $this->json([
            'status' => 'error',
            'message' => 'Impossible de recuperer une affaire'
        ], 500, [], ['groups' => 'deal:edit']);
    }

    #[Route('/win/{id}', name: 'deal_win', methods: ['GET'], options: ['description' => 'Marque une opportunité comme gagnée'])]
    public function win(int $id): JsonResponse
    {
       $deal = $this->managerRegistry->getManager()->getRepository(Deal::class)->find($id);
       if ($deal instanceof Deal) {
           $deal->setStatus(Deal::STATUS_WIN);
           $this->managerRegistry->getManager()->persist($deal);
           $this->managerRegistry->getManager()->flush();
           return $this->json([
               'status' => 'success',
               'deal' => $deal
           ], 200, [], ['groups' => 'deal:edit']);
       }
       return $this->json([
           'status' => 'error',
           'message' => 'Impossible de recuperer une affaire'
       ], 500, [], ['groups' => 'deal:edit']);
    }

    #[Route('/lose/{id}', name: 'deal_lose', methods: ['GET'], options: ['description' => 'Marque une opportunité comme perdue'])]
    public function lose(int $id): JsonResponse
    {
       $deal = $this->managerRegistry->getManager()->getRepository(Deal::class)->find($id);
       if ($deal instanceof Deal) {
           $deal->setStatus(Deal::STATUS_LOST);
           $this->managerRegistry->getManager()->persist($deal);
           $this->managerRegistry->getManager()->flush();
           return $this->json([
               'status' => 'success',
               'deal' => $deal
           ], 200, [], ['groups' => 'deal:edit']);
       }
       return $this->json([
           'status' => 'error',
           'message' => 'Impossible de recuperer une affaire'
       ], 500, [], ['groups' => 'deal:edit']);
    }


    #[Route('/unlose/unwin/{id}', name: 'deal_unlose_unwin', methods: ['GET'], options: ['description' => 'Annule le statut gagné ou perdu d\'une opportunité'])]
    public function unloseUnWin(int $id): JsonResponse
    {
       $deal = $this->managerRegistry->getManager()->getRepository(Deal::class)->find($id);
       if ($deal instanceof Deal) {
           $deal->setStatus(null);
           $this->managerRegistry->getManager()->persist($deal);
           $this->managerRegistry->getManager()->flush();
           return $this->json([
               'status' => 'success',
               'deal' => $deal
           ], 200, [], ['groups' => 'deal:edit']);
       }
       return $this->json([
           'status' => 'error',
           'message' => 'Impossible de recuperer une affaire'
       ], 500, [], ['groups' => 'deal:edit']);
    }


    #[Route('/dissociate/contact/{id}', name: 'deal_dissociate_contact', methods: ['GET'], options: ['description' => 'Dissocie le contact principal d\'une opportunité'])]
    public function dissociateContact(int $id): JsonResponse
    {
        $deal = $this->managerRegistry->getManager()->getRepository(Deal::class)->find($id);

        if (!$deal instanceof Deal) {
            return $this->json([
                'status' => 'error',
                'message' => 'Opportunité introuvable'
            ], 404);
        }

        $deal->setContact(null);
        $this->managerRegistry->getManager()->flush();

        return $this->json([
            'status' => 'success',
            'message' => 'Contact dissocié de l\'opportunité',
            'deal' => $deal
        ], 200, [], ['groups' => 'deal:info']);
    }


    #[Route('/dissociate/company/{id}', name: 'deal_dissociate_company', methods: ['GET'], options: ['description' => 'Dissocie l\'entreprise d\'une opportunité'])]
    public function dissociateCompany(int $id): JsonResponse
    {
        $deal = $this->managerRegistry->getManager()->getRepository(Deal::class)->find($id);

        if (!$deal instanceof Deal) {
            return $this->json([
                'status' => 'error',
                'message' => 'Opportunité introuvable'
            ], 404);
        }

        $deal->setCompany(null);
        $this->managerRegistry->getManager()->flush();

        return $this->json([
            'status' => 'success',
            'message' => 'Entreprise dissociée de l\'opportunité',
            'deal' => $deal
        ], 200, [], ['groups' => 'deal:info']);
    }


    #[Route('/remove/participant/{dealId}/{contactId}', name: 'deal_remove_participant', methods: ['GET'], options: ['description' => 'Retire un participant d\'une opportunité'])]
    public function removeParticipant(int $dealId, int $contactId): JsonResponse
    {
        $deal = $this->managerRegistry->getManager()->getRepository(Deal::class)->find($dealId);

        if (!$deal instanceof Deal) {
            return $this->json([
                'status' => 'error',
                'message' => 'Opportunité introuvable'
            ], 404);
        }

        $contact = $this->managerRegistry->getManager()->getRepository(\App\Entity\Contact::class)->find($contactId);

        if (!$contact instanceof \App\Entity\Contact) {
            return $this->json([
                'status' => 'error',
                'message' => 'Contact introuvable'
            ], 404);
        }

        $deal->removeParticipant($contact);
        $this->managerRegistry->getManager()->flush();

        return $this->json([
            'status' => 'success',
            'message' => 'Participant retiré de l\'opportunité',
            'deal' => $deal
        ], 200, [], ['groups' => 'deal:info']);
    }


    #[Route('/delete/{id}', name: 'deal_delete', methods: ['DELETE'], options: ['description' => 'Supprime une opportunité'])]
    public function delete(int $id): JsonResponse
    {
        try {
            $this->dealManager->delete($id);
            return $this->json([
                'status' => 'success',
                'message' => 'Opportunité supprimée'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }    
}

