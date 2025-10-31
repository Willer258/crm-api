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



    public function delete(int $id, bool $cascade = true): void
    {
        $pipeline = $this->registry->getManager()->getRepository(Pipeline::class)->find($id);

        if (!$pipeline instanceof Pipeline) {
            throw new Exception('Pipeline introuvable');
        }

        if ($pipeline->getRemoveAt() instanceof \DateTime) {
            throw new Exception('Pipeline déjà supprimé');
        }

        $pipeline->setRemoveAt(new \DateTime());
        $this->registry->getManager()->persist($pipeline);
        $this->registry->getManager()->flush();
    }

    public function restore(int $id, bool $cascade = true): void
    {
        $pipeline = $this->registry->getManager()->getRepository(Pipeline::class)->find($id);

        if (!$pipeline instanceof Pipeline) {
            throw new Exception('Pipeline introuvable');
        }

        if (!$pipeline->getRemoveAt() instanceof \DateTime) {
            throw new Exception('Pipeline non supprimé');
        }

        $pipeline->setRemoveAt(null);
        $pipeline->setRestoredAt(new \DateTime());
        $this->registry->getManager()->persist($pipeline);
        $this->registry->getManager()->flush();
    }

    public function getGroups(): array
    {
        return ['pipeline:list'];
    }
}
