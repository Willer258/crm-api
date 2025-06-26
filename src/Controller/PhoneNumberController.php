<?php

namespace App\Controller;

use App\Entity\PhoneNumber;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

use App\Managers\PhoneNumberManager;
// Ce contrôleur suppose que tu ajouteras l'entité PhoneNumber plus tard
#[Route('/phone/number')]
final class PhoneNumberController extends AbstractController
{
    public function __construct(
        private PhoneNumberManager $phoneNumberManager,
        private \App\Repository\PhoneNumberRepository $phoneNumberRepository
    ) {
    }

    #[Route('/', name: 'phone_number_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $numbers = $this->phoneNumberRepository->findAll();
        return $this->json($numbers);
    }

    #[Route('/{id}', name: 'phone_number_show', methods: ['GET'])]
    public function show(\App\Entity\PhoneNumber $phoneNumber): JsonResponse
    {
        return $this->json($phoneNumber);
    }

    #[Route('/edit', name: 'phone_number_edit', methods: ['POST'])]
    public function editPhoneNumber(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (empty($data)) {
            return $this->json(['status' => 'error', 'message' => 'Invalid data'], 400);
        }
        if (isset($data['id'])) {
            $phoneNumber = $this->phoneNumberRepository->find($data['id']);
            if ($phoneNumber instanceof PhoneNumber) {
                $phoneNumber = $this->phoneNumberManager->updateFromArray($phoneNumber, $data);
          
            }
        } else {
            $phoneNumber = $this->phoneNumberManager->createFromArray($data);
        }
        if ($phoneNumber instanceof PhoneNumber) {
            return $this->json(['status' => 'success', 'phoneNumber' => $phoneNumber ], 200, [], ['groups' => 'contact:info']);
        }
        return $this->json(['status' => 'error', 'message' => 'Impossible de créer ou modifier un numéro'], 500);
    }


    #[Route('/delete/{id}', name: 'phone_number_delete', methods: ['DELETE'])]
    public function deletePhoneNumber(int $id): JsonResponse
    {
        $this->phoneNumberManager->delete($this->phoneNumberRepository->find($id));
        return $this->json(['status' => 'success', 'message' => 'Numéro supprimé']);
    }
}

