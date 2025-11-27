<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Managers\ContactImportManager;
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
    public function __construct(private ManagerRegistry $managerRegistry, private ContactImportManager $contactImportManager, private ContactManager $contactManager) {}



    #[Route('/search/{contains}', name: 'search', methods: ['GET'])]
    public function searchContacts($contains, ContactRepository $contactRepository): Response
    {
        $contacts = $contactRepository->searchContacts($contains);
        return $this->json(['status' => 'success', 'contacts' => $contacts], 200, [], ['groups' => 'contact:list']);
    }

    #[Route('/list', name: 'list', options: ['description' => 'Liste tous les contacts'])]
    public function getContacts(ContactRepository $contactRepository, Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        $contacts = $contactRepository->listContacts($data);
        $total = $contactRepository->getCount();
        return $this->json(['status' => 'success', 'contacts' => $contacts, 'page' => $data ['pagination']['page'] ?? 1, 'limit' => $data['pagination']['limit'] ?? 25, 'total' => $total], 200, [], ['groups' => 'contact:list']);
    }

    #[Route('/info/{id}', name: 'info', options: ['description' => 'Affiche les informations d\'un contact'])]
    public function infoContact(ContactRepository $contactRepository, int $id): Response
    {
        $contact = $contactRepository->find($id);
        if (!$contact) {
            return $this->json(['status' => 'error', 'message' => 'Contact non trouvé'], 404);
        }
        return $this->json(['status' => 'success', 'contact' => $contact], 200, [], ['groups' => ['contact:info', 'userManagement', 'infos']]);
    }   


    #[Route('/import', name: 'import', methods: ['POST'], options: ['description' => 'Importe des contacts depuis un fichier'])]
    public function importContacts(Request $request): Response
    {
        $file = $request->files->get('file');
        if (!$file) {
            return $this->json(['status' => 'error', 'message' => 'Aucun fichier envoyé'], 400);
        }

        // Options de validation
        $skipDuplicates = filter_var($request->request->get('skip_duplicates', false), FILTER_VALIDATE_BOOLEAN);
        $autoMerge = filter_var($request->request->get('auto_merge', false), FILTER_VALIDATE_BOOLEAN);

        $result = $this->contactImportManager->importFromFile($file, $skipDuplicates, $autoMerge);
        return $this->json($result);
    }

    #[Route('/edit', name: 'edit', options: ['description' => 'Crée ou modifie un contact'])]
    public function editContact(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        // dd($data);

        if (empty($data)) {
            return $this->json(['status' => 'error', 'message' => 'Invalid data'], 400);
        }
        $contact = $this->contactManager->edit($data);


        if ($contact instanceof Contact) {
            return $this->json(['status' => 'success', 'contact' => $contact], 200, [], ['groups' => 'contact:edit']);
        }

        return $this->json(['status' => 'error', 'message' => 'Impossible de creer un contact'], 500);
    }


    #[Route('/associate/{id}/{idCompany}', name: 'associate_to_company',  methods: ['PATCH'], options: ['description' => 'Associe un contact à une entreprise'])]
    public function associateCompany(
        int $id,
        int $idCompany,
        ContactRepository $contacts,
        CompanyRepository $companies,
    ): Response {
        $contact = $contacts->find($id);
        $company = $companies->find($idCompany);

        if (!$contact || !$company) {
            return $this->json(['error' => 'Contact ou entreprise introuvable'], 404);
        }

        $contact->setCompany($company);

        $this->managerRegistry->getManager()->flush();

        return $this->json(['status' => 'success', 'company' => $company], 200, [], ['groups' => 'company:info']);
    }


    #[Route('/unassociate/{id}', name: 'unassociate_to_company',  methods: ['DELETE'], options: ['description' => 'Dissocie un contact d\'une entreprise'])]
    public function unassociateCompany(
        int $id,
        ContactRepository $contacts,
    ): Response {
        $contact = $contacts->find($id);

        if (!$contact) {
            return $this->json(['error' => 'Contact ou entreprise introuvable'], 404);
        }

        $contact->setCompany(null);

        $this->managerRegistry->getManager()->flush();

        return $this->json(['status' => 'success', 'contact' => $contact], 200, [], ['groups' => 'contact:info']);
    }


    #[Route('/merge/{sourceId}/{targetId}', name: 'merge', methods: ['POST'], options: ['description' => 'Fusionne deux contacts'])]
    public function mergeContacts(
        ContactRepository $contactRepository,
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
            $this->contactManager->merge($source, $target);
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

    #[Route('/addPhoto', name: 'addPhoto', methods: ['POST'], options: ['description' => 'Ajoute une photo a un contact'])]
    public function addPhoto(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
       
        $this->contactManager->addPhoto($data);

        return $this->json(['status' => 'success', 'message' => 'Photo ajoutée']);
    }

    #[Route('/delete/{id}', name: 'delete', methods: ['DELETE'], options: ['description' => 'Supprime un contact'])]
    public function deleteContact(int $id): Response
    {
        $this->contactManager->delete($id);

        return $this->json(['status' => 'success', 'message' => 'Contact supprimé']);
    }
}



