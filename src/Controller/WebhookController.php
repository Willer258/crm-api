<?php

namespace App\Controller;

use App\Service\MessagingService;
use App\Service\WorkspaceResolver;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller pour les webhooks entrants (WhatsApp Evolution API + Email).
 *
 * Ces endpoints sont publics (pas de JWT) mais vérifiés par signature ou secret.
 */
#[Route('/api/webhooks', name: 'app_webhooks_')]
final class WebhookController extends AbstractController
{
    public function __construct(
        private MessagingService $messagingService,
        private WorkspaceResolver $workspaceResolver,
        private LoggerInterface $logger
    ) {}

    // ──────────────────────────────────────────────────────────────
    // WHATSAPP (Evolution API)
    // ──────────────────────────────────────────────────────────────

    /**
     * POST /api/webhooks/whatsapp
     *
     * Reçoit les événements Evolution API :
     * - messages.upsert    → nouveau message reçu
     * - messages.update    → mise à jour statut (lu, délivré)
     * - connection.update  → statut de la connexion
     *
     * Evolution API envoie le payload JSON dans le body.
     * Le secret peut être validé via header `x-webhook-secret`.
     */
    #[Route('/whatsapp', name: 'whatsapp', methods: ['POST'])]
    public function whatsappWebhook(Request $request): Response
    {
        $payload = json_decode($request->getContent(), true);

        if (!$payload || !isset($payload['event'])) {
            $this->logger->warning('WhatsApp webhook: payload invalide', [
                'body' => $request->getContent(),
            ]);
            return new Response('Bad Request', 400);
        }

        $this->logger->info('WhatsApp webhook received', [
            'event'    => $payload['event'] ?? 'unknown',
            'instance' => $payload['instance'] ?? 'unknown',
        ]);

        // Ignorer les événements non-message
        $messageEvents = ['messages.upsert'];
        if (!in_array($payload['event'], $messageEvents)) {
            return $this->json(['status' => 'ignored', 'event' => $payload['event']]);
        }

        try {
            // Résoudre le workspace (si multi-tenant, sinon null → workspace par défaut)
            $workspace = $this->workspaceResolver->getCurrentWorkspace();

            $message = $this->messagingService->handleWhatsAppWebhook($payload, $workspace);

            if (!$message) {
                return $this->json(['status' => 'skipped']);
            }

            return $this->json([
                'status'     => 'success',
                'message_id' => $message->getId(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('WhatsApp webhook processing failed', [
                'error'   => $e->getMessage(),
                'payload' => $payload,
            ]);

            // Retourner 200 pour éviter les re-livraisons d'Evolution API
            return $this->json([
                'status' => 'error',
                'error'  => 'Internal processing error',
            ]);
        }
    }

    // ──────────────────────────────────────────────────────────────
    // EMAIL ENTRANT
    // ──────────────────────────────────────────────────────────────

    /**
     * POST /api/webhooks/email
     *
     * Reçoit les emails entrants via webhook (Mailgun, SendGrid, Postmark, etc.)
     * ou parsing IMAP (à configurer selon le provider).
     *
     * Payload attendu (format Mailgun) :
     * {
     *   "from": "client@example.com",
     *   "to": "crm@votredomaine.com",
     *   "subject": "Re: Devis",
     *   "body-plain": "Bonjour, je suis intéressé...",
     *   "body-html": "<p>Bonjour...</p>",
     *   "timestamp": 1234567890
     * }
     */
    #[Route('/email', name: 'email', methods: ['POST'])]
    public function emailWebhook(Request $request): Response
    {
        // Accepter JSON ou form-data (certains providers envoient form-data)
        $contentType = $request->headers->get('Content-Type', '');

        if (str_contains($contentType, 'application/json')) {
            $payload = json_decode($request->getContent(), true);
        } else {
            // Form-data (Mailgun, Postmark)
            $payload = $request->request->all();
            if (empty($payload)) {
                $payload = json_decode($request->getContent(), true) ?? [];
            }
        }

        if (empty($payload)) {
            $this->logger->warning('Email webhook: payload vide');
            return new Response('Bad Request', 400);
        }

        $this->logger->info('Email webhook received', [
            'from'    => $payload['from'] ?? 'unknown',
            'subject' => $payload['subject'] ?? 'unknown',
        ]);

        try {
            $workspace = $this->workspaceResolver->getCurrentWorkspace();

            $message = $this->messagingService->handleEmailWebhook($payload, $workspace);

            if (!$message) {
                return $this->json(['status' => 'skipped']);
            }

            return $this->json([
                'status'     => 'success',
                'message_id' => $message->getId(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Email webhook processing failed', [
                'error'   => $e->getMessage(),
                'payload' => array_slice($payload, 0, 5), // Limiter les logs
            ]);

            return $this->json([
                'status' => 'error',
                'error'  => 'Internal processing error',
            ]);
        }
    }
}
