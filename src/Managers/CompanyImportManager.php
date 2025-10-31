<?php

namespace App\Managers;

use App\Entity\Company;
use App\Entity\Mail;
use App\Entity\PhoneNumber;
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
     * Détecte si une valeur est un email
     */
    private function isEmail(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Détecte si une valeur est un numéro de téléphone
     * Format acceptés: +33123456789, 0123456789, 01 23 45 67 89, +33 1 23 45 67 89, etc.
     */
    private function isPhoneNumber(string $value): bool
    {
        // Nettoie la valeur pour ne garder que les chiffres et le +
        $cleaned = preg_replace('/[^0-9+]/', '', $value);

        // Vérifie que la valeur contient au moins 8 chiffres (minimum pour un numéro valide)
        // et commence par + ou 0
        return strlen($cleaned) >= 8 && preg_match('/^[+0]/', $cleaned);
    }

    /**
     * Normalise un numéro de téléphone pour le stockage
     */
    private function normalizePhoneNumber(string $value): string
    {
        // Garde les chiffres et le + initial
        return preg_replace('/[^0-9+]/', '', $value);
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
            $company->setItemType($itemTypeCompany); // Définit l'ItemType
            $this->em->persist($company);

            foreach ($data as $col => $value) {
                $propertyModel = $propertyModels[$col] ?? null;
                if ($propertyModel && $value !== null && $value !== '') {
                    $trimmedValue = trim($value);

                    // Détection automatique des emails
                    if ($this->isEmail($trimmedValue)) {
                        $mail = new Mail();
                        $mail->setEmail($trimmedValue);
                        $mail->setCompany($company);

                        // Détermine le type selon le nom de la colonne
                        $lowerCol = strtolower($col);
                        if (strpos($lowerCol, 'contact') !== false) {
                            $mail->setType('contact');
                        } elseif (strpos($lowerCol, 'commercial') !== false || strpos($lowerCol, 'sales') !== false) {
                            $mail->setType('commercial');
                        } elseif (strpos($lowerCol, 'support') !== false) {
                            $mail->setType('support');
                        } else {
                            $mail->setType('général');
                        }

                        $this->em->persist($mail);
                        continue; // On ne crée pas de Property pour les emails
                    }

                    // Détection automatique des numéros de téléphone
                    if ($this->isPhoneNumber($trimmedValue)) {
                        $phoneNumber = new PhoneNumber();
                        $phoneNumber->setNumber($this->normalizePhoneNumber($trimmedValue));
                        $phoneNumber->setCompany($company);

                        // Détermine le type selon le nom de la colonne
                        $lowerCol = strtolower($col);
                        if (strpos($lowerCol, 'standard') !== false || strpos($lowerCol, 'principal') !== false) {
                            $phoneNumber->setType('standard');
                        } elseif (strpos($lowerCol, 'fax') !== false) {
                            $phoneNumber->setType('fax');
                        } elseif (strpos($lowerCol, 'commercial') !== false || strpos($lowerCol, 'sales') !== false) {
                            $phoneNumber->setType('commercial');
                        } elseif (strpos($lowerCol, 'support') !== false) {
                            $phoneNumber->setType('support');
                        } else {
                            $phoneNumber->setType('autre');
                        }

                        $this->em->persist($phoneNumber);
                        continue; // On ne crée pas de Property pour les téléphones
                    }

                    // Pour les autres valeurs, on crée une Property normale
                    $property = new Property();
                    $property->setCompany($company);
                    $property->setPropertyModel($propertyModel);
                    $property->setValue($trimmedValue);
                    $this->em->persist($property);
                }
            }
            $success++;
        }
        $this->em->flush();
        return ['status' => 'success', 'imported' => $success, 'errors' => $errors];
    }
}
