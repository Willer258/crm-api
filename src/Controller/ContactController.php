<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Managers\ContactManager;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/contact', name: 'app_contact_')]
final class ContactController extends AbstractController
{

    public function __construct(private ManagerRegistry $managerRegistry) {}
    
    #[Route('/list', name: 'list')]
    public function getContacts(): Response
    {
        $contacts = $this->managerRegistry->getRepository(Contact::class)->findAll();
        return $this->json(['status' => 'success', 'contacts' => $contacts], 200, [], ['groups' => 'contact:list']);
   
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


    #[Route('/delete/{id}', name: 'delete', methods: ['DELETE'])]
    public function deleteContact(int $id, ContactManager $contactManager): Response
    {
       $contactManager->delete($id);

        return $this->json(['status' => 'success', 'message' => 'Contact supprimé']);
    }


}
