<?php

namespace App\Managers;

use App\Entity\Asset;
use App\Entity\Company;
use App\Entity\Contact;
use App\Entity\Deal;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class FileManager
{
    private EntityManagerInterface $em;
    private string $uploadDirectory;

    // Configuration de sécurité
    private const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB

    private const ALLOWED_MIME_TYPES = [
        // Images
        'image/jpeg' => 'jpg',
        'image/jpg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        'image/svg+xml' => 'svg',

        // Documents
        'application/pdf' => 'pdf',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.ms-excel' => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
        'text/csv' => 'csv',
        'text/plain' => 'txt',

        // Archives
        'application/zip' => 'zip',
        'application/x-rar-compressed' => 'rar',
    ];

    private const DANGEROUS_EXTENSIONS = [
        'php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'phps', 'pht',
        'exe', 'bat', 'cmd', 'com', 'pif', 'scr',
        'js', 'jar', 'vbs', 'wsf', 'sh', 'bash',
    ];

    public function __construct(
        EntityManagerInterface $em,
        ParameterBagInterface $params
    ) {
        $this->em = $em;
        // Récupérer le répertoire d'upload depuis les paramètres ou utiliser un défaut
        $this->uploadDirectory = $params->get('kernel.project_dir') . '/var/uploads';

        // Créer le répertoire s'il n'existe pas
        if (!is_dir($this->uploadDirectory)) {
            mkdir($this->uploadDirectory, 0755, true);
        }
    }

    /**
     * Upload et crée un asset à partir d'un fichier uploadé
     *
     * @param UploadedFile $file
     * @param array $data Données additionnelles (contact, company, deal)
     * @return Asset
     * @throws \RuntimeException Si validation échoue
     */
    public function uploadFile(UploadedFile $file, array $data = []): Asset
    {
        // Validation du fichier
        $this->validateFile($file);

        // Générer un nom de fichier sécurisé
        $secureFilename = $this->generateSecureFilename($file);

        // Déterminer le type de fichier
        $fileType = $this->determineFileType($file);

        // Déplacer le fichier dans le répertoire d'upload
        $file->move($this->uploadDirectory, $secureFilename);

        // Créer l'entité Asset
        $asset = new Asset();
        $asset->setSrc('/uploads/' . $secureFilename); // URL relative
        $asset->setName($file->getClientOriginalName());
        $asset->setType($fileType);

        // Associer aux entités si précisé
        $this->associateToEntities($asset, $data);

        $this->em->persist($asset);
        $this->em->flush();

        return $asset;
    }

    /**
     * Crée un fichier à partir d'un tableau de données (pour compatibilité)
     * ⚠️ ATTENTION: Cette méthode ne devrait pas être utilisée pour les uploads
     */
    public function createFromArray(array $data): Asset
    {
        $asset = new Asset();
        $this->hydrate($asset, $data, create: true);
        $this->em->persist($asset);
        $this->em->flush();
        return $asset;
    }

    /**
     * Met à jour un fichier existant à partir d'un tableau de données
     */
    public function updateFromArray(Asset $asset, array $data): Asset
    {
        $this->hydrate($asset, $data);
        $this->em->flush();
        return $asset;
    }

    /**
     * Supprime un asset ET son fichier physique
     */
    public function delete(Asset $asset): void
    {
        if ($asset instanceof Asset) {
            // Supprimer le fichier physique
            $this->deletePhysicalFile($asset);

            // Supprimer l'entité de la base de données
            $this->em->remove($asset);
            $this->em->flush();
        }
    }

    /**
     * Valide un fichier uploadé
     *
     * @throws \RuntimeException
     */
    private function validateFile(UploadedFile $file): void
    {
        // Vérifier si le fichier a été uploadé sans erreur
        if (!$file->isValid()) {
            throw new \RuntimeException('Erreur lors de l\'upload du fichier');
        }

        // Vérifier la taille
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw new \RuntimeException(sprintf(
                'Le fichier est trop volumineux (%d octets). Maximum autorisé: %d octets',
                $file->getSize(),
                self::MAX_FILE_SIZE
            ));
        }

        // Vérifier le type MIME
        $mimeType = $file->getMimeType();
        if (!array_key_exists($mimeType, self::ALLOWED_MIME_TYPES)) {
            throw new \RuntimeException(sprintf(
                'Type de fichier non autorisé: %s',
                $mimeType
            ));
        }

        // Vérifier l'extension
        $extension = strtolower($file->getClientOriginalExtension());
        if (in_array($extension, self::DANGEROUS_EXTENSIONS)) {
            throw new \RuntimeException(sprintf(
                'Extension de fichier dangereuse: %s',
                $extension
            ));
        }

        // Vérifier que l'extension correspond au MIME type
        $expectedExtension = self::ALLOWED_MIME_TYPES[$mimeType];
        if ($extension !== $expectedExtension && $extension !== '') {
            throw new \RuntimeException(sprintf(
                'Extension (%s) ne correspond pas au type MIME (%s)',
                $extension,
                $mimeType
            ));
        }
    }

    /**
     * Génère un nom de fichier sécurisé et unique
     */
    private function generateSecureFilename(UploadedFile $file): string
    {
        // Générer un nom unique
        $uniqueId = uniqid('', true);

        // Récupérer l'extension
        $extension = $file->guessExtension();

        // Format: timestamp_uniqueid.extension
        return sprintf(
            '%s_%s.%s',
            date('Y-m-d_His'),
            $uniqueId,
            $extension
        );
    }

    /**
     * Détermine le type de fichier basé sur le MIME type
     */
    private function determineFileType(UploadedFile $file): string
    {
        $mimeType = $file->getMimeType();

        if (str_starts_with($mimeType, 'image/')) {
            return Asset::TYPE_IMAGE;
        }

        if ($mimeType === 'application/pdf') {
            return Asset::TYPE_PDF;
        }

        if (in_array($mimeType, [
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ])) {
            return Asset::TYPE_DOC;
        }

        if (in_array($mimeType, [
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/csv'
        ])) {
            return Asset::TYPE_XLS;
        }

        return Asset::TYPE_OTHER;
    }

    /**
     * Associe l'asset aux entités liées
     */
    private function associateToEntities(Asset $asset, array $data): void
    {
        if (isset($data['company'])) {
            $company = $this->em->find(Company::class, $data['company']);
            if ($company instanceof Company) {
                $asset->setCompany($company);
            }
        }

        if (isset($data['contact'])) {
            $contact = $this->em->find(Contact::class, $data['contact']);
            if ($contact instanceof Contact) {
                $asset->setContact($contact);
            }
        }

        if (isset($data['deal'])) {
            $deal = $this->em->find(Deal::class, $data['deal']);
            if ($deal instanceof Deal) {
                $asset->setDeal($deal);
            }
        }
    }

    /**
     * Supprime le fichier physique associé à un asset
     */
    private function deletePhysicalFile(Asset $asset): void
    {
        $src = $asset->getSrc();
        if (!$src) {
            return;
        }

        // Convertir l'URL relative en chemin absolu
        // Ex: /uploads/file.jpg → /var/www/project/var/uploads/file.jpg
        $filename = basename($src);
        $filePath = $this->uploadDirectory . '/' . $filename;

        // Supprimer le fichier s'il existe
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    /**
     * Hydrate un asset (méthode legacy, ne devrait pas être utilisée pour uploads)
     */
    private function hydrate(Asset $asset, array $data, bool $create = false): void
    {
        // ⚠️ ATTENTION: Validation basique, cette méthode ne devrait pas accepter
        // de chemins non sécurisés directement
        if (isset($data['src'])) {
            // Vérifier path traversal
            $src = $data['src'];
            if (str_contains($src, '..') || str_contains($src, '//')) {
                throw new \RuntimeException('Chemin de fichier invalide (path traversal détecté)');
            }
            $asset->setSrc($src);
        }

        if (isset($data['name'])) {
            // Sanitizer le nom
            $name = preg_replace('/[^a-zA-Z0-9._-]/', '_', $data['name']);
            $asset->setName($name);
        }

        // Gestion du type de fichier avec validation sur les constantes
        if (isset($data['type'])) {
            $validTypes = [
                Asset::TYPE_IMAGE,
                Asset::TYPE_PDF,
                Asset::TYPE_DOC,
                Asset::TYPE_XLS,
                Asset::TYPE_OTHER,
            ];
            $asset->setType(in_array($data['type'], $validTypes, true) ? $data['type'] : Asset::TYPE_OTHER);
        } else {
            $asset->setType(Asset::TYPE_OTHER);
        }

        if ($create) {
            $this->associateToEntities($asset, $data);
        }
    }

    /**
     * Récupère la taille maximale autorisée
     */
    public function getMaxFileSize(): int
    {
        return self::MAX_FILE_SIZE;
    }

    /**
     * Récupère la liste des types MIME autorisés
     */
    public function getAllowedMimeTypes(): array
    {
        return array_keys(self::ALLOWED_MIME_TYPES);
    }
}
