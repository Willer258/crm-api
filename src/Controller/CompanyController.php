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
    public function __construct(private \App\Managers\CompanyImportManager $companyImportManager, private CompanyRepository $companyRepository , private CompanyManager $companyManager) {}

    #[Route('/list', name: 'list')]
    public function getCompanies(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        $companies = $this->companyRepository->listCompany($data);
        $total = $this->companyRepository->getCount();
        return $this->json(['status' => 'success', 'companies' => $companies, 'page' => $data ['pagination']['page'] ?? 1, 'limit' => $data['pagination']['limit'] ?? 25, 'total' => $total], 200, [], ['groups' => 'company:list']);
  
    }

    #[Route('/search/{contains}', name: 'search', methods: ['GET'])]
    public function searchCompanies($contains): Response
    {
        $companies = $this->companyRepository->searchCompanies($contains);
        // dd($companies);
        return $this->json(['status' => 'success', 'companies' => $companies], 200, [], ['groups' => 'company:list']);
    }

    #[Route('/info/{id}', name: 'info', methods: ['GET'])]
    public function infoCompany(int $id): Response
    {
        $company = $this->companyRepository->find($id);
        return $this->json(['status' => 'success', 'company' => $company], 200, [], ['groups' => ['company:info' ,'userManagement', 'infos']]);
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
    public function editCompany(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        // dd($data);

        if (empty($data)) {
            return $this->json(['status' => 'error', 'message' => 'Invalid data'], 400);
        }
        $company = $this->companyManager->edit($data);


        if ($company instanceof Company) {
            return $this->json(['status' => 'success', 'company' => $company], 200, [], ['groups' => 'company:edit']);
        }

        return $this->json(['status' => 'error', 'message' => 'Impossible de creer une entreprise'], 500);
    }


    #[Route('/addPhoto', name: 'addPhoto', methods: ['POST'], options: ['description' => 'Ajoute une photo a un contact'])]
    public function addPhoto(Request $request, ): Response
    {
        $data = json_decode($request->getContent(), true);
       
        $this->companyManager->addPhoto($data);

        return $this->json(['status' => 'success', 'message' => 'Photo ajoutée']);
    }




    #[Route('/merge/{sourceId}/{targetId}', name: 'merge', methods: ['GET'], options: ['description' => 'Fusionne deux sociétés'])]
    public function mergeCompany(
        
        $sourceId,
        $targetId
    ): Response {
        $source = $this->companyRepository->find($sourceId);
        $target = $this->companyRepository->find($targetId);

        if (!$source || !$target) {
            return $this->json([
                'status'  => 'error',
                'message' => 'Entreprise source ou cible introuvable'
            ], 404);
        }
        try {
            $this->companyManager->merge($source, $target);
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
    public function deleteCompany(int $id, ): Response
    {
        $this->companyManager->delete($id);

        return $this->json(['status' => 'success', 'message' => 'Entreprise supprimé']);
    }
}
