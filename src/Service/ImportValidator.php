<?php

namespace App\Service;

use App\Entity\Contact;
use App\Entity\Company;

/**
 * Validateur de données pour l'import
 * Vérifie les doublons et propose des suggestions de fusion
 */
class ImportValidator
{
    public function __construct(
        private DuplicateDetector $duplicateDetector
    ) {}

    /**
     * Valide une ligne de données pour un contact avant import
     *
     * @param array $rowData Les données de la ligne à valider
     * @return array {
     *   'valid': bool,
     *   'action': string, // 'import', 'skip', 'merge', 'review'
     *   'duplicates': Contact[],
     *   'similar': array[],
     *   'suggestions': array[],
     *   'warnings': string[],
     *   'errors': string[]
     * }
     */
    public function validateContactImport(array $rowData): array
    {
        $result = [
            'valid' => true,
            'action' => 'import',
            'duplicates' => [],
            'similar' => [],
            'suggestions' => [],
            'warnings' => [],
            'errors' => []
        ];

        // Validation basique des données
        $basicValidation = $this->validateContactData($rowData);
        if (!$basicValidation['valid']) {
            $result['valid'] = false;
            $result['action'] = 'skip';
            $result['errors'] = $basicValidation['errors'];
            return $result;
        }

        // Détection de doublons
        $duplicateCheck = $this->duplicateDetector->detectContactDuplicates($rowData);

        // Doublons exacts trouvés
        if ($duplicateCheck['is_duplicate']) {
            $result['duplicates'] = $duplicateCheck['exact_duplicates'];
            $result['action'] = 'merge';
            $result['warnings'][] = count($duplicateCheck['exact_duplicates']) . ' doublon(s) exact(s) trouvé(s)';

            // Génère des suggestions de fusion
            foreach ($duplicateCheck['exact_duplicates'] as $duplicate) {
                $result['suggestions'][] = [
                    'type' => 'merge',
                    'target' => $duplicate,
                    'confidence' => 'high',
                    'message' => 'Fusionner avec le contact #' . $duplicate->getId()
                ];
            }
        }

        // Contacts similaires trouvés
        if ($duplicateCheck['has_similar']) {
            $result['similar'] = $duplicateCheck['similar_contacts'];

            if ($duplicateCheck['confidence'] === 'high') {
                $result['action'] = 'review';
                $result['warnings'][] = 'Contact(s) très similaire(s) trouvé(s) - révision recommandée';
            } elseif ($duplicateCheck['confidence'] === 'medium') {
                $result['warnings'][] = 'Contact(s) similaire(s) trouvé(s)';
            }

            // Génère des suggestions pour les contacts similaires
            foreach ($duplicateCheck['similar_contacts'] as $similar) {
                if ($similar['score'] > 0.8) {
                    $result['suggestions'][] = [
                        'type' => 'possible_duplicate',
                        'target' => $similar['contact'],
                        'confidence' => 'high',
                        'score' => $similar['score'],
                        'reasons' => $similar['reasons'],
                        'message' => sprintf(
                            'Probablement un doublon (%.0f%% similaire) - %s',
                            $similar['score'] * 100,
                            implode(', ', $similar['reasons'])
                        )
                    ];
                } elseif ($similar['score'] > 0.6) {
                    $result['suggestions'][] = [
                        'type' => 'similar',
                        'target' => $similar['contact'],
                        'confidence' => 'medium',
                        'score' => $similar['score'],
                        'reasons' => $similar['reasons'],
                        'message' => sprintf(
                            'Contact similaire (%.0f%% similaire) - %s',
                            $similar['score'] * 100,
                            implode(', ', $similar['reasons'])
                        )
                    ];
                }
            }
        }

        return $result;
    }

