<?php

namespace App\Controller;

use App\Entity\Message;
use App\Repository\MessageRepository;
use App\Repository\MessageThreadRepository;
use App\Service\MessagingService;
use App\Service\WorkspaceResolver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/messages', name: 'app_messages_')]
final class MessageController extends AbstractController
{
    public function __construct(
        private MessagingService $messagingService,
        private MessageRepository $messageRepository,
        private MessageThreadRepository $threadRepository,
        private WorkspaceResolver $workspaceResolver
    ) {}

    // ──────────────────────────────────────────────────────────────
    // LISTE DES MESSAGES
    // ──────────────────────────────────────────────────────────────

    /**
     * GET /api/messages
     * Liste des messages avec filtres optionnels.
     *
     * Query params:
     *   - channel    : whatsapp | email
     *   - direction  : in | out
     *   - status     : pending | sent | delivered | read | failed
     *   - contact_id : int
     *   - thread_id  : int
     *   - page       : int (défaut 1)
     *   - limit      : int (défaut 25)
     */
    #[Route('', name: 'list', methods: ['GET'])]
    public function listMessages(Request $request): Response
    {
        $filters = [
            'channel'    => $request->query->get('channel'),
            'direction'  => $request->query->get('direction'),
            'status'     => $request->query->get('status'),
            'contact_id' => $request->query->getInt('contact_id') ?: null,
            'thread_id'  => $request->query->getInt('thread_id') ?: null,
        ];

        // Retirer les filtres vides
        $filters = array_filter($filters);

        $page  = max(1, $request->query->getInt('page', 1));
        $limit = min(100, max(1, $request->query->getInt('limit', 25)));

        $messages = $this->messageRepository->listMessages($filters, $page, $limit);
        $total    = $this->messageRepository->countMessages($filters);

        return $this->json([
            'status'  => 'success',
            'data'    => $messages,
            'pagination' => [
                'page'     => $page,
                'limit'    => $limit,
                'total'    => $total,
                'pages'    => (int) ceil($total / $limit),
                'has_next' => ($page * $limit) < $total,
                'has_prev' => $page > 1,
            ],
        ], 200, [], ['groups' => ['message:list']]);
    }

    // ──────────────────────────────────────────────────────────────
    // ENVOI DE MESSAGES
    // ──────────────────────────────────────────────────────────────

