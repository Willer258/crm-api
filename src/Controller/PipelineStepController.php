<?php

namespace App\Controller;

use App\Entity\Pipeline;
use App\Entity\PipelineStep;
use App\Managers\PipelineStepManager;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;



#[Route('/pipeline/step', name: 'app_pipeline_step_')]
final class PipelineStepController extends AbstractController
{
    public function __construct(private ManagerRegistry $registry)
    {
    }
    #[Route('/list', name: 'list')]
    public function list(): Response
    {
        $pipelineSteps = $this->registry->getManager()->getRepository(PipelineStep::class)->findAll();
        return $this->json(['status' => 'success', 'pipelineSteps' => $pipelineSteps], 200, [], ['groups' => 'pipelineStep:list']);
    }

    #[Route('/list/{pipelineId}', name: 'list_by_pipeline')]
    public function listByPipeline(int $pipelineId): Response
    {
        $pipeline = $this->registry->getManager()->getRepository(Pipeline::class)->findOneBy(['id' => $pipelineId]);
        return $this->json(['status' => 'success', 'pipelineSteps' => $pipeline->getPipelineSteps()], 200, [], ['groups' => 'pipelineStep:list']);
    }

    #[Route('/edit', name: 'edit', methods: ['POST'], options: ['description' => 'Créer ou modifier une étape de pipeline'])]
    public function edit(Request $request , PipelineStepManager $pipelineStepManager): Response
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data)) {
            return $this->json(['status' => 'error', 'message' => 'Invalid data'], 400);
        }
        $pipelineStep = $pipelineStepManager->edit($data);

        if ($pipelineStep instanceof PipelineStep) {
            return $this->json(['status' => 'success', 'pipelineStep' => $pipelineStep], 200, [], ['groups' => 'pipelineStep:edit']);
        }

        return $this->json(['status' => 'error', 'message' => 'Impossible de creer une etape'], 500);
    }

    #[Route('/delete/{id}', name: 'delete', options: ['description' => 'Supprime une étape de pipeline'])]
    public function delete(PipelineStepManager $pipelineStepManager, int $id): Response
    {
        $pipelineStepManager->delete($id);

        return $this->json(['status' => 'success', 'message' => 'Etape supprimée'], 200);
    }
}
