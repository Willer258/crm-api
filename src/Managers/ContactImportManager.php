<?php

namespace App\Managers;

use App\Entity\Contact;
use App\Entity\Mail;
use App\Entity\PhoneNumber;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Entity\PropertyModel;
use App\Entity\Property;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ContactImportManager
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
     * Importe des contacts à partir d'un fichier CSV (UTF-8, séparateur virgule) ou Excel (.xlsx)
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
        $itemTypeContact = $itemTypeRepo->findOneBy(['code' => 'contact']);
        if (!$itemTypeContact) {
            return ['status' => 'error', 'message' => "ItemType 'contact' introuvable dans la base de données."];
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
            $propertyModel = $propertyModelRepo->findOneBy(['label' => $label, 'itemType' => $itemTypeContact]);
            if (!$propertyModel) {
                $propertyModel = new PropertyModel();
                $propertyModel->setLabel($label);
                $propertyModel->setType(PropertyModel::TYPE_TEXT);
                $propertyModel->setIdentifier(false);
                $propertyModel->setItemType($itemTypeContact);
                $this->em->persist($propertyModel);
                $this->em->flush(); // pour l'id
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
            $contact = new Contact();
            $contact->setSource('import');
            $this->em->persist($contact);

            foreach ($data as $col => $value) {
                $propertyModel = $propertyModels[$col] ?? null;
                if ($propertyModel && $value !== null && $value !== '') {
                    $trimmedValue = trim($value);

                    // Détection automatique des emails
                    if ($this->isEmail($trimmedValue)) {
                        $mail = new Mail();
                        $mail->setEmail($trimmedValue);
                        $mail->setContact($contact);

                        // Détermine le type selon le nom de la colonne
                        $lowerCol = strtolower($col);
                        if (strpos($lowerCol, 'professionnel') !== false || strpos($lowerCol, 'work') !== false) {
                            $mail->setType('professionnel');
                        } elseif (strpos($lowerCol, 'personnel') !== false || strpos($lowerCol, 'personal') !== false) {
                            $mail->setType('personnel');
                        } else {
                            $mail->setType('autre');
                        }

                        $this->em->persist($mail);
                        continue; // On ne crée pas de Property pour les emails
                    }

                    // Détection automatique des numéros de téléphone
                    if ($this->isPhoneNumber($trimmedValue)) {
                        $phoneNumber = new PhoneNumber();
                        $phoneNumber->setNumber($this->normalizePhoneNumber($trimmedValue));
                        $phoneNumber->setContact($contact);

                        // Détermine le type selon le nom de la colonne
                        $lowerCol = strtolower($col);
                        if (strpos($lowerCol, 'mobile') !== false || strpos($lowerCol, 'portable') !== false) {
                            $phoneNumber->setType('mobile');
                        } elseif (strpos($lowerCol, 'fixe') !== false || strpos($lowerCol, 'bureau') !== false || strpos($lowerCol, 'office') !== false) {
                            $phoneNumber->setType('fixe');
                        } elseif (strpos($lowerCol, 'fax') !== false) {
                            $phoneNumber->setType('fax');
                        } else {
                            $phoneNumber->setType('autre');
                        }

                        $this->em->persist($phoneNumber);
                        continue; // On ne crée pas de Property pour les téléphones
                    }

                    // Pour les autres valeurs, on crée une Property normale
                    $property = new Property();
                    $property->setContact($contact);
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
