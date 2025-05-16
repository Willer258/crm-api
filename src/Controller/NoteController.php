<?php

namespace App\Controller;

use App\Entity\Note;
use App\Managers\NoteManager;
use App\Repository\NoteRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/note', name: 'app_note_')]
final class NoteController extends AbstractController
{
    private NoteManager $noteManager;

    public function __construct(NoteManager $noteManager , private NoteRepository $noteRepository)
    {
        $this->noteManager = $noteManager;
    }



    #[Route('/edit', name: 'edit', methods: ['POST'])]
    public function editNote(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (empty($data)) {
            return $this->json(['status' => 'error', 'message' => 'Invalid data'], 400);
        }

        if (isset($data['id'])) {   
            $note = $this->noteRepository->find($data['id']);
            if ($note instanceof Note) {
                $note = $this->noteManager->updateFromArray($note, $data);
            }
        } else {
            $note = $this->noteManager->createFromArray($data);
        }

        if ($note instanceof Note) {
            return $this->json(['status' => 'success', 'note' => $note], 200, [], ['groups' => 'note:edit']);
        }
        return $this->json(['status' => 'error', 'message' => 'Impossible de créer ou modifier une note'], 500);
    }



    #[Route('/delete/{id}', name: 'delete', methods: ['DELETE'])]
    public function deleteNote(int $id): JsonResponse
    {
        $this->noteManager->delete($this->noteRepository->find($id));
        return $this->json(['status' => 'success', 'message' => 'Note supprimée']);
    }
}

