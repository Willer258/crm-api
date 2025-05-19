<?php

namespace App\Controller;

use App\Entity\File;
use App\Managers\FileManager;
use App\Repository\FileRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/file', name: 'app_file_')]
final class FileController extends AbstractController
{
    private FileManager $fileManager;

    public function __construct(FileManager $fileManager, private FileRepository $fileRepository)
    {
        $this->fileManager = $fileManager;
    }


    #[Route('/list', name: 'list', methods: ['GET'])]
    public function listFiles(): JsonResponse
    {
        $files = $this->fileRepository->findAll();
        return $this->json($files, 200, [], ['groups' => 'file:list']);
    }

    #[Route('/edit', name: 'edit', methods: ['POST'])]
    public function editFile(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (empty($data)) {
            return $this->json(['status' => 'error', 'message' => 'Invalid data'], 400);
        }

        if (isset($data['id'])) {
            $file = $this->fileRepository->find($data['id']);
            if ($file instanceof File) {
                $file = $this->fileManager->updateFromArray($file, $data);
            }
        } else {
            $file = $this->fileManager->createFromArray($data);
        }

        if ($file instanceof File) {
            return $this->json(['status' => 'success', 'file' => $file], 200, [], ['groups' => 'file:edit']);
        }
        return $this->json(['status' => 'error', 'message' => 'Impossible de créer ou modifier un fichier'], 500);
    }

    #[Route('/delete/{id}', name: 'delete', methods: ['DELETE'])]
    public function deleteFile(int $id): JsonResponse
    {
        $file = $this->fileRepository->find($id);
        if ($file instanceof File) {
            $this->fileManager->delete($file);
        }
        return $this->json(['status' => 'success', 'message' => 'Fichier supprimé']);
    }
}

