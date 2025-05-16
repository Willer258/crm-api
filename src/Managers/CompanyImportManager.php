<?php

namespace App\Managers;

use App\Entity\Company;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Entity\PropertyModel;
use App\Entity\Property;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CompanyImportManager
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Importe des entreprises à partir d'un fichier CSV (UTF-8, séparateur virgule)
     * Retourne un tableau avec le nombre de succès et d'erreurs
     */
    public function importFromFile(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $success = 0;
        $errors = 0;
        $propertyModels = [];
        $propertyModelRepo = $this->em->getRepository(PropertyModel::class);
        $itemTypeRepo = $this->em->getRepository(\App\Entity\ItemType::class);
        $itemTypeCompany = $itemTypeRepo->findOneBy(['code' => 'company']);
        if (!$itemTypeCompany) {
            return ['status' => 'error', 'message' => "ItemType 'company' introuvable dans la base de données."];
        }
        $rows = [];
        $headers = [];
        if ($extension === 'xlsx') {
            try {
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
                        $rows[] = $rowData;
                    }
                }
            } catch (\Exception $e) {
                return ['status' => 'error', 'message' => 'Erreur lecture Excel: ' . $e->getMessage()];
            }
        } else {
            // Par défaut : CSV
            $handle = fopen($file->getPathname(), 'r');
            if (!$handle) {
                return ['status' => 'error', 'message' => 'Impossible d\'ouvrir le fichier'];
            }
            $headers = fgetcsv($handle, 0, ',');
            if (!$headers) {
                return ['status' => 'error', 'message' => 'Aucune entête trouvée'];
            }
            while (($row = fgetcsv($handle, 0, ',')) !== false) {
                $rows[] = $row;
            }
            fclose($handle);
        }
        // Prépare les PropertyModel pour chaque colonne
        foreach ($headers as $header) {
            $label = trim($header);
            $propertyModel = $propertyModelRepo->findOneBy(['label' => $label, 'itemType' => $itemTypeCompany]);
            if (!$propertyModel) {
                $propertyModel = new PropertyModel();
                $propertyModel->setLabel($label);
                $propertyModel->setType(PropertyModel::TYPE_TEXT);
                $propertyModel->setIdentifier(false);
                $propertyModel->setItemType($itemTypeCompany);
                $this->em->persist($propertyModel);
                $this->em->flush();
            }
            $propertyModels[$label] = $propertyModel;
        }
        // Import des données
        foreach ($rows as $row) {
            $data = array_combine($headers, $row);
            if (!$data) {
                $errors++;
                continue;
            }
            $company = new Company();
            if (method_exists($company, 'setSource')) {
                $company->setSource('import');
            }
            $this->em->persist($company);
            foreach ($data as $col => $value) {
                $propertyModel = $propertyModels[$col] ?? null;
                if ($propertyModel && $value !== null && $value !== '') {
                    $property = new Property();
                    $property->setCompany($company);
                    $property->setPropertyModel($propertyModel);
                    $property->setValue($value);
                    $this->em->persist($property);
                }
            }
            $success++;
        }
        $this->em->flush();
        return ['status' => 'success', 'imported' => $success, 'errors' => $errors];
    }
}