    /**
     * Valide une ligne de données pour une entreprise avant import
     *
     * @param array $rowData Les données de la ligne à valider
     * @return array
     */
    public function validateCompanyImport(array $rowData): array
    {
        $result = [
            'valid' => true,
            'action' => 'import',
            'duplicates' => [],
            'similar' => [],
            'suggestions' => [],
            'warnings' => [],
            'errors' => []
        ];

        // Validation basique des données
        $basicValidation = $this->validateCompanyData($rowData);
        if (!$basicValidation['valid']) {
            $result['valid'] = false;
            $result['action'] = 'skip';
            $result['errors'] = $basicValidation['errors'];
            return $result;
        }

        // Détection de doublons
        $duplicateCheck = $this->duplicateDetector->detectCompanyDuplicates($rowData);

        // Doublons exacts trouvés
        if ($duplicateCheck['is_duplicate']) {
            $result['duplicates'] = $duplicateCheck['exact_duplicates'];
            $result['action'] = 'merge';
            $result['warnings'][] = count($duplicateCheck['exact_duplicates']) . ' doublon(s) exact(s) trouvé(s)';

            foreach ($duplicateCheck['exact_duplicates'] as $duplicate) {
                $result['suggestions'][] = [
                    'type' => 'merge',
                    'target' => $duplicate,
                    'confidence' => 'high',
                    'message' => 'Fusionner avec l\'entreprise #' . $duplicate->getId()
                ];
            }
        }

        // Entreprises similaires trouvées
        if ($duplicateCheck['has_similar']) {
            $result['similar'] = $duplicateCheck['similar_companies'];

            if ($duplicateCheck['confidence'] === 'high') {
                $result['action'] = 'review';
                $result['warnings'][] = 'Entreprise(s) très similaire(s) trouvée(s) - révision recommandée';
            } elseif ($duplicateCheck['confidence'] === 'medium') {
                $result['warnings'][] = 'Entreprise(s) similaire(s) trouvée(s)';
            }

            foreach ($duplicateCheck['similar_companies'] as $similar) {
                if ($similar['score'] > 0.8) {
                    $result['suggestions'][] = [
                        'type' => 'possible_duplicate',
                        'target' => $similar['company'],
                        'confidence' => 'high',
                        'score' => $similar['score'],
                        'reasons' => $similar['reasons'],
                        'message' => sprintf(
                            'Probablement un doublon (%.0f%% similaire) - %s',
                            $similar['score'] * 100,
                            implode(', ', $similar['reasons'])
                        )
                    ];
                } elseif ($similar['score'] > 0.6) {
                    $result['suggestions'][] = [
                        'type' => 'similar',
                        'target' => $similar['company'],
                        'confidence' => 'medium',
                        'score' => $similar['score'],
                        'reasons' => $similar['reasons'],
                        'message' => sprintf(
                            'Entreprise similaire (%.0f%% similaire) - %s',
                            $similar['score'] * 100,
                            implode(', ', $similar['reasons'])
                        )
                    ];
                }
            }
        }

        return $result;
    }

    /**
     * Valide un lot complet de données pour l'import
     *
     * @param array $rows Tableau de lignes de données
     * @param string $entityType 'contact' ou 'company'
     * @return array {
     *   'total': int,
     *   'valid': int,
     *   'invalid': int,
     *   'duplicates': int,
     *   'to_review': int,
     *   'results': array[]
     * }
     */
    public function validateBatchImport(array $rows, string $entityType = 'contact'): array
    {
        $summary = [
            'total' => count($rows),
            'valid' => 0,
            'invalid' => 0,
            'duplicates' => 0,
            'to_review' => 0,
            'results' => []
        ];

        foreach ($rows as $index => $row) {
            if ($entityType === 'contact') {
                $validation = $this->validateContactImport($row);
            } else {
                $validation = $this->validateCompanyImport($row);
            }

            $validation['row_index'] = $index;
            $summary['results'][] = $validation;

            if (!$validation['valid']) {
                $summary['invalid']++;
            } elseif ($validation['action'] === 'merge') {
                $summary['duplicates']++;
            } elseif ($validation['action'] === 'review') {
                $summary['to_review']++;
            } else {
                $summary['valid']++;
            }
        }

        return $summary;
    }

