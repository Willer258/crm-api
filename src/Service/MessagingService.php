<?php

namespace App\Service;

use App\Entity\Contact;
use App\Entity\Message;
use App\Entity\MessageThread;
use App\Entity\Workspace;
use App\Repository\ContactRepository;
use App\Repository\MessageThreadRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Orchestrateur de messagerie CRM.
 *
 * Gère la création/mise à jour des threads et messages,
 * l'association aux contacts, et le dispatch vers WhatsApp/Email.
 */
class MessagingService
{
    public function __construct(
        private EntityManagerInterface $em,
        private MessageThreadRepository $threadRepository,
        private ContactRepository $contactRepository,
        private WhatsAppService $whatsAppService,
        private CrmEmailService $crmEmailService,
        private LoggerInterface $logger
    ) {}

    // ──────────────────────────────────────────────────────────────
    // ENVOI DE MESSAGES
    // ──────────────────────────────────────────────────────────────

    /**
     * Envoie un message WhatsApp à un contact.
     *
     * @param array{
     *   to: string,
     *   content: string,
     *   contact_id?: int,
     *   workspace: Workspace
     * } $data
     */
    public function sendWhatsApp(array $data): array
    {
        $workspace = $data['workspace'];
        $phone     = $data['to'];
        $content   = $data['content'];
        $contact   = null;

        // Résoudre le contact si fourni
        if (!empty($data['contact_id'])) {
            $contact = $this->contactRepository->find($data['contact_id']);
        }

        // Envoyer via Evolution API
        $result = $this->whatsAppService->sendTextMessage($phone, $content);

        // Trouver ou créer un thread
        $thread = $this->findOrCreateThread(
            channel: Message::CHANNEL_WHATSAPP,
            contactAddress: $phone,
            contact: $contact,
            workspace: $workspace
        );

        // Créer le message en base
        $message = $this->createMessage([
            'channel'     => Message::CHANNEL_WHATSAPP,
            'direction'   => Message::DIRECTION_OUT,
            'fromAddress' => 'crm',
            'toAddress'   => $phone,
            'content'     => $content,
            'contact'     => $contact,
            'thread'      => $thread,
            'workspace'   => $workspace,
            'status'      => $result['success'] ? Message::STATUS_SENT : Message::STATUS_FAILED,
            'metadata'    => [
                'evolution_message_id' => $result['messageId'],
                'error'                => $result['error'],
            ],
        ]);

        $this->updateThread($thread, $message);

        return [
            'success' => $result['success'],
            'message' => $message,
            'thread'  => $thread,
            'error'   => $result['error'],
        ];
    }

    /**
     * Envoie un email à un contact.
     *
     * @param array{
     *   to: string,
     *   subject: string,
     *   content: string,
     *   contact_id?: int,
     *   workspace: Workspace
     * } $data
     */
    public function sendEmail(array $data): array
    {
        $workspace = $data['workspace'];
        $toEmail   = $data['to'];
        $subject   = $data['subject'] ?? '(Sans objet)';
        $content   = $data['content'];
        $contact   = null;

        if (!empty($data['contact_id'])) {
            $contact = $this->contactRepository->find($data['contact_id']);
        }

        // Envoyer via Symfony Mailer
        $result = $contact
            ? $this->crmEmailService->sendToContact($contact, $toEmail, $subject, $content)
            : $this->crmEmailService->sendToAddress($toEmail, '', $subject, $content);

        // Thread
        $thread = $this->findOrCreateThread(
            channel: Message::CHANNEL_EMAIL,
            contactAddress: $toEmail,
            contact: $contact,
            workspace: $workspace,
            subject: $subject
        );

        // Message
        $message = $this->createMessage([
            'channel'     => Message::CHANNEL_EMAIL,
            'direction'   => Message::DIRECTION_OUT,
            'fromAddress' => 'crm@example.com',
            'toAddress'   => $toEmail,
            'subject'     => $subject,
            'content'     => $content,
            'contact'     => $contact,
            'thread'      => $thread,
            'workspace'   => $workspace,
            'status'      => $result['success'] ? Message::STATUS_SENT : Message::STATUS_FAILED,
            'metadata'    => [
                'message_id' => $result['messageId'],
                'error'      => $result['error'],
            ],
        ]);

        $this->updateThread($thread, $message);

        return [
            'success' => $result['success'],
            'message' => $message,
            'thread'  => $thread,
            'error'   => $result['error'],
        ];
    }

