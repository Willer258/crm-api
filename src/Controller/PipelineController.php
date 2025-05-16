<?php

namespace App\Controller;

use App\Entity\Pipeline;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Managers\PipelineManager;
use App\Repository\PipelineRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/pipeline', name: 'app_pipeline_')]
final class PipelineController extends AbstractController
{
   
    #[Route('/list', name: 'list', options: ['description' => 'Liste tous les pipelines'])]
    public function list(PipelineRepository $pipelineRepository): Response
    {
        $pipelines = $pipelineRepository->findAll();
        return $this->json(['status' => 'success', 'pipelines' => $pipelines], 200, [], ['groups' => 'pipeline:list']);
    }


    #[Route('/edit', name: 'edit', methods: ['POST', 'PUT'], options: ['description' => 'Créer ou modifier un pipeline'])]
    public function edit(PipelineManager $pipelineManager , Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        // dd($data);

        if (empty($data)) {
            return $this->json(['status' => 'error', 'message' => 'Invalid data'], 400);
        }
        $pipeline = $pipelineManager->edit($data);


        if ($pipeline instanceof Pipeline) {
            return $this->json(['status' => 'success', 'pipeline' => $pipeline], 200, [], ['groups' => 'pipeline:edit']);
        }

        return $this->json(['status' => 'error', 'message' => 'Impossible de creer une pipeline'], 500);
    }

    #[Route('/info/{id}', name: 'info', options: ['description' => 'Affiche les informations d\'un pipeline'])]
    public function info(PipelineRepository $pipelineRepository, int $id): Response
    {
        $pipeline = $pipelineRepository->findOneBy(['id' => $id]);
        return $this->json(['status' => 'success', 'pipeline' => $pipeline], 200, [], ['groups' => 'pipeline:info']);
    }
    

    #[Route('/delete/{id}', name: 'delete', options: ['description' => 'Supprime un pipeline'])]
    public function delete(PipelineManager $pipelineManager, int $id): Response
    {
        $pipelineManager->delete($id);

        return $this->json(['status' => 'success', 'message' => 'Pipeline supprimée'], 200);
        
    }
}
