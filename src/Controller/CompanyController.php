<?php

namespace App\Controller;

use App\Entity\Company;
use App\Managers\CompanyManager;
use App\Repository\CompanyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/company', name: 'app_company_')]
final class CompanyController extends AbstractController
{
    public function __construct(private \App\Managers\CompanyImportManager $companyImportManager) {}

    #[Route('/list', name: 'list')]
    public function getContacts(CompanyRepository $companyRepository, Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        $companies = $companyRepository->listCompany($data);
        $total = $companyRepository->getCount();
        return $this->json(['status' => 'success', 'contacts' => $companies['data'], 'page' => $companies['page'], 'limit' => $companies['limit'], 'count' => $companies['count'], 'total' => $total], 200, [], ['groups' => 'company:list']);
    }




    #[Route('/import', name: 'import', methods: ['POST'])]
    public function importCompanies(Request $request): Response
    {
        $file = $request->files->get('file');
        if (!$file) {
            return $this->json(['status' => 'error', 'message' => 'Aucun fichier envoyé'], 400);
        }
        $result = $this->companyImportManager->importFromFile($file);
        return $this->json($result);
    }

    #[Route('/edit', name: 'edit')]
    public function editContact(CompanyManager $contactManager, Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        // dd($data);

        if (empty($data)) {
            return $this->json(['status' => 'error', 'message' => 'Invalid data'], 400);
        }
        $company = $contactManager->edit($data);


        if ($company instanceof Company) {
            return $this->json(['status' => 'success', 'company' => $company], 200, [], ['groups' => 'company:edit']);
        }

        return $this->json(['status' => 'error', 'message' => 'Impossible de creer une entreprise'], 500);
    }




    #[Route('/merge/{sourceId}/{targetId}', name: 'merge', methods: ['GET'], options: ['description' => 'Fusionne deux sociétés'])]
    public function mergeCompany(
        CompanyRepository $companyRepository,
        CompanyManager $companyManager,
        $sourceId,
        $targetId
    ): Response {
        $source = $companyRepository->find($sourceId);
        $target = $companyRepository->find($targetId);

        if (!$source || !$target) {
            return $this->json([
                'status'  => 'error',
                'message' => 'Entreprise source ou cible introuvable'
            ], 404);
        }
        try {
            $companyManager->merge($source, $target);
            return $this->json([
                'status'  => 'success',
                'message' => "Fusion réussie : Proceder au nettoyage des donnees"
            ], 200);
        } catch (\Throwable $e) {
            return $this->json([
                'status'  => 'error',
                'message' => 'Erreur pendant la fusion : ' . $e->getMessage()
            ], 500);
        }
    }


    #[Route('/delete/{id}', name: 'delete', methods: ['DELETE'], options: ['description' => 'Supprime une société'])]
    public function deleteContact(int $id, CompanyManager $companyManager): Response
    {
        $companyManager->delete($id);

        return $this->json(['status' => 'success', 'message' => 'Entreprise supprimé']);
    }
}
