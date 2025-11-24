<?php

namespace App\Controller;

use App\Service\ImportValidator;
use App\Service\DuplicateDetector;
use App\Repository\ContactRepository;
use App\Repository\CompanyRepository;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/import/validation', name: 'app_import_validation_')]
final class ImportValidationController extends AbstractController
{
    public function __construct(
        private ImportValidator $importValidator,
        private DuplicateDetector $duplicateDetector,
        private ContactRepository $contactRepository,
        private CompanyRepository $companyRepository
    ) {}

    /**
     * Valide un fichier d'import de contacts avant de l'importer
     * Retourne un rapport détaillé avec les doublons potentiels
     */
    #[Route('/contact/file', name: 'validate_contact_file', methods: ['POST'], options: ['description' => 'Valide un fichier d\'import de contacts'])]
    public function validateContactFile(Request $request): Response
    {
        $file = $request->files->get('file');
        if (!$file) {
            return $this->json([
                'status' => 'error',
                'message' => 'Aucun fichier envoyé'
            ], 400);
        }

        try {
            $rows = $this->parseImportFile($file);
            $validation = $this->importValidator->validateBatchImport($rows, 'contact');

            return $this->json([
                'status' => 'success',
                'summary' => [
                    'total' => $validation['total'],
                    'valid' => $validation['valid'],
                    'invalid' => $validation['invalid'],
                    'duplicates' => $validation['duplicates'],
                    'to_review' => $validation['to_review']
                ],
                'details' => $validation['results']
            ], 200, [], ['groups' => 'contact:list']);
        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => 'Erreur lors de la validation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Valide un fichier d'import d'entreprises avant de l'importer
     */
    #[Route('/company/file', name: 'validate_company_file', methods: ['POST'], options: ['description' => 'Valide un fichier d\'import d\'entreprises'])]
    public function validateCompanyFile(Request $request): Response
    {
        $file = $request->files->get('file');
        if (!$file) {
            return $this->json([
                'status' => 'error',
                'message' => 'Aucun fichier envoyé'
            ], 400);
        }

        try {
            $rows = $this->parseImportFile($file);
            $validation = $this->importValidator->validateBatchImport($rows, 'company');

            return $this->json([
                'status' => 'success',
                'summary' => [
                    'total' => $validation['total'],
                    'valid' => $validation['valid'],
                    'invalid' => $validation['invalid'],
                    'duplicates' => $validation['duplicates'],
                    'to_review' => $validation['to_review']
                ],
                'details' => $validation['results']
            ], 200, [], ['groups' => 'company:list']);
        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => 'Erreur lors de la validation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Valide une seule ligne de données pour un contact
     */
    #[Route('/contact/row', name: 'validate_contact_row', methods: ['POST'], options: ['description' => 'Valide les données d\'un contact'])]
    public function validateContactRow(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data)) {
            return $this->json([
                'status' => 'error',
                'message' => 'Données invalides'
            ], 400);
        }

        $validation = $this->importValidator->validateContactImport($data);

        return $this->json([
            'status' => 'success',
            'validation' => $validation,
            'report' => $this->importValidator->generateValidationReport($validation)
        ], 200, [], ['groups' => 'contact:list']);
    }

    /**
     * Valide une seule ligne de données pour une entreprise
     */
    #[Route('/company/row', name: 'validate_company_row', methods: ['POST'], options: ['description' => 'Valide les données d\'une entreprise'])]
    public function validateCompanyRow(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data)) {
            return $this->json([
                'status' => 'error',
                'message' => 'Données invalides'
            ], 400);
        }

        $validation = $this->importValidator->validateCompanyImport($data);

        return $this->json([
            'status' => 'success',
            'validation' => $validation,
            'report' => $this->importValidator->generateValidationReport($validation)
        ], 200, [], ['groups' => 'company:list']);
    }

    /**
     * Recherche des doublons potentiels pour un contact
     */
    #[Route('/contact/duplicates', name: 'find_contact_duplicates', methods: ['POST'], options: ['description' => 'Recherche des doublons pour un contact'])]
    public function findContactDuplicates(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data)) {
            return $this->json([
                'status' => 'error',
                'message' => 'Données invalides'
            ], 400);
        }

        $result = $this->duplicateDetector->detectContactDuplicates($data);

        return $this->json([
            'status' => 'success',
            'is_duplicate' => $result['is_duplicate'],
            'has_similar' => $result['has_similar'],
            'confidence' => $result['confidence'],
            'exact_duplicates' => $result['exact_duplicates'],
            'similar_contacts' => array_map(function($similar) {
                return [
                    'contact' => $similar['contact'],
                    'score' => $similar['score'],
                    'score_percent' => round($similar['score'] * 100),
                    'reasons' => $similar['reasons']
                ];
            }, $result['similar_contacts'])
        ], 200, [], ['groups' => 'contact:list']);
    }

    /**
     * Recherche des doublons potentiels pour une entreprise
     */
    #[Route('/company/duplicates', name: 'find_company_duplicates', methods: ['POST'], options: ['description' => 'Recherche des doublons pour une entreprise'])]
    public function findCompanyDuplicates(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data)) {
            return $this->json([
                'status' => 'error',
                'message' => 'Données invalides'
            ], 400);
        }

        $result = $this->duplicateDetector->detectCompanyDuplicates($data);

        return $this->json([
            'status' => 'success',
            'is_duplicate' => $result['is_duplicate'],
            'has_similar' => $result['has_similar'],
            'confidence' => $result['confidence'],
            'exact_duplicates' => $result['exact_duplicates'],
            'similar_companies' => array_map(function($similar) {
                return [
                    'company' => $similar['company'],
                    'score' => $similar['score'],
                    'score_percent' => round($similar['score'] * 100),
                    'reasons' => $similar['reasons']
                ];
            }, $result['similar_companies'])
        ], 200, [], ['groups' => 'company:list']);
    }

    /**
     * Parse un fichier CSV ou Excel et retourne les lignes de données
     */
    private function parseImportFile($file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $rows = [];
        $headers = [];

        if ($extension === 'xlsx') {
            $spreadsheet = IOFactory::load($file->getPathname());
            $sheet = $spreadsheet->getActiveSheet();

            foreach ($sheet->getRowIterator() as $rowIndex => $row) {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);
                $rowData = [];

                foreach ($cellIterator as $cell) {
                    $rowData[] = $cell->getValue();
                }

                if ($rowIndex === 1) {
                    $headers = $rowData;
                } else {
                    if (!empty(array_filter($rowData))) { // Ignore les lignes vides
                        $rows[] = array_combine($headers, $rowData);
                    }
                }
            }
        } else {
            // CSV par défaut
            $handle = fopen($file->getPathname(), 'r');
            if (!$handle) {
                throw new \Exception('Impossible d\'ouvrir le fichier');
            }

            $headers = fgetcsv($handle, 0, ',');
            if (!$headers) {
                throw new \Exception('Aucune entête trouvée');
            }

            while (($row = fgetcsv($handle, 0, ',')) !== false) {
                if (!empty(array_filter($row))) { // Ignore les lignes vides
                    $rows[] = array_combine($headers, $row);
                }
            }

            fclose($handle);
        }

        return $rows;
    }
}
