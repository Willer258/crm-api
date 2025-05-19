<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Entity\Company;
use App\Entity\PropertyModel;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ExportController extends AbstractController
{
    #[Route('/export-full', name: 'export_full', methods: ['GET'])]
    public function exportFull(ManagerRegistry $registry): Response
    {
        $propertyModelRepo = $registry->getRepository(PropertyModel::class);
        $contactRepo = $registry->getRepository(Contact::class);
        $companyRepo = $registry->getRepository(Company::class);

        // 1. PropertyModels par type
        $propertyModels = $propertyModelRepo->findAll();
        $contactPropertyModels = array_filter($propertyModels, fn($pm) => $pm->getItemType() && $pm->getItemType()->getCode() === 'contact');
        $companyPropertyModels = array_filter($propertyModels, fn($pm) => $pm->getItemType() && $pm->getItemType()->getCode() === 'company');

        // 2. Header pour contacts
        $contactHeader = ['item_type', 'email', 'number', 'tags'];
        foreach ($contactPropertyModels as $pm) {
            $contactHeader[] = $pm->getLabel();
        }
        // 3. Header pour companies
        $companyHeader = ['item_type', 'email', 'number', 'tags'];
        foreach ($companyPropertyModels as $pm) {
            $companyHeader[] = $pm->getLabel();
        }

        // 4. Générer les lignes pour contacts
        $contacts = $contactRepo->findAll();
        $contactRows = [];
        foreach ($contacts as $contact) {
            $row = [];
            $row[] = $contact->getItemType() ? $contact->getItemType()->getCode() : '';
            // Email (on prend le premier mail, sinon vide)
            $row[] = ($contact->getMails()->first()) ? $contact->getMails()->first()->getEmail() : '';
            // Number (si tu as une entité Phone, à adapter, ici on met vide)
            $row[] = '';
            // Tags
            $row[] = implode(';', array_map(fn($tag) => $tag->getLabel(), $contact->getTags()->toArray()));
            // Propriétés dynamiques
            $propertyMap = [];
            foreach ($contact->getProperties() as $prop) {
                $pm = $prop->getPropertyModel();
                if ($pm) {
                    $propertyMap[$pm->getLabel()] = $prop->getValue();
                }
            }
            foreach ($contactPropertyModels as $pm) {
                $row[] = $propertyMap[$pm->getLabel()] ?? '';
            }
            $contactRows[] = $row;
        }

        // 5. Générer les lignes pour companies
        $companies = $companyRepo->findAll();
        $companyRows = [];
        foreach ($companies as $company) {
            $row = [];
            $row[] = $company->getItemType() ? $company->getItemType()->getCode() : '';
            // Email (on prend le premier mail, sinon vide)
            $row[] = ($company->getMails()->first()) ? $company->getMails()->first()->getEmail() : '';
            // Number (à adapter si tu as une entité Phone)
            $row[] = '';
            // Tags
            $row[] = implode(';', array_map(fn($tag) => $tag->getLabel(), $company->getTags()->toArray()));
            // Propriétés dynamiques
            $propertyMap = [];
            foreach ($company->getProperties() as $prop) {
                $pm = $prop->getPropertyModel();
                if ($pm) {
                    $propertyMap[$pm->getLabel()] = $prop->getValue();
                }
            }
            foreach ($companyPropertyModels as $pm) {
                $row[] = $propertyMap[$pm->getLabel()] ?? '';
            }
            $companyRows[] = $row;
        }

        // 6. Générer les CSV et le ZIP
        $contactCsv = $this->arrayToCsv($contactHeader, $contactRows);
        $companyCsv = $this->arrayToCsv($companyHeader, $companyRows);
        $zip = new \ZipArchive();
        $tmpFile = tempnam(sys_get_temp_dir(), 'export_zip');
        $zip->open($tmpFile, \ZipArchive::CREATE);
        $zip->addFromString('contacts.csv', $contactCsv);
        $zip->addFromString('companies.csv', $companyCsv);
        $zip->close();
        $zipContent = file_get_contents($tmpFile);
        unlink($tmpFile);
        return new Response(
            $zipContent,
            200,
            [
                'Content-Type' => 'application/zip',
                'Content-Disposition' => 'attachment; filename="export_crm.zip"',
            ]
        );
    }

    private function arrayToCsv(array $header, array $rows): string
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, $header);
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);
        return $content;
    }
}