    // ──────────────────────────────────────────────────────────────
    // RÉCEPTION DE MESSAGES (WEBHOOKS)
    // ──────────────────────────────────────────────────────────────

    /**
     * Traite un webhook WhatsApp entrant (Evolution API).
     */
    public function handleWhatsAppWebhook(array $payload, ?Workspace $workspace = null): ?Message
    {
        $parsed = $this->whatsAppService->parseWebhook($payload);

        // On ne traite que les messages entrants (pas les ACKs, statuts, etc.)
        if ($parsed['event'] !== 'messages.upsert' || $parsed['isGroup']) {
            $this->logger->debug('WhatsApp webhook ignored', ['event' => $parsed['event']]);
            return null;
        }

        if (empty($parsed['body'])) {
            return null;
        }

        $from    = $parsed['from'];
        $content = $parsed['body'];

        // Chercher le contact par numéro de téléphone dans le CRM
        $contact = $this->findContactByPhone($from);

        // Thread
        $thread = $this->findOrCreateThread(
            channel: Message::CHANNEL_WHATSAPP,
            contactAddress: $from,
            contact: $contact,
            workspace: $workspace
        );

        // Message
        $message = $this->createMessage([
            'channel'     => Message::CHANNEL_WHATSAPP,
            'direction'   => Message::DIRECTION_IN,
            'fromAddress' => $from,
            'toAddress'   => 'crm',
            'content'     => $content,
            'contact'     => $contact,
            'thread'      => $thread,
            'workspace'   => $workspace,
            'status'      => Message::STATUS_DELIVERED,
            'metadata'    => [
                'evolution_message_id' => $parsed['messageId'],
                'timestamp'            => $parsed['timestamp'],
                'raw'                  => $parsed['raw'] ?? [],
            ],
        ]);

        // Incrémenter les non-lus
        $thread->incrementUnreadCount();
        $this->updateThread($thread, $message);

        $this->logger->info('WhatsApp message received', [
            'from'      => $from,
            'messageId' => $parsed['messageId'],
            'contact'   => $contact?->getId(),
        ]);

        return $message;
    }

    /**
     * Traite un webhook email entrant.
     */
    public function handleEmailWebhook(array $payload, ?Workspace $workspace = null): ?Message
    {
        $parsed = $this->crmEmailService->parseInboundWebhook($payload);

        if (empty($parsed['from']) || empty($parsed['body'])) {
            return null;
        }

        $fromEmail = $this->extractEmail($parsed['from']);
        $contact   = $this->findContactByEmail($fromEmail);

        $thread = $this->findOrCreateThread(
            channel: Message::CHANNEL_EMAIL,
            contactAddress: $fromEmail,
            contact: $contact,
            workspace: $workspace,
            subject: $parsed['subject']
        );

        $message = $this->createMessage([
            'channel'     => Message::CHANNEL_EMAIL,
            'direction'   => Message::DIRECTION_IN,
            'fromAddress' => $parsed['from'],
            'toAddress'   => $parsed['to'],
            'subject'     => $parsed['subject'],
            'content'     => $parsed['body'],
            'contact'     => $contact,
            'thread'      => $thread,
            'workspace'   => $workspace,
            'status'      => Message::STATUS_DELIVERED,
            'metadata'    => [
                'message_id' => $parsed['messageId'],
                'body_html'  => $parsed['bodyHtml'] ?? null,
            ],
        ]);

        $thread->incrementUnreadCount();
        $this->updateThread($thread, $message);

        return $message;
    }

