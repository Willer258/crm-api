<?php

namespace App\Managers;

use App\Entity\Contact;
use App\Entity\Mail;
use App\Entity\PhoneNumber;
use App\Service\ImportValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Entity\PropertyModel;
use App\Entity\Property;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ContactImportManager
{
    private EntityManagerInterface $em;
    private ?ImportValidator $importValidator;

    public function __construct(EntityManagerInterface $em, ?ImportValidator $importValidator = null)
    {
        $this->em = $em;
        $this->importValidator = $importValidator;
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
     *
     * @param UploadedFile $file Le fichier à importer
     * @param bool $skipDuplicates Si true, ignore les doublons détectés
     * @param bool $autoMerge Si true, fusionne automatiquement les doublons exacts
     */
    public function importFromFile(UploadedFile $file, bool $skipDuplicates = false, bool $autoMerge = false): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $success = 0;
        $errors = 0;
        $skipped = 0;
        $merged = 0;
        $propertyModels = [];
        $propertyModelRepo = $this->em->getRepository(PropertyModel::class);
        $itemTypeRepo = $this->em->getRepository(\App\Entity\ItemType::class);
        $itemTypeContact = $itemTypeRepo->findOneBy(['code' => 'contact']);
        if (!$itemTypeContact) {
            return ['status' => 'error', 'message' => "ItemType 'contact' introuvable dans la base de données."];
        }
        $rows = [];
        $headers = [];
        $validationWarnings = [];
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
        foreach ($rows as $rowIndex => $row) {
            $data = array_combine($headers, $row);
            if (!$data) {
                $errors++;
                continue;
            }

            // Validation avec détection de doublons si le validateur est disponible
            if ($this->importValidator) {
                $validation = $this->importValidator->validateContactImport($data);

                // Si la donnée est invalide, on la skip
                if (!$validation['valid']) {
                    $errors++;
                    $validationWarnings[] = [
                        'row' => $rowIndex + 2, // +2 car ligne 1 = headers, index commence à 0
                        'errors' => $validation['errors']
                    ];
                    continue;
                }

                // Si c'est un doublon exact
                if ($validation['action'] === 'merge') {
                    if ($skipDuplicates) {
                        $skipped++;
                        $validationWarnings[] = [
                            'row' => $rowIndex + 2,
                            'message' => 'Doublon ignoré',
                            'duplicates' => count($validation['duplicates'])
                        ];
                        continue;
                    } elseif ($autoMerge && !empty($validation['duplicates'])) {
                        // Auto-merge avec le premier doublon trouvé
                        $existingContact = $validation['duplicates'][0];
                        $this->updateContactFromImport($existingContact, $data, $propertyModels);
                        $merged++;
                        $validationWarnings[] = [
                            'row' => $rowIndex + 2,
                            'message' => 'Fusionné avec contact #' . $existingContact->getId()
                        ];
                        continue;
                    }
                }

                // Si révision requise mais on continue l'import quand même
                if ($validation['action'] === 'review' && !empty($validation['similar'])) {
                    $validationWarnings[] = [
                        'row' => $rowIndex + 2,
                        'message' => 'Contact similaire détecté',
                        'similar_count' => count($validation['similar']),
                        'confidence' => $validation['similar'][0]['score'] ?? 0
                    ];
                }
            }

            // Création du nouveau contact
            $contact = new Contact();
            $contact->setSource('import');
            $contact->setItemType($itemTypeContact);
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

        return [
            'status' => 'success',
            'imported' => $success,
            'errors' => $errors,
            'skipped' => $skipped,
            'merged' => $merged,
            'warnings' => $validationWarnings
        ];
    }

    /**
     * Met à jour un contact existant avec des données d'import
     */
    private function updateContactFromImport(Contact $contact, array $data, array $propertyModels): void
    {
        foreach ($data as $col => $value) {
            $propertyModel = $propertyModels[$col] ?? null;
            if ($propertyModel && $value !== null && $value !== '') {
                $trimmedValue = trim($value);

                // Gestion des emails
                if ($this->isEmail($trimmedValue)) {
                    // Vérifie si l'email existe déjà
                    $emailExists = false;
                    foreach ($contact->getMails() as $existingMail) {
                        if (strtolower($existingMail->getEmail()) === strtolower($trimmedValue)) {
                            $emailExists = true;
                            break;
                        }
                    }

                    if (!$emailExists) {
                        $mail = new Mail();
                        $mail->setEmail($trimmedValue);
                        $mail->setContact($contact);
                        $lowerCol = strtolower($col);
                        if (strpos($lowerCol, 'professionnel') !== false || strpos($lowerCol, 'work') !== false) {
                            $mail->setType('professionnel');
                        } elseif (strpos($lowerCol, 'personnel') !== false || strpos($lowerCol, 'personal') !== false) {
                            $mail->setType('personnel');
                        } else {
                            $mail->setType('autre');
                        }
                        $this->em->persist($mail);
                    }
                    continue;
                }

                // Gestion des téléphones
                if ($this->isPhoneNumber($trimmedValue)) {
                    $normalizedPhone = $this->normalizePhoneNumber($trimmedValue);
                    $phoneExists = false;
                    foreach ($contact->getPhones() as $existingPhone) {
                        if ($this->normalizePhoneNumber($existingPhone->getNumber()) === $normalizedPhone) {
                            $phoneExists = true;
                            break;
                        }
                    }

                    if (!$phoneExists) {
                        $phoneNumber = new PhoneNumber();
                        $phoneNumber->setNumber($normalizedPhone);
                        $phoneNumber->setContact($contact);
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
                    }
                    continue;
                }

                // Mise à jour ou création de propriétés
                $propertyExists = false;
                foreach ($contact->getProperties() as $existingProperty) {
                    if ($existingProperty->getPropertyModel() === $propertyModel) {
                        // Mise à jour de la valeur existante
                        $existingProperty->setValue($trimmedValue);
                        $propertyExists = true;
                        break;
                    }
                }

                if (!$propertyExists) {
                    $property = new Property();
                    $property->setContact($contact);
                    $property->setPropertyModel($propertyModel);
                    $property->setValue($trimmedValue);
                    $this->em->persist($property);
                }
            }
        }
    }
}
