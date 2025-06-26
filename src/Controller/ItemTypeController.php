<?php

namespace App\Controller;

use App\Entity\ItemType;
use App\Managers\ItemTypeManager;
use App\Repository\ItemTypeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/item/type', name: 'app_item_type_')]
final class ItemTypeController extends AbstractController
{
    #[Route('/list', name: 'list')]
    public function list(ItemTypeRepository $itemTypeRepository, Request $request): Response
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $page = $data['page'] ?? 1;
        $limit = $data['limit'] ?? 10;

        $itemTypes = $itemTypeRepository->findBy([], null, $limit, ($page - 1) * $limit);
        $total = $itemTypeRepository->count([]);

        return $this->json([
            'status' => 'success', 
            'data' => $itemTypes, 
            'page' => $page, 
            'limit' => $limit, 
            'total' => $total
        ], 200, [], ['groups' => 'itemType:list']);
    }

    #[Route('/{code}', name: 'show', methods: ['GET'], options: ['description' => 'Affiche un type d\'élément'])]
    public function show($code, ItemTypeRepository $itemTypeRepository): Response
    {
        $itemType = $itemTypeRepository->findOneBy(['code' => $code]);
        if (!$itemType) {
            return $this->json(['status' => 'error', 'message' => 'Type d\'élément non trouvé'], 404);
        }
        return $this->json(['status' => 'success', 'itemType' => $itemType], 200, [], ['groups' => 'itemType:show']);
    }
    

    #[Route('/edit', name: 'edit', options: ['description' => 'Créer ou modifier un type d\'élément'])]
    public function edit(ItemTypeManager $itemTypeManager, Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data)) {
            return $this->json(['status' => 'error', 'message' => 'Données invalides'], 400);
        }

        $itemType = $itemTypeManager->edit($data);

        if ($itemType instanceof ItemType) {
            return $this->json(['status' => 'success', 'itemType' => $itemType], 200, [], ['groups' => 'itemType:edit']);
        }

        return $this->json(['status' => 'error', 'message' => 'Impossible de créer ou modifier le type d\'élément'], 500);
    }

    #[Route('/delete/{id}', name: 'delete', methods: ['DELETE'], options: ['description' => 'Supprime un type d\'élément'])]
    public function delete(ItemTypeManager $itemTypeManager, int $id): Response
    {
        try {
            $itemTypeManager->delete($id);
            return $this->json(['status' => 'success', 'message' => 'Type d\'élément supprimé'], 200);
        } catch (\Exception $e) {
            return $this->json(['status' => 'error', 'message' => $e->getMessage()], 404);
        }
    }
}
