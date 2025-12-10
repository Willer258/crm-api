<?php

namespace App\Service;

use App\Entity\Asset;
use App\Entity\Workspace;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mime\MimeTypes;

/**
 * Service pour la gestion des fichiers avec support du chunked upload
 * Basé sur l'implémentation de wiassur/backend/master
 */
class FileService
{
    private const CHUNK_SIZE = 1024 * 1024 * 2; // 2MB chunks

    private int $totalChunkReceived = 0;
    private ?int $missingChunk = null;
    private string $missingMessage = '';
    private ?Asset $file = null;
    private ?string $mimeType = null;
    private ?array $extension = null;

    private array $allowedExtensions = [
        'audio/mp3', 'audio/ogg', 'audio/webm', 'audio/ogg;codecs=opus', 'audio/webm;codecs=opus',
        'image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'image/svg+xml',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/msword', 'text/plain',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-excel'
    ];

    public function __construct(
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
        private WorkspaceResolver $workspaceResolver,
        private string $uploadFolder
    ) {
    }

    /**
     * Point d'entrée principal pour l'upload de fichiers
     */
    public function handle(Request $request, Workspace $workspace): array
    {
        $this->logger->debug('FileService::handle - Start chunked upload');

        // Validation des données de la requête
        $data = $this->validateRequestData($request);

        // Vérification du type de fichier
        if (!in_array($data['fileType'], $this->allowedExtensions, false)) {
            throw new \RuntimeException('Type de fichier ' . $data['fileType'] . ' non autorisé');
        }

        $tempFolder = $this->getWorkspaceTempFolder($workspace);
        $fileUniqFolder = $this->createUniqFolder($data, $tempFolder);
        $fileChunks = $this->getFileChunks($fileUniqFolder);
        $chunksNum = ceil($data['fileSize'] / self::CHUNK_SIZE);

        // Vérification des morceaux manquants
        if ($this->isChunkMissing($data, $fileUniqFolder, $fileChunks)) {
            if ((int)$data['loaded'] !== (int)$this->missingChunk) {
                return [
                    'success' => true,
                    'status' => 'missing',
                    'message' => $this->missingMessage,
                    'part' => $this->missingChunk,
                    'full' => $chunksNum,
                    'received' => (int)$data['loaded']
                ];
            }
        }

        // Sauvegarde du morceau actuel
        if ((int)$data['loaded'] < (int)$chunksNum) {
            $result = $this->saveChunk($data, $fileUniqFolder);
            if ($result !== true) {
                return [
                    'success' => false,
                    'message' => 'Problème lors de l\'écriture du fichier',
                    'error' => $result->getMessage(),
                ];
            }
        }

        // Fusion des morceaux si tout est reçu
        if ($this->isReadyToMerge($data, $fileUniqFolder, $chunksNum)) {
            $filename = $this->generateFile($data, $fileUniqFolder, $workspace, $tempFolder);

            return [
                'success' => true,
                'status' => 'finished',
                'id' => $this->file?->getUuid(),
                'filename' => $filename,
                'type' => $this->mimeType,
                'extension' => $this->extension,
                'link' => 'uploads/' . $workspace->getId() . '/' . $filename,
                'generated' => true,
            ];
        }

        return [
            'success' => true,
            'status' => 'loaded',
            'message' => 'continue',
            'received' => (int)$data['loaded'],
            'full' => $chunksNum
        ];
    }

    private function saveChunk(array $data, string $folder): bool|\Exception
    {
        $chunk = $this->decodeChunk($data['chunk']);
        try {
            file_put_contents(
                $folder . $data['uniqId'] . '.filePart' . $data['loaded'],
                $chunk,
                FILE_APPEND
            );
            return true;
        } catch (\Exception $e) {
            return $e;
        }
    }

    private function isReadyToMerge(array $data, string $fileUniqFolder, int $chunksNum): bool
    {
        $fileChunks = $this->getFileChunks($fileUniqFolder);

        if ($this->isChunkMissing($data, $fileUniqFolder, $fileChunks) ||
            (int)$chunksNum !== (int)count($fileChunks)) {
            return false;
        }

        return true;
    }

