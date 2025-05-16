<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Managers\ContactManager;
use App\Repository\CompanyRepository;
use App\Repository\ContactRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/contact', name: 'app_contact_')]
final class ContactController extends AbstractController
{
    public function __construct(private ManagerRegistry $managerRegistry, private \App\Managers\ContactImportManager $contactImportManager) {}



    #[Route('/list', name: 'list')]
    public function getContacts(ContactRepository $contactRepository, Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        $contacts = $contactRepository->listContacts($data);
        $total = $contactRepository->getCount();
        return $this->json(['status' => 'success', 'contacts' => $contacts['data'], 'page' => $contacts['page'], 'limit' => $contacts['limit'], 'count' => $contacts['count'], 'total' => $total], 200, [], ['groups' => 'contact:list']);
    }


    #[Route('/import', name: 'import', methods: ['POST'])]
    public function importContacts(Request $request): Response
    {
        $file = $request->files->get('file');
        if (!$file) {
            return $this->json(['status' => 'error', 'message' => 'Aucun fichier envoyé'], 400);
        }
        $result = $this->contactImportManager->importFromFile($file);
        return $this->json($result);
    }

    #[Route('/edit', name: 'edit')]
    public function editContact(ContactManager $contactManager, Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        // dd($data);

        if (empty($data)) {
            return $this->json(['status' => 'error', 'message' => 'Invalid data'], 400);
        }
        $contact = $contactManager->edit($data);


        if ($contact instanceof Contact) {
            return $this->json(['status' => 'success', 'contact' => $contact], 200, [], ['groups' => 'contact:edit']);
        }

        return $this->json(['status' => 'error', 'message' => 'Impossible de creer un contact'], 500);
    }


    #[Route('/associate/{id}/{idCompany}', name: 'associate_to_company',  methods: ['GET'])]
    public function associateCompany(
        int $id,
        int $idCompany,
        Request $request,
        ContactRepository $contacts,
        CompanyRepository $companies,
    ): Response {
        $contact = $contacts->find($id);
        $data    = json_decode($request->getContent(), true);
        $company = $companies->find($idCompany);

        if (!$contact || !$company) {
            return $this->json(['error' => 'Contact ou entreprise introuvable'], 404);
        }

        $contact->setCompany($company);

        $this->managerRegistry->getManager()->flush();

        return $this->json(['status' => 'success', 'company' => $company->getName()]);
    }


    #[Route('/merge/{sourceId}/{targetId}', name: 'merge', methods: ['GET'], options: ['description' => 'Fusionne deux contacts'])]
    public function mergeContacts(
        ContactRepository $contactRepository,
        ContactManager $contactManager,
        $sourceId,
        $targetId
    ): Response {
        $source = $contactRepository->find($sourceId);
        $target = $contactRepository->find($targetId);

        if (!$source || !$target) {
            return $this->json([
                'status'  => 'error',
                'message' => 'Contact source ou cible introuvable'
            ], 404);
        }
        try {
            $contactManager->merge($source, $target);
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

    #[Route('/delete/{id}', name: 'delete', methods: ['DELETE'], options: ['description' => 'Supprime un contact'])]
    public function deleteContact(int $id, ContactManager $contactManager): Response
    {
        $contactManager->delete($id);

        return $this->json(['status' => 'success', 'message' => 'Contact supprimé']);
    }
}
