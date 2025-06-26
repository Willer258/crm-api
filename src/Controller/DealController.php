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

        if (!isset($data['manager'])) {
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
            ], 200, [], ['groups' => 'deal:info']);
        }
        return $this->json([
            'status' => 'error',
            'message' => 'Impossible de recuperer une affaire'
        ], 500, [], ['groups' => 'deal:info']);
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
    

    #[Route('/delete/{id}', name: 'deal_delete', methods: ['DELETE'], options: ['description' => 'Supprime une opportunité'])]
    public function delete(int $id): JsonResponse
    {
       $deal = $this->managerRegistry->getManager()->getRepository(Deal::class)->find($id);
       if ($deal instanceof Deal) {

        if ($deal->getRemoveAt() instanceof \DateTime) {
            return $this->json([
                'status' => 'error',
                'message' => 'Affaire deja supprimé'
            ], 500, [], ['groups' => 'deal:edit']);
        }
           
        $deal->setRemoveAt(new \DateTime());
        $this->managerRegistry->getManager()->persist($deal);
        $this->managerRegistry->getManager()->flush();

           return $this->json([
               'status' => 'success',
                'message' => 'Affaire supprimé'
           ], 200, [], ['groups' => 'deal:edit']);
       }
       return $this->json([
           'status' => 'error',
           'message' => 'Impossible de supprimer une affaire'
       ], 500, [], ['groups' => 'deal:edit']);
    }    
}

