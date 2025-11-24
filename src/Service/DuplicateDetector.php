<?php

namespace App\Service;

use App\Entity\Contact;
use App\Entity\Company;
use App\Repository\ContactRepository;
use App\Repository\CompanyRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service de détection de doublons et de données similaires
 */
class DuplicateDetector
{
    public function __construct(
        private EntityManagerInterface $em,
        private ContactRepository $contactRepository,
        private CompanyRepository $companyRepository
    ) {}

    /**
     * Détecte les doublons potentiels pour un contact
     *
     * @param array $data Les données du contact à vérifier
     * @return array {
     *   'exact_duplicates': Contact[], // Doublons exacts (même email ou téléphone)
     *   'similar_contacts': array[], // Contacts similaires avec score de similarité
     *   'is_duplicate': bool,
     *   'confidence': string // 'high', 'medium', 'low'
     * }
     */
    public function detectContactDuplicates(array $data): array
    {
        $exactDuplicates = [];
        $similarContacts = [];

        // 1. Recherche de doublons exacts par email
        $emails = $this->extractEmails($data);
        foreach ($emails as $email) {
            $existing = $this->findContactsByEmail($email);
            foreach ($existing as $contact) {
                if (!in_array($contact, $exactDuplicates, true)) {
                    $exactDuplicates[] = $contact;
                }
            }
        }

        // 2. Recherche de doublons exacts par téléphone
        $phones = $this->extractPhones($data);
        foreach ($phones as $phone) {
            $existing = $this->findContactsByPhone($phone);
            foreach ($existing as $contact) {
                if (!in_array($contact, $exactDuplicates, true)) {
                    $exactDuplicates[] = $contact;
                }
            }
        }

        // 3. Recherche de contacts similaires (par nom)
        if (empty($exactDuplicates)) {
            $name = $this->extractName($data);
            if ($name) {
                $potentialMatches = $this->findContactsByName($name);
                foreach ($potentialMatches as $contact) {
                    $similarity = $this->calculateContactSimilarity($data, $contact);
                    if ($similarity['score'] > 0.5) { // Seuil de similarité > 50%
                        $similarContacts[] = [
                            'contact' => $contact,
                            'score' => $similarity['score'],
                            'reasons' => $similarity['reasons']
                        ];
                    }
                }
            }
        }

        // Tri des contacts similaires par score décroissant
        usort($similarContacts, fn($a, $b) => $b['score'] <=> $a['score']);

        // Détermination du niveau de confiance
        $confidence = 'low';
        if (count($exactDuplicates) > 0) {
            $confidence = 'high';
        } elseif (count($similarContacts) > 0 && $similarContacts[0]['score'] > 0.8) {
            $confidence = 'high';
        } elseif (count($similarContacts) > 0 && $similarContacts[0]['score'] > 0.6) {
            $confidence = 'medium';
        }

        return [
            'exact_duplicates' => $exactDuplicates,
            'similar_contacts' => $similarContacts,
            'is_duplicate' => count($exactDuplicates) > 0,
            'has_similar' => count($similarContacts) > 0,
            'confidence' => $confidence
        ];
    }

    /**
     * Détecte les doublons potentiels pour une entreprise
     *
     * @param array $data Les données de l'entreprise à vérifier
     * @return array {
     *   'exact_duplicates': Company[],
     *   'similar_companies': array[],
     *   'is_duplicate': bool,
     *   'confidence': string
     * }
     */
    public function detectCompanyDuplicates(array $data): array
    {
        $exactDuplicates = [];
        $similarCompanies = [];

        // 1. Recherche par nom exact (insensible à la casse)
        $name = $this->extractCompanyName($data);
        if ($name) {
            $existing = $this->findCompaniesByExactName($name);
            $exactDuplicates = array_merge($exactDuplicates, $existing);
        }

        // 2. Recherche par SIRET/SIREN si disponible
        $siret = $this->extractSiret($data);
        if ($siret) {
            $existing = $this->findCompaniesBySiret($siret);
            foreach ($existing as $company) {
                if (!in_array($company, $exactDuplicates, true)) {
                    $exactDuplicates[] = $company;
                }
            }
        }

        // 3. Recherche de sociétés similaires
        if (empty($exactDuplicates) && $name) {
            $potentialMatches = $this->findCompaniesByName($name);
            foreach ($potentialMatches as $company) {
                $similarity = $this->calculateCompanySimilarity($data, $company);
                if ($similarity['score'] > 0.5) {
                    $similarCompanies[] = [
                        'company' => $company,
                        'score' => $similarity['score'],
                        'reasons' => $similarity['reasons']
                    ];
                }
            }
        }

        usort($similarCompanies, fn($a, $b) => $b['score'] <=> $a['score']);

        $confidence = 'low';
        if (count($exactDuplicates) > 0) {
            $confidence = 'high';
        } elseif (count($similarCompanies) > 0 && $similarCompanies[0]['score'] > 0.8) {
            $confidence = 'high';
        } elseif (count($similarCompanies) > 0 && $similarCompanies[0]['score'] > 0.6) {
            $confidence = 'medium';
        }

        return [
            'exact_duplicates' => $exactDuplicates,
            'similar_companies' => $similarCompanies,
            'is_duplicate' => count($exactDuplicates) > 0,
            'has_similar' => count($similarCompanies) > 0,
            'confidence' => $confidence
        ];
    }