    private function generateFile(
        array $data,
        string $fileUniqFolder,
        Workspace $workspace,
        string $tempFolder
    ): ?string {
        if (!is_dir($fileUniqFolder)) {
            return null;
        }

        $files = $this->getFileChunks($fileUniqFolder);
        natsort($files);
        $files = array_values($files);

        $extension = $this->mime2ext($data['fileType']);

        // Création de l'entité Asset
        $this->file = new Asset();
        $this->file->setWorkspace($workspace);
        $this->file->setSrc($data['uniqId'] . '.' . $extension);
        $this->file->setRealName($data['fileName']);
        $this->file->setName($data['fileName']);
        $this->file->setType($data['fileType']);

        $this->em->persist($this->file);
        $this->em->flush();

        // Création du dossier de destination
        $destinationFolder = $this->uploadFolder . '/' . $workspace->getId() . '/';
        if (!is_dir($destinationFolder)) {
            mkdir($destinationFolder, 0755, true);
        }

        // Ajout du fichier .htaccess pour CORS
        if (!file_exists($destinationFolder . '.htaccess')) {
            file_put_contents($destinationFolder . '.htaccess', 'Header set Access-Control-Allow-Origin "*"');
        }

        $fileName = $this->file->getUuid() . '.' . $extension;

        // Fusion de tous les morceaux
        foreach ($files as $file) {
            file_put_contents(
                $destinationFolder . $fileName,
                file_get_contents($fileUniqFolder . $file),
                FILE_APPEND
            );
            unlink($fileUniqFolder . $file);
        }

        // Détection du type MIME réel
        $mimeTypes = new MimeTypes();
        $this->mimeType = $mimeTypes->guessMimeType($destinationFolder . $fileName);
        $this->extension = $mimeTypes->getExtensions($this->mimeType);

        chmod($destinationFolder . $fileName, 0755);
        rmdir($fileUniqFolder);

        // Mise à jour du fullPath
        $this->file->setFullPath($destinationFolder . $fileName);
        $this->em->flush();

        return $fileName;
    }

    private function isChunkMissing(array $data, string $fileUniqFolder, array $fileChunks): bool
    {
        natsort($fileChunks);
        $files = array_values($fileChunks);
        $currentChunkCount = count($files);

        if ($currentChunkCount > 1) {
            $chunksNum = ceil($data['fileSize'] / self::CHUNK_SIZE);
            $received = 0;

            for ($i = 0; $i < $currentChunkCount; $i++) {
                if (file_exists($fileUniqFolder . $data['uniqId'] . '.filePart' . $i)) {
                    ++$received;
                } else {
                    $this->missingMessage = 'missing ' . $data['uniqId'] . '.filePart' . $i;
                    $this->missingChunk = $i;
                    return true;
                }
            }

            if ($received === $chunksNum) {
                return false;
            }
        }

        return false;
    }

    private function getFileChunks(string $tempFolder): array
    {
        $files = [];

        if ($handle = opendir($tempFolder)) {
            while ($file = readdir($handle)) {
                if ($file !== '.' && $file !== '..') {
                    $files[] = $file;
                }
            }
            closedir($handle);
        }

        return $files;
    }

    private function createUniqFolder(array $data, string $baseFolder): string
    {
        $folderPath = $baseFolder . $data['uniqId'] . '/';

        if (!is_dir($folderPath)) {
            try {
                mkdir($folderPath, 0777, true);
            } catch (\Exception $e) {
                throw new \RuntimeException(
                    'Impossible de créer le dossier temporaire ' . $data['uniqId'] .
                    ' dans ' . $baseFolder . '. Vérifier les droits d\'accès.'
                );
            }
        }

        return $folderPath;
    }

    private function getWorkspaceTempFolder(Workspace $workspace): string
    {
        $tempFolder = $this->uploadFolder . '/temp/' . $workspace->getId() . '/';

        if (!is_dir($tempFolder)) {
            mkdir($tempFolder, 0777, true);
        }

        return $tempFolder;
    }

    private function validateRequestData(Request $request): array
    {
        $allowedParameters = [
            'loaded',       // Nombre de morceaux téléchargés
            'fileName',     // Nom de fichier original
            'fileSize',     // Taille totale du fichier
            'fileType',     // Type MIME
            'fileHash',     // Hash du fichier
            'chunk',        // Morceau de fichier
            'uniqId',       // Identifiant unique du fichier
        ];

        $data = [];

        // Essai de récupération depuis le corps JSON
        try {
            $content = json_decode($request->getContent(), true);
            if (is_array($content)) {
                foreach ($allowedParameters as $param) {
                    if (array_key_exists($param, $content)) {
                        $data[$param] = $content[$param];
                    }
                }
                return $data;
            }
        } catch (\Throwable $e) {
            // Si JSON échoue, on essaie les paramètres de requête
        }

        // Récupération depuis les paramètres de requête
        foreach ($allowedParameters as $param) {
            $value = $request->request->get($param);
            if ($value !== null) {
                $data[$param] = $value;
            }
        }

        return $data;
    }

    private function decodeChunk(string $data): string|false
    {
        if (str_contains($data, ';base64')) {
            $parts = explode(';base64,', $data);
            if (!is_array($parts) || !isset($parts[1])) {
                return false;
            }
            $decoded = base64_decode($parts[1]);
            if (!$decoded) {
                return false;
            }
            return $decoded;
        }

        return base64_decode($data);
    }

    private function mime2ext(string $mime): string
    {
        $mimeMap = [
            'image/jpeg' => 'jpeg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/svg+xml' => 'svg',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/msword' => 'doc',
            'text/plain' => 'txt',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'application/vnd.ms-excel' => 'xls',
            'audio/mp3' => 'mp3',
            'audio/mpeg' => 'mp3',
            'audio/ogg' => 'ogg',
            'audio/webm' => 'webm',
            'video/mp4' => 'mp4',
            'video/webm' => 'webm',
        ];

        return $mimeMap[$mime] ?? 'bin';
    }
}
