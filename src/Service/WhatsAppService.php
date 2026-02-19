<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Service d'intégration Evolution API pour WhatsApp Business.
 *
 * Evolution API docs: https://doc.evolution-api.com
 * Instance par défaut: localhost:8080
 */
class WhatsAppService
{
    private string $baseUrl;
    private string $instanceName;
    private string $apiKey;

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        string $evolutionApiUrl = 'http://localhost:8080',
        string $evolutionInstanceName = 'crm-instance',
        string $evolutionApiKey = ''
    ) {
        $this->baseUrl      = rtrim($evolutionApiUrl, '/');
        $this->instanceName = $evolutionInstanceName;
        $this->apiKey       = $evolutionApiKey;
    }

    /**
     * Envoie un message texte WhatsApp à un numéro de téléphone.
     *
     * @param string $phone   Numéro au format international sans + (ex: 2250102030405)
     * @param string $message Contenu du message
     *
     * @return array{success: bool, messageId: ?string, error: ?string}
     */
    public function sendTextMessage(string $phone, string $message): array
    {
        $phone = $this->normalizePhone($phone);

        $url = sprintf('%s/message/sendText/%s', $this->baseUrl, $this->instanceName);

        try {
            $response = $this->httpClient->request('POST', $url, [
                'headers' => [
                    'apikey'       => $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'number'  => $phone,
                    'options' => ['delay' => 1200],
                    'textMessage' => ['text' => $message],
                ],
                'timeout' => 30,
            ]);

            $data = $response->toArray();

            $this->logger->info('WhatsApp message sent', [
                'phone'     => $phone,
                'messageId' => $data['key']['id'] ?? null,
            ]);

            return [
                'success'   => true,
                'messageId' => $data['key']['id'] ?? null,
                'status'    => $data['status'] ?? 'sent',
                'error'     => null,
            ];
        } catch (\Exception $e) {
            $this->logger->error('WhatsApp send failed', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            return [
                'success'   => false,
                'messageId' => null,
                'status'    => 'failed',
                'error'     => $e->getMessage(),
            ];
        }
    }

    /**
     * Parse un webhook entrant d'Evolution API.
     *
     * @param array $payload Payload JSON du webhook
     *
     * @return array{
     *   event: string,
     *   instanceName: string,
     *   messageId: ?string,
     *   from: ?string,
     *   to: ?string,
     *   body: ?string,
     *   timestamp: ?int,
     *   isGroup: bool
     * }
     */
    public function parseWebhook(array $payload): array
    {
        $event        = $payload['event'] ?? '';
        $instanceName = $payload['instance'] ?? '';
        $data         = $payload['data'] ?? [];

        $key  = $data['key'] ?? [];
        $msg  = $data['message'] ?? [];

        // Contenu du message texte
        $body = $msg['conversation']
            ?? $msg['extendedTextMessage']['text']
            ?? $msg['imageMessage']['caption']
            ?? null;

        // Numéro de l'expéditeur (format: 2250101010101@s.whatsapp.net)
        $remoteJid = $key['remoteJid'] ?? '';
        $from      = str_replace('@s.whatsapp.net', '', $remoteJid);

        return [
            'event'        => $event,
            'instanceName' => $instanceName,
            'messageId'    => $key['id'] ?? null,
            'from'         => $from,
            'to'           => $this->instanceName,
            'body'         => $body,
            'timestamp'    => $data['messageTimestamp'] ?? null,
            'isGroup'      => str_contains($remoteJid, '@g.us'),
            'raw'          => $data,
        ];
    }

    /**
     * Vérifie le statut de l'instance Evolution API.
     */
    public function getInstanceStatus(): array
    {
        $url = sprintf('%s/instance/connectionState/%s', $this->baseUrl, $this->instanceName);

        try {
            $response = $this->httpClient->request('GET', $url, [
                'headers' => ['apikey' => $this->apiKey],
                'timeout' => 10,
            ]);

            return $response->toArray();
        } catch (\Exception $e) {
            return ['state' => 'disconnected', 'error' => $e->getMessage()];
        }
    }

    /**
     * Normalise un numéro de téléphone pour Evolution API.
     * Retire +, espaces, tirets.
     */
    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Ajouter le code pays par défaut (225 = Côte d'Ivoire) si numéro court
        if (strlen($phone) <= 9) {
            $phone = '225' . $phone;
        }

        return $phone;
    }
}
