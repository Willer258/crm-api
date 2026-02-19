<?php

namespace App\Service;

use App\Entity\Contact;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

/**
 * Service d'envoi d'emails CRM (conversations avec les contacts).
 *
 * Distinct de EmailService qui gère les emails système (vérification, reset password).
 */
class CrmEmailService
{
    private const FROM_EMAIL = 'crm@example.com'; // Configuré via env
    private const FROM_NAME  = 'CRM';

    public function __construct(
        private MailerInterface $mailer,
        private LoggerInterface $logger,
        string $fromEmail = '',
        string $fromName = 'CRM'
    ) {
        if ($fromEmail) {
            // Injection via env si disponible
        }
    }

    /**
     * Envoie un email à un contact.
     *
     * @return array{success: bool, messageId: ?string, error: ?string}
     */
    public function sendToContact(
        Contact $contact,
        string $toEmail,
        string $subject,
        string $body,
        bool $isHtml = false
    ): array {
        try {
            $contactName = $this->getContactName($contact);

            $email = (new Email())
                ->from(sprintf('%s <%s>', self::FROM_NAME, self::FROM_EMAIL))
                ->to(sprintf('%s <%s>', $contactName, $toEmail))
                ->subject($subject);

            if ($isHtml) {
                $email->html($body);
            } else {
                $email->text($body);
            }

            $this->mailer->send($email);

            // Génération d'un ID simple pour le suivi
            $messageId = 'crm-' . uniqid('', true) . '@crm';

            $this->logger->info('CRM email sent', [
                'to'        => $toEmail,
                'subject'   => $subject,
                'messageId' => $messageId,
            ]);

            return [
                'success'   => true,
                'messageId' => $messageId,
                'error'     => null,
            ];
        } catch (\Exception $e) {
            $this->logger->error('CRM email send failed', [
                'to'    => $toEmail,
                'error' => $e->getMessage(),
            ]);

            return [
                'success'   => false,
                'messageId' => null,
                'error'     => $e->getMessage(),
            ];
        }
    }

    /**
     * Envoie un email à une adresse sans contact CRM associé.
     *
     * @return array{success: bool, messageId: ?string, error: ?string}
     */
    public function sendToAddress(
        string $toEmail,
        string $toName,
        string $subject,
        string $body,
        bool $isHtml = false
    ): array {
        try {
            $email = (new Email())
                ->from(sprintf('%s <%s>', self::FROM_NAME, self::FROM_EMAIL))
                ->to(sprintf('%s <%s>', $toName, $toEmail))
                ->subject($subject);

            if ($isHtml) {
                $email->html($body);
            } else {
                $email->text($body);
            }

            $this->mailer->send($email);

            $messageId = 'crm-' . uniqid('', true) . '@crm';

            $this->logger->info('CRM email sent (no contact)', [
                'to'        => $toEmail,
                'subject'   => $subject,
                'messageId' => $messageId,
            ]);

            return [
                'success'   => true,
                'messageId' => $messageId,
                'error'     => null,
            ];
        } catch (\Exception $e) {
            $this->logger->error('CRM email send failed', [
                'to'    => $toEmail,
                'error' => $e->getMessage(),
            ]);

            return [
                'success'   => false,
                'messageId' => null,
                'error'     => $e->getMessage(),
            ];
        }
    }

    /**
     * Parse les métadonnées d'un email entrant (webhook).
     */
    public function parseInboundWebhook(array $payload): array
    {
        return [
            'from'      => $payload['from'] ?? $payload['sender'] ?? '',
            'to'        => $payload['to'] ?? $payload['recipient'] ?? '',
            'subject'   => $payload['subject'] ?? '(Sans objet)',
            'body'      => $payload['body-plain'] ?? $payload['text'] ?? $payload['body'] ?? '',
            'bodyHtml'  => $payload['body-html'] ?? $payload['html'] ?? null,
            'messageId' => $payload['Message-Id'] ?? $payload['message-id'] ?? null,
            'timestamp' => $payload['timestamp'] ?? time(),
        ];
    }

    private function getContactName(Contact $contact): string
    {
        // Chercher le nom dans les propriétés
        $props = $contact->getProperties();
        $firstName = '';
        $lastName  = '';

        foreach ($props as $prop) {
            if ($prop->getKey() === 'first_name') {
                $firstName = $prop->getValue() ?? '';
            }
            if ($prop->getKey() === 'last_name') {
                $lastName = $prop->getValue() ?? '';
            }
        }

        $name = trim($firstName . ' ' . $lastName);
        return $name ?: 'Contact';
    }
}
