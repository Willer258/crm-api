<?php

namespace App\Controller;

use App\Entity\Asset;
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


    #[Route('/list', name: 'list', methods: ['GET'], options: ['description' => 'Liste tous les fichiers'])]
    public function listFiles(): JsonResponse
    {
        $files = $this->fileRepository->findAll();
        return $this->json($files, 200, [], ['groups' => 'file:list']);
    }

    #[Route('/upload', name: 'upload', methods: ['POST'], options: ['description' => 'Upload un fichier de manière sécurisée'])]
    public function uploadFile(Request $request): JsonResponse
    {
        $uploadedFile = $request->files->get('file');

        if (!$uploadedFile) {
            return $this->json([
                'status' => 'error',
                'message' => 'Aucun fichier fourni'
            ], 400);
        }

        try {
            // Données additionnelles (contact, company, deal)
            $data = [
                'contact' => $request->request->get('contact'),
                'company' => $request->request->get('company'),
                'deal' => $request->request->get('deal'),
            ];

            $asset = $this->fileManager->uploadFile($uploadedFile, $data);

            return $this->json([
                'status' => 'success',
                'message' => 'Fichier uploadé avec succès',
                'file' => $asset
            ], 201, [], ['groups' => 'file:edit']);

        } catch (\RuntimeException $e) {
            return $this->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    #[Route('/config', name: 'config', methods: ['GET'], options: ['description' => 'Récupère la configuration d\'upload'])]
    public function getUploadConfig(): JsonResponse
    {
        return $this->json([
            'status' => 'success',
            'config' => [
                'maxFileSize' => $this->fileManager->getMaxFileSize(),
                'maxFileSizeMB' => round($this->fileManager->getMaxFileSize() / 1024 / 1024, 2),
                'allowedMimeTypes' => $this->fileManager->getAllowedMimeTypes(),
            ]
        ]);
    }

    #[Route('/edit', name: 'edit', methods: ['POST'], options: ['description' => '⚠️ DEPRECATED: Utiliser /upload pour les nouveaux fichiers'])]
    public function editFile(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (empty($data)) {
            return $this->json(['status' => 'error', 'message' => 'Invalid data'], 400);
        }

        if (isset($data['id'])) {
            $file = $this->fileRepository->find($data['id']);
            if ($file instanceof Asset) {
                $file = $this->fileManager->updateFromArray($file, $data);
            }
        } else {
            // ⚠️ Cette méthode n'est pas sécurisée pour les uploads
            // Utiliser /upload à la place
            $file = $this->fileManager->createFromArray($data);
        }

        if ($file instanceof Asset) {
            return $this->json(['status' => 'success', 'file' => $file], 200, [], ['groups' => 'file:edit']);
        }
        return $this->json(['status' => 'error', 'message' => 'Impossible de créer ou modifier un fichier'], 500);
    }

    #[Route('/delete/{id}', name: 'delete', methods: ['DELETE'])]
    public function deleteFile(int $id): JsonResponse
    {
        $file = $this->fileRepository->find($id);
        if ($file instanceof Asset) {
            $this->fileManager->delete($file);
        }
        return $this->json(['status' => 'success', 'message' => 'Fichier supprimé']);
    }
}