    /**
     * Calcule un score de similarité entre des données et un contact existant
     */
    private function calculateContactSimilarity(array $data, Contact $contact): array
    {
        $score = 0.0;
        $reasons = [];
        $weights = [
            'name' => 0.4,
            'email' => 0.3,
            'phone' => 0.2,
            'company' => 0.1
        ];

        // Comparaison du nom
        $dataName = $this->extractName($data);
        $contactName = $this->getContactName($contact);
        if ($dataName && $contactName) {
            $nameSimilarity = $this->stringSimilarity($dataName, $contactName);
            $score += $nameSimilarity * $weights['name'];
            if ($nameSimilarity > 0.8) {
                $reasons[] = "Nom très similaire ({$this->formatPercentage($nameSimilarity)})";
            } elseif ($nameSimilarity > 0.6) {
                $reasons[] = "Nom similaire ({$this->formatPercentage($nameSimilarity)})";
            }
        }

        // Comparaison des emails (partielle)
        $dataEmails = $this->extractEmails($data);
        $contactEmails = $this->getContactEmails($contact);
        foreach ($dataEmails as $email) {
            foreach ($contactEmails as $contactEmail) {
                $emailSimilarity = $this->stringSimilarity($email, $contactEmail);
                if ($emailSimilarity > 0.8) {
                    $score += $emailSimilarity * $weights['email'];
                    $reasons[] = "Email similaire: {$email}";
                    break 2;
                }
            }
        }

        // Comparaison des téléphones (partielle)
        $dataPhones = $this->extractPhones($data);
        $contactPhones = $this->getContactPhones($contact);
        foreach ($dataPhones as $phone) {
            $cleanPhone = $this->normalizePhone($phone);
            foreach ($contactPhones as $contactPhone) {
                $cleanContactPhone = $this->normalizePhone($contactPhone);
                if ($this->phoneMatches($cleanPhone, $cleanContactPhone)) {
                    $score += $weights['phone'];
                    $reasons[] = "Téléphone similaire: {$phone}";
                    break 2;
                }
            }
        }

        // Comparaison de l'entreprise
        $dataCompany = $this->extractCompanyName($data);
        $contactCompany = $contact->getCompany()?->getName() ?? '';
        if ($dataCompany && $contactCompany) {
            $companySimilarity = $this->stringSimilarity($dataCompany, $contactCompany);
            if ($companySimilarity > 0.8) {
                $score += $companySimilarity * $weights['company'];
                $reasons[] = "Même entreprise ({$contactCompany})";
            }
        }

        return [
            'score' => min($score, 1.0),
            'reasons' => $reasons
        ];
    }

    /**
     * Calcule un score de similarité entre des données et une entreprise existante
     */
    private function calculateCompanySimilarity(array $data, Company $company): array
    {
        $score = 0.0;
        $reasons = [];

        $dataName = $this->extractCompanyName($data);
        $companyName = $company->getName() ?? '';

        if ($dataName && $companyName) {
            $nameSimilarity = $this->stringSimilarity($dataName, $companyName);
            $score = $nameSimilarity;

            if ($nameSimilarity > 0.8) {
                $reasons[] = "Nom très similaire ({$this->formatPercentage($nameSimilarity)})";
            } elseif ($nameSimilarity > 0.6) {
                $reasons[] = "Nom similaire ({$this->formatPercentage($nameSimilarity)})";
            }

            // Vérifier les variations courantes (SA, SAS, SARL, etc.)
            if ($this->areCompanyVariations($dataName, $companyName)) {
                $score = max($score, 0.9);
                $reasons[] = "Variation du nom d'entreprise détectée";
            }
        }

        return [
            'score' => min($score, 1.0),
            'reasons' => $reasons
        ];
    }

