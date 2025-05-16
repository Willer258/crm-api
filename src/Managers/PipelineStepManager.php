<?php

namespace App\Managers;

use App\Entity\Pipeline;
use App\Entity\PipelineStep;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Exception;

class PipelineStepManager extends Manager
{


    public function __construct(private ManagerRegistry $registry, private PropertyManager $propertyManager, EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function edit($data): ?PipelineStep
    {
        $pipelineStep = null;
        $entityManager = $this->registry->getManager();
        $pipelineStepRepository = $entityManager->getRepository(PipelineStep::class);

        if (isset($data['id'])) {
            $pipelineStep = $pipelineStepRepository->findOneBy(['id' => $data['id']]);
        }


        if (!($pipelineStep instanceof PipelineStep)) {
            $pipelineStep = new PipelineStep();
        }
        if (isset($data['pipeline'])) {
            $pipeline = $this->registry->getManager()->getRepository(Pipeline::class)->findOneBy(['id' => $data['pipeline']]);
            if (!($pipeline instanceof Pipeline)) {
                throw new Exception('Pipeline introuvable');
            }
            $pipelineStep->setPipeline($pipeline);
        }
        $pipelineStep->setName($data['name']);


        if (isset($data['color'])) {
            $pipelineStep->setColor($data['color']);
        }

        if (isset($data['description'])) {
            $pipelineStep->setDescription($data['description']);
        }

      
        if (isset($data['successProbability'])) {
            $pipelineStep->setSuccessProbability($data['successProbability']);
        }

        if (isset($data['ranking'])) {
            $pipelineStep->setRanking($data['ranking']);
        }
      
        $entityManager->persist($pipelineStep);
        $entityManager->flush();

        return $pipelineStep;
    }



    public function delete($id)
    {
        $pipelineStep = $this->registry->getManager()->getRepository(PipelineStep::class)->findOneBy(['id' => $id]);
        if ($pipelineStep instanceof PipelineStep) {
            if ($pipelineStep->getDeals()->count() > 0) {
             throw new Exception('Impossible de supprimer une etape avec des affaires');
            } else {
                $this->registry->getManager()->remove($pipelineStep);
            }
            $this->registry->getManager()->flush();
            return true;
        } else {
            throw new Exception('Etape introuvable');
        }
    }

    public function getGroups(): array
    {
        return ['pipelineStep:list'];
    }
}
