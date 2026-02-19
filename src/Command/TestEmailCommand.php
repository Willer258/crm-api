<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsCommand(
    name: 'app:test-email',
    description: 'Test email configuration and send a test email',
)]
class TestEmailCommand extends Command
{
    public function __construct(
        private MailerInterface $mailer
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('📧 Email Configuration Test');

        // 1. Check MAILER_DSN
        $io->section('1. Checking MAILER_DSN');
        $mailerDsn = $_ENV['MAILER_DSN'] ?? 'NOT SET';

        if ($mailerDsn === 'null://null' || $mailerDsn === 'NOT SET') {
            $io->error('MAILER_DSN is not configured correctly!');
            $io->text('Current value: ' . $mailerDsn);
            $io->text('Please set MAILER_DSN in your .env.local file');
            return Command::FAILURE;
        }

        $io->success('MAILER_DSN is configured');
        $io->text('Value: ' . $this->maskDsn($mailerDsn));

        // 2. Test SMTP connection
        $io->section('2. Testing SMTP Connection');

        if (preg_match('/smtp:\/\/(.+):(.+)@(.+):(\d+)/', $mailerDsn, $matches)) {
            $host = $matches[3];
            $port = (int)$matches[4];

            $io->text("Connecting to $host:$port...");

            $errno = 0;
            $errstr = '';
            $socket = @fsockopen($host, $port, $errno, $errstr, 10);

            if (!$socket) {
                $io->error("Failed to connect: $errstr ($errno)");
                return Command::FAILURE;
            }

            $response = fgets($socket);
            fclose($socket);

            $io->success('SMTP connection successful');
            $io->text('Server response: ' . trim($response));
        }

        // 3. Send test email
        $io->section('3. Sending Test Email');

        $testEmail = $io->ask('Enter test email address', 'test@example.com');

        try {
            $email = (new Email())
                ->from('hello@demomailtrap.com')
                ->to($testEmail)
                ->subject('✅ Test Email from CRM API')
                ->html($this->getTestEmailTemplate());

            $this->mailer->send($email);

            $io->success('Test email sent successfully!');
            $io->text('Check your inbox at: ' . $testEmail);

            if (str_contains($mailerDsn, 'mailtrap')) {
                $io->note('Using Mailtrap - Check your inbox at https://mailtrap.io/inboxes');
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Failed to send email: ' . $e->getMessage());
            $io->text('Error details: ' . $e->getTraceAsString());
            return Command::FAILURE;
        }
    }

    private function maskDsn(string $dsn): string
    {
        return preg_replace('/(:\/\/)(.+):(.+)(@)/', '$1***:***$4', $dsn);
    }

    private function getTestEmailTemplate(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .content {
            background: #f9f9f9;
            padding: 30px;
            border-radius: 0 0 10px 10px;
        }
        .success {
            background: #d4edda;
            border-left: 4px solid #28a745;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>✅ Email Test Successful</h1>
    </div>
    <div class="content">
        <p>Bonjour,</p>

        <div class="success">
            ✅ <strong>Félicitations!</strong> Votre configuration email fonctionne correctement!
        </div>

        <p>Ce test confirme que:</p>
        <ul>
            <li>La connexion SMTP est fonctionnelle</li>
            <li>Les emails peuvent être envoyés</li>
            <li>Le système OTP est prêt à l'emploi</li>
        </ul>

        <p>Vous pouvez maintenant utiliser le système de vérification par email OTP en toute confiance.</p>

        <p>Cordialement,<br>L'équipe CRM API</p>
    </div>
</body>
</html>
HTML;
    }
}
