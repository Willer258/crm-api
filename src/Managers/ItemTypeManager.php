<?php

namespace App\Managers;

use App\Entity\ItemType;
use Doctrine\Persistence\ManagerRegistry;
use Exception;

class ItemTypeManager extends Manager
{
    public function __construct(
        private ManagerRegistry $registry, 
    ) {
      
    }

    public function edit(array $data): ?ItemType
    {
        $itemType = null;
        $entityManager = $this->registry->getManager();
        $itemTypeRepository = $entityManager->getRepository(ItemType::class);

        if (isset($data['id'])) {
            $itemType = $itemTypeRepository->findOneBy(['id' => $data['id']]);
        }

        if (!($itemType instanceof ItemType)) {
            $itemType = new ItemType();
        }

        // Ajoutez ici les champs spécifiques à ItemType
        if (isset($data['code'])) {
            $itemType->setCode($data['code']);
        }

        if (isset($data['description'])) {
            $itemType->setDescription($data['description']);
        }

        $entityManager->persist($itemType);
        $entityManager->flush();

        return $itemType;
    }

    public function delete($id)
    {
        $itemType = $this->registry->getManager()->getRepository(ItemType::class)->findOneBy(['id' => $id]);
        
        if ($itemType instanceof ItemType) {
            // Ajoutez des vérifications supplémentaires si nécessaire
            $this->registry->getManager()->remove($itemType);
            $this->registry->getManager()->flush();
            return true;
        } else {
            throw new Exception('Type d\'élément introuvable');
        }
    }

    public function getGroups(): array
    {
        return ['itemType:list'];
    }
}
