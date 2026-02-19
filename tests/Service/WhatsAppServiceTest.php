<?php

namespace App\Tests\Service;

use App\Service\WhatsAppService;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class WhatsAppServiceTest extends TestCase
{
    private WhatsAppService $service;

    protected function setUp(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);

        $this->service = new WhatsAppService(
            httpClient: $httpClient,
            logger: new NullLogger(),
            evolutionApiUrl: 'http://localhost:8080',
            evolutionInstanceName: 'test-instance',
            evolutionApiKey: 'test-key'
        );
    }

    public function testParseWebhookMessageUpsert(): void
    {
        $payload = [
            'event'    => 'messages.upsert',
            'instance' => 'test-instance',
            'data'     => [
                'key' => [
                    'id'        => 'ABCD1234',
                    'remoteJid' => '2250102030405@s.whatsapp.net',
                    'fromMe'    => false,
                ],
                'message' => [
                    'conversation' => 'Bonjour, je suis intéressé par vos services.',
                ],
                'messageTimestamp' => 1708300000,
            ],
        ];

        $parsed = $this->service->parseWebhook($payload);

        $this->assertEquals('messages.upsert', $parsed['event']);
        $this->assertEquals('ABCD1234', $parsed['messageId']);
        $this->assertEquals('2250102030405', $parsed['from']);
        $this->assertEquals('Bonjour, je suis intéressé par vos services.', $parsed['body']);
        $this->assertFalse($parsed['isGroup']);
    }

    public function testParseWebhookIgnoresGroupMessages(): void
    {
        $payload = [
            'event'    => 'messages.upsert',
            'instance' => 'test-instance',
            'data'     => [
                'key' => [
                    'id'        => 'GROUP_MSG_001',
                    'remoteJid' => '120363123456789@g.us',
                    'fromMe'    => false,
                ],
                'message' => [
                    'conversation' => 'Message de groupe',
                ],
                'messageTimestamp' => 1708300000,
            ],
        ];

        $parsed = $this->service->parseWebhook($payload);

        $this->assertTrue($parsed['isGroup']);
    }

    public function testParseWebhookExtendedTextMessage(): void
    {
        $payload = [
            'event'    => 'messages.upsert',
            'instance' => 'test-instance',
            'data'     => [
                'key' => [
                    'id'        => 'EXT1234',
                    'remoteJid' => '2250102030405@s.whatsapp.net',
                ],
                'message' => [
                    'extendedTextMessage' => [
                        'text' => 'Message étendu avec lien',
                    ],
                ],
                'messageTimestamp' => 1708300000,
            ],
        ];

        $parsed = $this->service->parseWebhook($payload);

        $this->assertEquals('Message étendu avec lien', $parsed['body']);
    }

    public function testSendTextMessageSuccess(): void
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('toArray')->willReturn([
            'key'    => ['id' => 'SENT_MSG_001'],
            'status' => 'sent',
        ]);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->once())
            ->method('request')
            ->with('POST', 'http://localhost:8080/message/sendText/test-instance')
            ->willReturn($mockResponse);

        $service = new WhatsAppService(
            httpClient: $httpClient,
            logger: new NullLogger(),
            evolutionApiUrl: 'http://localhost:8080',
            evolutionInstanceName: 'test-instance',
            evolutionApiKey: 'test-key'
        );

        $result = $service->sendTextMessage('+2250102030405', 'Test message');

        $this->assertTrue($result['success']);
        $this->assertEquals('SENT_MSG_001', $result['messageId']);
        $this->assertNull($result['error']);
    }

    public function testSendTextMessageFailure(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('request')
            ->willThrowException(new \RuntimeException('Connection refused'));

        $service = new WhatsAppService(
            httpClient: $httpClient,
            logger: new NullLogger(),
            evolutionApiUrl: 'http://localhost:8080',
            evolutionInstanceName: 'test-instance',
            evolutionApiKey: 'test-key'
        );

        $result = $service->sendTextMessage('2250102030405', 'Test message');

        $this->assertFalse($result['success']);
        $this->assertNull($result['messageId']);
        $this->assertEquals('failed', $result['status']);
        $this->assertNotEmpty($result['error']);
    }
}