    /**
     * Validation basique des données d'un contact
     */
    private function validateContactData(array $data): array
    {
        $errors = [];

        // Vérifie qu'il y a au moins un identifiant (nom, email ou téléphone)
        $hasIdentifier = false;
        foreach ($data as $key => $value) {
            if (!empty($value)) {
                $lowerKey = mb_strtolower($key);
                if (in_array($lowerKey, ['nom', 'name', 'email', 'mail', 'phone', 'telephone', 'prenom', 'firstname'])) {
                    $hasIdentifier = true;
                    break;
                }
            }
        }

        if (!$hasIdentifier) {
            $errors[] = 'Aucun identifiant valide (nom, email ou téléphone requis)';
        }

        // Valide les emails si présents
        foreach ($data as $key => $value) {
            if (!empty($value) && is_string($value)) {
                // Si la clé suggère un email
                $lowerKey = mb_strtolower($key);
                if (str_contains($lowerKey, 'email') || str_contains($lowerKey, 'mail')) {
                    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $errors[] = "Email invalide: {$value}";
                    }
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Validation basique des données d'une entreprise
     */
    private function validateCompanyData(array $data): array
    {
        $errors = [];

        // Vérifie qu'il y a au moins un nom d'entreprise
        $hasName = false;
        foreach ($data as $key => $value) {
            if (!empty($value)) {
                $lowerKey = mb_strtolower($key);
                if (in_array($lowerKey, ['nom', 'name', 'company', 'entreprise', 'societe', 'société'])) {
                    $hasName = true;
                    break;
                }
            }
        }

        if (!$hasName) {
            $errors[] = 'Nom d\'entreprise requis';
        }

        // Valide le SIRET si présent
        foreach ($data as $key => $value) {
            if (!empty($value)) {
                $lowerKey = mb_strtolower($key);
                if ($lowerKey === 'siret') {
                    $cleaned = preg_replace('/[^0-9]/', '', $value);
                    if (strlen($cleaned) !== 14) {
                        $errors[] = "SIRET invalide (14 chiffres requis): {$value}";
                    }
                } elseif ($lowerKey === 'siren') {
                    $cleaned = preg_replace('/[^0-9]/', '', $value);
                    if (strlen($cleaned) !== 9) {
                        $errors[] = "SIREN invalide (9 chiffres requis): {$value}";
                    }
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Génère un rapport de validation détaillé
     */
    public function generateValidationReport(array $validationResult): string
    {
        $report = [];

        if (!$validationResult['valid']) {
            $report[] = '❌ DONNÉES INVALIDES';
            $report[] = 'Erreurs:';
            foreach ($validationResult['errors'] as $error) {
                $report[] = "  - {$error}";
            }
            return implode("\n", $report);
        }

        switch ($validationResult['action']) {
            case 'import':
                $report[] = '✅ IMPORT AUTORISÉ';
                if (!empty($validationResult['warnings'])) {
                    $report[] = 'Avertissements:';
                    foreach ($validationResult['warnings'] as $warning) {
                        $report[] = "  ⚠️ {$warning}";
                    }
                }
                break;

            case 'merge':
                $report[] = '🔄 FUSION RECOMMANDÉE';
                $report[] = 'Doublons exacts trouvés: ' . count($validationResult['duplicates']);
                break;

            case 'review':
                $report[] = '👁️ RÉVISION REQUISE';
                $report[] = 'Contacts similaires trouvés: ' . count($validationResult['similar']);
                break;

            case 'skip':
                $report[] = '⏭️ IGNORER';
                break;
        }

        if (!empty($validationResult['suggestions'])) {
            $report[] = '';
            $report[] = 'Suggestions:';
            foreach ($validationResult['suggestions'] as $suggestion) {
                $confidence = match($suggestion['confidence']) {
                    'high' => '🔴',
                    'medium' => '🟡',
                    default => '🟢'
                };
                $report[] = "  {$confidence} {$suggestion['message']}";
            }
        }

        return implode("\n", $report);
    }
}