    /**
     * POST /api/messages/send
     * Envoie un message WhatsApp ou Email.
     *
     * Body:
     * {
     *   "channel": "whatsapp" | "email",
     *   "to": "2250102030405",            // numéro ou email
     *   "content": "Bonjour !",
     *   "subject": "Re: Devis",           // Email uniquement
     *   "contact_id": 42                   // Optionnel
     * }
     */
    #[Route('/send', name: 'send', methods: ['POST'])]
    public function sendMessage(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data)) {
            return $this->json([
                'status'  => 'error',
                'message' => 'Corps de requête invalide ou vide.',
                'code'    => 'INVALID_BODY',
            ], 400);
        }

        $channel = $data['channel'] ?? '';
        $to      = trim($data['to'] ?? '');
        $content = trim($data['content'] ?? '');

        // Validation
        if (!in_array($channel, [Message::CHANNEL_WHATSAPP, Message::CHANNEL_EMAIL])) {
            return $this->json([
                'status'  => 'error',
                'message' => 'Canal invalide. Valeurs acceptées : whatsapp, email.',
                'code'    => 'INVALID_CHANNEL',
            ], 400);
        }

        if (empty($to)) {
            return $this->json([
                'status'  => 'error',
                'message' => 'Le destinataire (to) est requis.',
                'code'    => 'MISSING_RECIPIENT',
            ], 400);
        }

        if (empty($content)) {
            return $this->json([
                'status'  => 'error',
                'message' => 'Le contenu du message est requis.',
                'code'    => 'MISSING_CONTENT',
            ], 400);
        }

        // Résoudre le workspace
        $workspace = $this->workspaceResolver->getCurrentWorkspace();

        $payload = [
            'to'         => $to,
            'content'    => $content,
            'workspace'  => $workspace,
            'contact_id' => $data['contact_id'] ?? null,
        ];

        if ($channel === Message::CHANNEL_EMAIL) {
            $payload['subject'] = $data['subject'] ?? '(Sans objet)';
            $result = $this->messagingService->sendEmail($payload);
        } else {
            $result = $this->messagingService->sendWhatsApp($payload);
        }

        if (!$result['success']) {
            return $this->json([
                'status'  => 'error',
                'message' => 'Échec d\'envoi du message.',
                'error'   => $result['error'],
                'code'    => 'SEND_FAILED',
            ], 502);
        }

        return $this->json([
            'status'  => 'success',
            'message' => $result['message'],
            'thread'  => $result['thread'],
        ], 201, [], ['groups' => ['message:detail', 'thread:list']]);
    }

    // ──────────────────────────────────────────────────────────────
    // FILS DE DISCUSSION (THREADS)
    // ──────────────────────────────────────────────────────────────

    /**
     * GET /api/messages/threads
     * Liste des fils de discussion.
     *
     * Query params:
     *   - channel    : whatsapp | email
     *   - status     : open | archived
     *   - contact_id : int
     *   - unread_only: 1
     *   - search     : string
     *   - page       : int
     *   - limit      : int
     */
    #[Route('/threads', name: 'threads_list', methods: ['GET'])]
    public function listThreads(Request $request): Response
    {
        $filters = [
            'channel'     => $request->query->get('channel'),
            'status'      => $request->query->get('status', 'open'),
            'contact_id'  => $request->query->getInt('contact_id') ?: null,
            'unread_only' => $request->query->getBoolean('unread_only'),
            'search'      => $request->query->get('search'),
        ];

        $filters = array_filter($filters, fn($v) => $v !== null && $v !== '' && $v !== false);

        $page  = max(1, $request->query->getInt('page', 1));
        $limit = min(100, max(1, $request->query->getInt('limit', 25)));

        $threads = $this->threadRepository->listThreads($filters, $page, $limit);
        $total   = $this->threadRepository->countThreads($filters);
        $unread  = $this->threadRepository->countUnread();

        return $this->json([
            'status'       => 'success',
            'data'         => $threads,
            'unread_total' => $unread,
            'pagination'   => [
                'page'     => $page,
                'limit'    => $limit,
                'total'    => $total,
                'pages'    => (int) ceil($total / $limit),
                'has_next' => ($page * $limit) < $total,
                'has_prev' => $page > 1,
            ],
        ], 200, [], ['groups' => ['thread:list']]);
    }

    /**
     * GET /api/messages/threads/{id}
     * Détail d'un fil de discussion avec ses messages.
     */
    #[Route('/threads/{id}', name: 'thread_detail', methods: ['GET'])]
    public function threadDetail(int $id): Response
    {
        $thread = $this->threadRepository->find($id);

        if (!$thread) {
            return $this->json([
                'status'  => 'error',
                'message' => 'Fil de discussion introuvable.',
                'code'    => 'NOT_FOUND',
            ], 404);
        }

        return $this->json([
            'status' => 'success',
            'thread' => $thread,
        ], 200, [], ['groups' => ['thread:detail', 'message:list', 'message:detail']]);
    }

    /**
     * POST /api/messages/threads/{id}/read
     * Marque tous les messages d'un thread comme lus.
     */
    #[Route('/threads/{id}/read', name: 'thread_mark_read', methods: ['POST'])]
    public function markThreadRead(int $id): Response
    {
        $thread = $this->threadRepository->find($id);

        if (!$thread) {
            return $this->json([
                'status'  => 'error',
                'message' => 'Fil de discussion introuvable.',
                'code'    => 'NOT_FOUND',
            ], 404);
        }

        $this->messagingService->markThreadAsRead($thread);

        return $this->json([
            'status'  => 'success',
            'message' => 'Fil marqué comme lu.',
        ]);
    }
}
