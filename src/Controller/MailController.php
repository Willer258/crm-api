<?php

namespace App\Controller;

use App\Entity\Mail;
use App\Managers\MailManager;
use App\Repository\MailRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/mail')]
final class MailController extends AbstractController
{

    public function __construct(private MailManager $mailManager, private MailRepository $mailRepository)
    {
    }
    #[Route('/', name: 'mail_index', methods: ['GET'])]
    public function index(MailRepository $mailRepository): JsonResponse
    {
        $mails = $mailRepository->findAll();
        return $this->json($mails);
    }


    #[Route('/edit', name: 'mail_edit', methods: ['POST'])]
    public function editMail(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (empty($data)) {
            return $this->json(['status' => 'error', 'message' => 'Invalid data'], 400);
        }
        if (isset($data['id'])) {
            $mail = $this->mailRepository->find($data['id']);
            if ($mail instanceof Mail) {
                $mail = $this->mailManager->updateFromArray($mail, $data);
            }
        } else {
            $mail = $this->mailManager->createFromArray($data);
        }
        if ($mail instanceof Mail) {
            return $this->json(['status' => 'success', 'mail' => $mail], 200, [], ['groups' => 'contact:info']);
        }
        return $this->json(['status' => 'error', 'message' => 'Impossible de créer ou modifier un mail'], 500);
    }


    #[Route('/delete/{id}', name: 'mail_delete', methods: ['DELETE'])]
    public function deleteMail(int $id): JsonResponse
    {
        $this->mailManager->delete($this->mailRepository->find($id));
        return $this->json(['status' => 'success', 'message' => 'Mail supprimé']);
    }
}
