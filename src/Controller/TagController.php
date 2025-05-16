<?php

namespace App\Controller;

use App\Entity\Tag;
use App\Entity\Deal;
use App\Entity\Company;
use App\Entity\Contact;
use App\Managers\TagManager;
use App\Repository\TagRepository;
use App\Repository\DealRepository;
use App\Repository\CompanyRepository;
use App\Repository\ContactRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/tag', name: 'app_tag_')]
final class TagController extends AbstractController
{
    private TagManager $tagManager;

    public function __construct(
        TagManager $tagManager,
        private TagRepository $tagRepository,
        private DealRepository $dealRepository,
        private CompanyRepository $companyRepository,
        private ContactRepository $contactRepository
    ) {
        $this->tagManager = $tagManager;
    }

    #[Route('/list', name: 'list', methods: ['GET'], options: ['description' => 'Liste tous les tags'])]
    public function listTags(): JsonResponse
    {
        $tags = $this->tagRepository->findAll();
        return $this->json($tags, 200, [], ['groups' => 'tag:list']);
    }

    #[Route('/create', name: 'create', methods: ['POST'], options: ['description' => 'Crée un nouveau tag'])]
    public function createTag(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (empty($data)) {
            return $this->json(['status' => 'error', 'message' => 'Invalid data'], 400);
        }
        $tag = $this->tagManager->createFromArray($data);
        return $this->json(['status' => 'success', 'tag' => $tag], 201, [], ['groups' => 'tag:list']);
    }

    #[Route('/assign', name: 'assign', methods: ['POST', 'PATCH'], options: ['description' => 'Assigne un tag à une entité'])]
    public function assignTag(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (empty($data) || !isset($data['tag_id'], $data['entity_type'], $data['entity_id'])) {
            return $this->json(['status' => 'error', 'message' => 'Invalid data'], 400);
        }
        $tag = $this->tagRepository->find($data['tag_id']);
        if (!$tag instanceof Tag) {
            return $this->json(['status' => 'error', 'message' => 'Tag not found'], 404);
        }
        $entity = null;
        switch ($data['entity_type']) {
            case 'deal':
                $entity = $this->dealRepository->find($data['entity_id']);
                break;
            case 'company':
                $entity = $this->companyRepository->find($data['entity_id']);
                break;
            case 'contact':
                $entity = $this->contactRepository->find($data['entity_id']);
                break;
            default:
                return $this->json(['status' => 'error', 'message' => 'Invalid entity type'], 400);
        }
        if (!$entity) {
            return $this->json(['status' => 'error', 'message' => 'Entity not found'], 404);
        }
        $this->tagManager->assignTag($tag, $entity);
        return $this->json(['status' => 'success', 'message' => 'Tag assigned'], 200);
    }
}