    /**
     * Calcule la similarité entre deux chaînes (Levenshtein + similarité phonétique)
     */
    private function stringSimilarity(string $str1, string $str2): float
    {
        $str1 = mb_strtolower(trim($str1));
        $str2 = mb_strtolower(trim($str2));

        if ($str1 === $str2) {
            return 1.0;
        }

        // Utilise similar_text pour calculer la similarité
        similar_text($str1, $str2, $percent);

        return $percent / 100;
    }

    /**
     * Vérifie si deux noms d'entreprise sont des variations (ex: "Acme SA" vs "Acme SAS")
     */
    private function areCompanyVariations(string $name1, string $name2): bool
    {
        $legalForms = ['sa', 'sas', 'sarl', 'eurl', 'sasu', 'sci', 'snc', 'sca', 'ltd', 'inc', 'llc', 'gmbh'];

        $clean1 = mb_strtolower(trim($name1));
        $clean2 = mb_strtolower(trim($name2));

        // Retire les formes juridiques
        foreach ($legalForms as $form) {
            $clean1 = preg_replace('/\b' . $form . '\b/i', '', $clean1);
            $clean2 = preg_replace('/\b' . $form . '\b/i', '', $clean2);
        }

        $clean1 = trim(preg_replace('/\s+/', ' ', $clean1));
        $clean2 = trim(preg_replace('/\s+/', ' ', $clean2));

        return $clean1 === $clean2;
    }

    /**
     * Vérifie si deux téléphones correspondent
     */
    private function phoneMatches(string $phone1, string $phone2): bool
    {
        // Compare les 8 derniers chiffres
        $digits1 = substr($phone1, -8);
        $digits2 = substr($phone2, -8);

        return $digits1 === $digits2;
    }

    /**
     * Normalise un numéro de téléphone
     */
    private function normalizePhone(string $phone): string
    {
        return preg_replace('/[^0-9]/', '', $phone);
    }

    /**
     * Formate un pourcentage
     */
    private function formatPercentage(float $value): string
    {
        return round($value * 100) . '%';
    }

    // ========== MÉTHODES D'EXTRACTION ==========

    /**
     * Extrait tous les emails d'un tableau de données
     */
    private function extractEmails(array $data): array
    {
        $emails = [];
        foreach ($data as $key => $value) {
            if (is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $emails[] = strtolower(trim($value));
            }
        }
        return array_unique($emails);
    }

    /**
     * Extrait tous les numéros de téléphone d'un tableau de données
     */
    private function extractPhones(array $data): array
    {
        $phones = [];
        foreach ($data as $key => $value) {
            if (is_string($value) && $this->isPhoneNumber($value)) {
                $phones[] = $this->normalizePhone($value);
            }
        }
        return array_unique($phones);
    }

    /**
     * Détecte si une valeur est un numéro de téléphone
     */
    private function isPhoneNumber(string $value): bool
    {
        $cleaned = preg_replace('/[^0-9+]/', '', $value);
        return strlen($cleaned) >= 8 && preg_match('/^[+0]/', $cleaned);
    }

    /**
     * Extrait le nom d'un contact depuis les données
     */
    private function extractName(array $data): ?string
    {
        $nameFields = ['nom', 'name', 'prenom', 'firstname', 'lastname', 'full_name', 'fullname'];
        $nameParts = [];

        foreach ($data as $key => $value) {
            $lowerKey = mb_strtolower($key);
            if (in_array($lowerKey, $nameFields) && !empty($value)) {
                $nameParts[] = trim($value);
            }
        }

        return !empty($nameParts) ? implode(' ', $nameParts) : null;
    }

    /**
     * Extrait le nom d'une entreprise depuis les données
     */
    private function extractCompanyName(array $data): ?string
    {
        $companyFields = ['company', 'entreprise', 'societe', 'société', 'organization', 'organisation', 'nom_entreprise'];

        foreach ($data as $key => $value) {
            $lowerKey = mb_strtolower($key);
            if (in_array($lowerKey, $companyFields) && !empty($value)) {
                return trim($value);
            }
        }

        return null;
    }

    /**
     * Extrait le SIRET depuis les données
     */
    private function extractSiret(array $data): ?string
    {
        $siretFields = ['siret', 'siren', 'registration_number'];

        foreach ($data as $key => $value) {
            $lowerKey = mb_strtolower($key);
            if (in_array($lowerKey, $siretFields) && !empty($value)) {
                return preg_replace('/[^0-9]/', '', $value);
            }
        }

        return null;
    }

    // ========== MÉTHODES DE RECHERCHE ==========