    // ──────────────────────────────────────────────────────────────
    // GESTION DES THREADS
    // ──────────────────────────────────────────────────────────────

    /**
     * Marque tous les messages d'un thread comme lus.
     */
    public function markThreadAsRead(MessageThread $thread): void
    {
        $thread->resetUnreadCount();
        $this->em->persist($thread);
        $this->em->flush();
    }

    // ──────────────────────────────────────────────────────────────
    // MÉTHODES PRIVÉES
    // ──────────────────────────────────────────────────────────────

    private function findOrCreateThread(
        string $channel,
        string $contactAddress,
        ?Contact $contact,
        ?Workspace $workspace,
        ?string $subject = null
    ): MessageThread {
        // Chercher thread existant (ouvert) par adresse
        $thread = $this->threadRepository->findThreadByAddress(
            contactAddress: $contactAddress,
            channel: $channel,
            workspace: $workspace
        );

        if (!$thread) {
            $thread = new MessageThread();
            $thread->setChannel($channel);
            $thread->setContactAddress($contactAddress);
            $thread->setContact($contact);
            $thread->setWorkspace($workspace);

            if ($subject) {
                $thread->setSubject($subject);
            }

            $this->em->persist($thread);
        } elseif ($contact && !$thread->getContact()) {
            // Associer le contact si on vient de le trouver
            $thread->setContact($contact);
        }

        return $thread;
    }

    private function createMessage(array $data): Message
    {
        $message = new Message();
        $message->setChannel($data['channel']);
        $message->setDirection($data['direction']);
        $message->setFromAddress($data['fromAddress']);
        $message->setToAddress($data['toAddress']);
        $message->setContent($data['content']);
        $message->setStatus($data['status'] ?? Message::STATUS_PENDING);
        $message->setThread($data['thread']);
        $message->setContact($data['contact'] ?? null);
        $message->setWorkspace($data['workspace'] ?? null);
        $message->setMetadata($data['metadata'] ?? null);

        if (!empty($data['subject'])) {
            $message->setSubject($data['subject']);
        }

        if ($data['direction'] === Message::DIRECTION_OUT && $data['status'] === Message::STATUS_SENT) {
            $message->setSentAt(new \DateTimeImmutable());
        }

        $this->em->persist($message);
        $this->em->flush();

        return $message;
    }

    private function updateThread(MessageThread $thread, Message $message): void
    {
        $thread->setLastMessageAt(new \DateTimeImmutable());
        $thread->setLastMessagePreview($message->getContentPreview(80));

        $this->em->persist($thread);
        $this->em->flush();
    }

    private function findContactByPhone(string $phone): ?Contact
    {
        // Normaliser le numéro pour la recherche
        $normalized = preg_replace('/[^0-9]/', '', $phone);

        // Recherche via les propriétés du contact
        return $this->em->createQuery(
            'SELECT c FROM App\Entity\Contact c
             JOIN c.properties p
             WHERE p.key = :key
             AND (p.value = :phone OR p.value = :normalized)'
        )
        ->setParameter('key', 'phone')
        ->setParameter('phone', $phone)
        ->setParameter('normalized', $normalized)
        ->setMaxResults(1)
        ->getOneOrNullResult();
    }

    private function findContactByEmail(string $email): ?Contact
    {
        return $this->em->createQuery(
            'SELECT c FROM App\Entity\Contact c
             JOIN c.properties p
             WHERE p.key = :key AND p.value = :email'
        )
        ->setParameter('key', 'email')
        ->setParameter('email', strtolower($email))
        ->setMaxResults(1)
        ->getOneOrNullResult();
    }

    private function extractEmail(string $from): string
    {
        // Extraire l'email de "Nom Prénom <email@domain.com>"
        if (preg_match('/<([^>]+)>/', $from, $matches)) {
            return trim($matches[1]);
        }
        return trim($from);
    }
}
