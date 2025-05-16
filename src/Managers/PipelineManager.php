<?php

namespace App\Managers;

use App\Entity\Pipeline;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Exception;

class PipelineManager extends Manager
{


    public function __construct(private ManagerRegistry $registry, private PropertyManager $propertyManager, EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function edit($data): ?Pipeline
    {
        $pipeline = null;
        $entityManager = $this->registry->getManager();
        $pipelineRepository = $entityManager->getRepository(Pipeline::class);

        if (isset($data['id'])) {
            $pipeline = $pipelineRepository->findOneBy(['id' => $data['id']]);
        }

        if (!($pipeline instanceof Pipeline)) {
            $pipeline = new Pipeline();
        }

        $pipeline->setName($data['name']);
        if (isset($data['description'])) {
            $pipeline->setDescription($data['description']);
        }

        if (isset($data['roles'])) {
            $pipeline->setRoles($data['roles']);
        }

        $entityManager->persist($pipeline);
        $entityManager->flush();

        return $pipeline;
    }



    public function delete($id)
    {
        $pipeline = $this->registry->getManager()->getRepository(Pipeline::class)->findOneBy(['id' => $id]);
        if ($pipeline instanceof Pipeline) {
            if ($pipeline->getPipelineSteps()->count() > 0) {
                $pipeline->setRemoveAt(new \DateTime());
                $this->registry->getManager()->persist($pipeline);
            } else {
                $this->registry->getManager()->remove($pipeline);
            }
            $this->registry->getManager()->flush();
            return true;
        } else {
            throw new Exception('Pipeline introuvable');
        }
    }

    public function getGroups(): array
    {
        return ['pipeline:list'];
    }
}