    /**
     * Recherche des contacts par email
     */
    private function findContactsByEmail(string $email): array
    {
        $qb = $this->em->createQueryBuilder();
        return $qb->select('c')
            ->from(Contact::class, 'c')
            ->leftJoin('c.mails', 'm')
            ->where('LOWER(m.email) = :email')
            ->andWhere('c.removeAt IS NULL')
            ->setParameter('email', strtolower($email))
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche des contacts par téléphone
     */
    private function findContactsByPhone(string $phone): array
    {
        $cleanPhone = $this->normalizePhone($phone);
        $last8 = substr($cleanPhone, -8);

        $qb = $this->em->createQueryBuilder();
        return $qb->select('c')
            ->from(Contact::class, 'c')
            ->leftJoin('c.phones', 'p')
            ->where('p.number LIKE :phone')
            ->andWhere('c.removeAt IS NULL')
            ->setParameter('phone', '%' . $last8)
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche des contacts par nom (fuzzy)
     */
    private function findContactsByName(string $name): array
    {
        $words = explode(' ', $name);
        $qb = $this->em->createQueryBuilder();
        $qb->select('c')
            ->from(Contact::class, 'c')
            ->leftJoin('c.properties', 'p')
            ->leftJoin('p.propertyModel', 'm')
            ->where('c.removeAt IS NULL');

        $orConditions = [];
        foreach ($words as $i => $word) {
            if (strlen($word) > 2) {
                $orConditions[] = "p.value LIKE :word{$i}";
                $qb->setParameter("word{$i}", '%' . $word . '%');
            }
        }

        if (!empty($orConditions)) {
            $qb->andWhere(implode(' OR ', $orConditions));
        }

        return $qb->setMaxResults(20)
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche des entreprises par nom exact
     */
    private function findCompaniesByExactName(string $name): array
    {
        $qb = $this->em->createQueryBuilder();
        return $qb->select('c')
            ->from(Company::class, 'c')
            ->leftJoin('c.properties', 'p')
            ->leftJoin('p.propertyModel', 'm')
            ->where('LOWER(p.value) = :name')
            ->andWhere('m.label IN (:nameFields)')
            ->andWhere('c.removeAt IS NULL')
            ->setParameter('name', mb_strtolower($name))
            ->setParameter('nameFields', ['nom', 'name', 'raison sociale', 'company name'])
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche des entreprises par SIRET
     */
    private function findCompaniesBySiret(string $siret): array
    {
        $qb = $this->em->createQueryBuilder();
        return $qb->select('c')
            ->from(Company::class, 'c')
            ->leftJoin('c.properties', 'p')
            ->leftJoin('p.propertyModel', 'm')
            ->where('p.value = :siret')
            ->andWhere('m.label IN (:siretFields)')
            ->andWhere('c.removeAt IS NULL')
            ->setParameter('siret', $siret)
            ->setParameter('siretFields', ['siret', 'siren', 'registration'])
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche des entreprises par nom (fuzzy)
     */
    private function findCompaniesByName(string $name): array
    {
        $qb = $this->em->createQueryBuilder();
        return $qb->select('c')
            ->from(Company::class, 'c')
            ->leftJoin('c.properties', 'p')
            ->leftJoin('p.propertyModel', 'm')
            ->where('p.value LIKE :name')
            ->andWhere('m.label IN (:nameFields)')
            ->andWhere('c.removeAt IS NULL')
            ->setParameter('name', '%' . $name . '%')
            ->setParameter('nameFields', ['nom', 'name', 'raison sociale', 'company name'])
            ->setMaxResults(20)
            ->getQuery()
            ->getResult();
    }

    // ========== MÉTHODES D'ACCÈS AUX DONNÉES DE CONTACT ==========

    /**
     * Récupère le nom d'un contact
     */
    private function getContactName(Contact $contact): string
    {
        $nameParts = [];
        foreach ($contact->getProperties() as $property) {
            $label = mb_strtolower($property->getPropertyModel()?->getLabel() ?? '');
            if (in_array($label, ['nom', 'name', 'prenom', 'firstname', 'lastname'])) {
                $nameParts[] = $property->getValue();
            }
        }
        return implode(' ', $nameParts);
    }

    /**
     * Récupère les emails d'un contact
     */
    private function getContactEmails(Contact $contact): array
    {
        $emails = [];
        foreach ($contact->getMails() as $mail) {
            if ($mail->getEmail()) {
                $emails[] = strtolower($mail->getEmail());
            }
        }
        return $emails;
    }

    /**
     * Récupère les téléphones d'un contact
     */
    private function getContactPhones(Contact $contact): array
    {
        $phones = [];
        foreach ($contact->getPhones() as $phone) {
            if ($phone->getNumber()) {
                $phones[] = $phone->getNumber();
            }
        }
        return $phones;
    }
}
