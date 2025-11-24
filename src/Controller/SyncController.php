<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Entity\Deal;
use App\Entity\PipelineStep;
use App\Entity\Tag;
use App\Managers\ContactManager;
use App\Managers\DealManager;
use App\Repository\ContactRepository;
use App\Repository\DealRepository;
use App\Repository\PipelineStepRepository;
use App\Repository\TagRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/sync', name: 'app_sync_')]
final class SyncController extends AbstractController
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private ContactManager $contactManager,
        private DealManager $dealManager,
        private ContactRepository $contactRepository,
        private DealRepository $dealRepository,
        private TagRepository $tagRepository,
        private PipelineStepRepository $pipelineStepRepository
    ) {}

    /**
     * Recherche un contact par email ou téléphone
     */
    #[Route('/contact/search', name: 'contact_search', methods: ['POST'], options: ['description' => 'Recherche un contact par email ou téléphone'])]
    public function searchContact(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['email']) && empty($data['phone'])) {
            return $this->json([
                'status' => 'error',
                'message' => 'Email ou téléphone requis'
            ], 400);
        }

        $email = $data['email'] ?? null;
        $phone = $data['phone'] ?? null;

        $contact = $this->contactRepository->findByEmailOrPhone($email, $phone);

        if (!$contact) {
            return $this->json([
                'status' => 'success',
                'found' => false,
                'contact' => null
            ]);
        }

        return $this->json([
            'status' => 'success',
            'found' => true,
            'contact' => $contact
        ], 200, [], ['groups' => 'contact:info']);
    }

    /**
     * Crée ou met à jour un contact (source OBLIGATOIRE)
     */
    #[Route('/contact', name: 'contact_create', methods: ['POST'], options: ['description' => 'Crée ou met à jour un contact avec source obligatoire'])]
    public function createOrUpdateContact(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Validation: source OBLIGATOIRE
        if (empty($data['source'])) {
            return $this->json([
                'status' => 'error',
                'message' => 'Le champ "source" est obligatoire (ex: wiassur-form, wiassur-form-partner)'
            ], 400);
        }

        // Validation: au moins email ou téléphone
        // Accepter à la fois le format ancien (email/phone) et le nouveau format (mails/phones)
        $hasMails = !empty($data['mails']) && is_array($data['mails']) && count($data['mails']) > 0;
        $hasPhones = !empty($data['phones']) && is_array($data['phones']) && count($data['phones']) > 0;
        $hasLegacyEmail = !empty($data['email']);
        $hasLegacyPhone = !empty($data['phone']);

        if (!$hasMails && !$hasPhones && !$hasLegacyEmail && !$hasLegacyPhone) {
            return $this->json([
                'status' => 'error',
                'message' => 'Email ou téléphone requis (formats acceptés: email/phone ou mails[]/phones[])'
            ], 400);
        }

        try {
            $contact = $this->contactManager->edit($data);

            if ($contact instanceof Contact) {
                return $this->json([
                    'status' => 'success',
                    'contact' => $contact,
                    'created' => !isset($data['id'])
                ], 200, [], ['groups' => 'contact:edit']);
            }

            return $this->json([
                'status' => 'error',
                'message' => 'Impossible de créer ou modifier le contact'
            ], 500);
        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crée ou met à jour une affaire
     */
    #[Route('/deal', name: 'deal_create', methods: ['POST'], options: ['description' => 'Crée ou met à jour une affaire'])]
    public function createOrUpdateDeal(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data)) {
            return $this->json([
                'status' => 'error',
                'message' => 'Données invalides'
            ], 400);
        }

        // Validation: contact_id obligatoire
        if (empty($data['contact'])) {
            return $this->json([
                'status' => 'error',
                'message' => 'Le contact_id est obligatoire'
            ], 400);
        }

        try {
            $deal = $this->dealManager->editDeal($data);

            if ($deal instanceof Deal) {
                return $this->json([
                    'status' => 'success',
                    'deal' => $deal,
                    'created' => !isset($data['id'])
                ], 200, [], ['groups' => 'deal:info']);
            }

            return $this->json([
                'status' => 'error',
                'message' => 'Impossible de créer ou modifier l\'affaire'
            ], 500);
        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ajoute des tags à une affaire
     */
    #[Route('/deal/{id}/tags', name: 'deal_add_tags', methods: ['POST'], options: ['description' => 'Ajoute des tags à une affaire'])]
    public function addTagsToDeal(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['tags']) || !is_array($data['tags'])) {
            return $this->json([
                'status' => 'error',
                'message' => 'Liste de tags invalide'
            ], 400);
        }

        $deal = $this->dealRepository->find($id);

        if (!$deal instanceof Deal) {
            return $this->json([
                'status' => 'error',
                'message' => 'Affaire non trouvée'
            ], 404);
        }

        $em = $this->managerRegistry->getManager();

        foreach ($data['tags'] as $tagName) {
            // Trouver ou créer le tag
            $tag = $this->tagRepository->findOrCreateByCode($tagName);

            if ($tag && !$deal->getTags()->contains($tag)) {
                $deal->addTag($tag);
            }
        }

        $em->persist($deal);
        $em->flush();

        return $this->json([
            'status' => 'success',
            'deal' => $deal
        ], 200, [], ['groups' => 'deal:info']);
    }

    /**
     * Change l'étape d'une affaire via code
     */
    #[Route('/deal/{id}/step', name: 'deal_change_step', methods: ['POST'], options: ['description' => 'Change l\'étape d\'une affaire via code'])]
    public function changeDealStep(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['step_code'])) {
            return $this->json([
                'status' => 'error',
                'message' => 'Code de l\'étape requis'
            ], 400);
        }

        $deal = $this->dealRepository->find($id);

        if (!$deal instanceof Deal) {
            return $this->json([
                'status' => 'error',
                'message' => 'Affaire non trouvée'
            ], 404);
        }

        $step = $this->pipelineStepRepository->findOneBy(['code' => $data['step_code']]);

        if (!$step instanceof PipelineStep) {
            return $this->json([
                'status' => 'error',
                'message' => 'Étape non trouvée avec le code: ' . $data['step_code']
            ], 404);
        }

        $deal->setStep($step);

        $em = $this->managerRegistry->getManager();
        $em->persist($deal);
        $em->flush();

        return $this->json([
            'status' => 'success',
            'deal' => $deal
        ], 200, [], ['groups' => 'deal:info']);
    }

    /**
     * Change le statut d'une affaire (win/lost)
     */
    #[Route('/deal/{id}/status', name: 'deal_change_status', methods: ['POST'], options: ['description' => 'Change le statut d\'une affaire'])]
    public function changeDealStatus(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['status'])) {
            return $this->json([
                'status' => 'error',
                'message' => 'Statut requis (win, lost, ou null pour réinitialiser)'
            ], 400);
        }

        $deal = $this->dealRepository->find($id);

        if (!$deal instanceof Deal) {
            return $this->json([
                'status' => 'error',
                'message' => 'Affaire non trouvée'
            ], 404);
        }

        $statusMap = [
            'win' => Deal::STATUS_WIN,
            'lost' => Deal::STATUS_LOST,
            null => null,
            'null' => null
        ];

        if (!array_key_exists($data['status'], $statusMap)) {
            return $this->json([
                'status' => 'error',
                'message' => 'Statut invalide. Valeurs acceptées: win, lost, null'
            ], 400);
        }

        $deal->setStatus($statusMap[$data['status']]);

        $em = $this->managerRegistry->getManager();
        $em->persist($deal);
        $em->flush();

        return $this->json([
            'status' => 'success',
            'deal' => $deal
        ], 200, [], ['groups' => 'deal:info']);
    }

    /**
     * Ajoute des tags à un contact
     */
    #[Route('/contact/{id}/tags', name: 'contact_add_tags', methods: ['POST'], options: ['description' => 'Ajoute des tags à un contact'])]
    public function addTagsToContact(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['tags']) || !is_array($data['tags'])) {
            return $this->json([
                'status' => 'error',
                'message' => 'Liste de tags invalide'
            ], 400);
        }

        $contact = $this->contactRepository->find($id);

        if (!$contact instanceof Contact) {
            return $this->json([
                'status' => 'error',
                'message' => 'Contact non trouvé'
            ], 404);
        }

        $em = $this->managerRegistry->getManager();

        foreach ($data['tags'] as $tagName) {
            // Trouver ou créer le tag
            $tag = $this->tagRepository->findOrCreateByCode($tagName);

            if ($tag && !$contact->getTags()->contains($tag)) {
                $contact->addTag($tag);
            }
        }

        $em->persist($contact);
        $em->flush();

        return $this->json([
            'status' => 'success',
            'contact' => $contact
        ], 200, [], ['groups' => 'contact:info']);
    }

    /**
     * Endpoint pour récupérer les données depuis Form (webhook)
     * Ce endpoint sera appelé par Form lors des transitions de ResponseGroup
     */
    #[Route('/fetch-from-form', name: 'fetch_from_form', methods: ['POST'], options: ['description' => 'Reçoit les données de synchronisation depuis Form'])]
    public function fetchFromForm(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data)) {
            return $this->json([
                'status' => 'error',
                'message' => 'Données invalides'
            ], 400);
        }

        // Validation des données minimales
        if (empty($data['prospect']) || empty($data['response_group'])) {
            return $this->json([
                'status' => 'error',
                'message' => 'Données de prospect et response_group requises'
            ], 400);
        }

        try {
            $result = [
                'contact' => null,
                'deal' => null,
                'errors' => []
            ];

            // 1. Créer ou mettre à jour le contact
            $prospectData = $data['prospect'];
            $prospectData['source'] = $data['source'] ?? 'wiassur-form';

            // Rechercher contact existant
            $existingContact = $this->contactRepository->findByEmailOrPhone(
                $prospectData['email'] ?? null,
                $prospectData['mobile'] ?? $prospectData['fixe'] ?? null
            );

            if ($existingContact) {
                $prospectData['id'] = $existingContact->getId();
                // Tag "client_habituel"
                $habituelTag = $this->tagRepository->findOrCreateByCode('client_habituel');
                if ($habituelTag && !$existingContact->getTags()->contains($habituelTag)) {
                    $existingContact->addTag($habituelTag);
                }
            } else {
                // Tag "nouveau_client"
                $newClientTag = $this->tagRepository->findOrCreateByCode('nouveau_client');
            }

            $contact = $this->contactManager->edit($prospectData);

            if (!$contact instanceof Contact) {
                throw new \Exception('Impossible de créer le contact');
            }

            // Ajouter tag nouveau_client si c'est une création
            if (!$existingContact && isset($newClientTag)) {
                $contact->addTag($newClientTag);
                $this->managerRegistry->getManager()->flush();
            }

            $result['contact'] = [
                'id' => $contact->getId(),
                'created' => !$existingContact
            ];

            // 2. Créer ou mettre à jour l'affaire
            $responseGroupData = $data['response_group'];

            // Construire les données de l'affaire
            $dealData = [
                'contact' => $contact->getId(),
                'object' => $responseGroupData['title'] ?? 'Cotation ' . ($responseGroupData['branch'] ?? 'Assurance'),
                // Note: Les champs amount et description n'existent pas dans l'entité Deal
            ];

            // Si transaction_id existe, c'est une mise à jour
            if (!empty($responseGroupData['transaction_id'])) {
                $existingDeal = $this->dealRepository->find($responseGroupData['transaction_id']);
                if ($existingDeal) {
                    $dealData['id'] = $existingDeal->getId();
                }
            }

            $deal = $this->dealManager->editDeal($dealData);

            if (!$deal instanceof Deal) {
                throw new \Exception('Impossible de créer l\'affaire');
            }

            $result['deal'] = [
                'id' => $deal->getId(),
                'created' => empty($dealData['id'])
            ];

            // 3. Ajouter les tags basés sur le status
            if (!empty($responseGroupData['status'])) {
                $tags = $this->mapStatusToTags($responseGroupData['status']);
                foreach ($tags as $tagName) {
                    $tag = $this->tagRepository->findOrCreateByCode($tagName);
                    if ($tag && !$deal->getTags()->contains($tag)) {
                        $deal->addTag($tag);
                    }
                }
            }

            // 4. Définir le statut officiel du deal basé sur le ResponseGroup status
            if (!empty($responseGroupData['status'])) {
                $dealStatus = $this->mapStatusToDealStatus($responseGroupData['status']);
                if ($dealStatus !== null) {
                    $deal->setStatus($dealStatus);
                }
            }

            // 5. Changer l'étape du pipeline
            if (!empty($responseGroupData['status'])) {
                $stepCode = $this->mapStatusToStepCode($responseGroupData['status']);
                if ($stepCode) {
                    $step = $this->pipelineStepRepository->findOneBy(['code' => $stepCode]);
                    if ($step) {
                        $deal->setStep($step);
                    }
                }
            }

            $this->managerRegistry->getManager()->flush();

            return $this->json([
                'status' => 'success',
                'result' => $result
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mapper le status ResponseGroup vers les tags
     */
    private function mapStatusToTags(string $status): array
    {
        return match($status) {
            'INCOMPLETE' => ['froid'],
            'REACHABLE' => ['interesse', 'en_prospection'],
            'COMPARED' => ['devis_demande', 'en_comparaison'],
            'OFFER_SELECTED' => ['chaud', 'proposition_envoyee'],
            'PAYMENT_INITIALIZED' => ['tres_chaud', 'en_negociation'],
            'VALIDATED' => ['gagne'],
            'ABORTED' => ['perdu', 'abandonne'],
            'EXPIRED' => ['perdu', 'expire'],
            default => []
        };
    }

    /**
     * Mapper le status ResponseGroup vers le statut officiel du Deal
     */
    private function mapStatusToDealStatus(string $status): ?string
    {
        return match($status) {
            'VALIDATED' => Deal::STATUS_WIN,
            'ABORTED', 'EXPIRED' => Deal::STATUS_LOST,
            default => null
        };
    }

    /**
     * Mapper le status ResponseGroup vers le code de step
     */
    private function mapStatusToStepCode(string $status): ?string
    {
        return match($status) {
            'INCOMPLETE', 'REACHABLE' => 'prospection',
            'COMPARED' => 'qualification',
            'OFFER_SELECTED' => 'proposition',
            'PAYMENT_INITIALIZED', 'VALIDATED' => 'negociation',
            default => null
        };
    }
}
