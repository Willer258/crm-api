<?php

/**
 * Envoi d'un email de test à une adresse spécifique
 * Usage: php send-test-email.php
 */

require __DIR__.'/vendor/autoload.php';

use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;

echo "=== Envoi d'un email de test ===\n\n";

// Lire directement le fichier .env
$envFile = __DIR__.'/.env';
if (!file_exists($envFile)) {
    echo "❌ Fichier .env non trouvé\n";
    exit(1);
}

$envContent = file_get_contents($envFile);
preg_match('/MAILER_DSN=(.+)/', $envContent, $matches);
$dsn = trim($matches[1] ?? '');

if (empty($dsn)) {
    echo "❌ MAILER_DSN non configuré\n";
    exit(1);
}

// Extraire l'email expéditeur
preg_match('/smtp:\/\/([^:]+)/', $dsn, $matches);
$fromEmail = $matches[1] ?? '';

echo "Expéditeur: $fromEmail\n";
echo "Destinataire: alainwill91@gmail.com\n\n";

try {
    // Créer le transport
    $transport = Transport::fromDsn($dsn);
    $mailer = new Mailer($transport);

    // Créer l'email
    $email = (new Email())
        ->from($fromEmail)
        ->to('alainwill91@gmail.com')
        ->subject('🚀 Test CRM API - Configuration Gmail réussie!')
        ->text('Bonjour,

Ceci est un email de test envoyé depuis le CRM API.

La configuration Gmail fonctionne parfaitement !

Détails techniques :
- Envoyé depuis : ' . $fromEmail . '
- Date : ' . date('d/m/Y à H:i:s') . '
- Serveur SMTP : smtp.gmail.com:587
- Sécurité : TLS

Cordialement,
CRM API System')
        ->html('
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
        .success-badge { background: #10b981; color: white; padding: 10px 20px; border-radius: 20px; display: inline-block; margin: 20px 0; }
        .info-box { background: white; padding: 20px; border-left: 4px solid #667eea; margin: 20px 0; }
        .footer { text-align: center; margin-top: 30px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 Test CRM API</h1>
            <p style="margin: 0; font-size: 18px;">Configuration Gmail réussie!</p>
        </div>
        <div class="content">
            <div class="success-badge">✅ Configuration Opérationnelle</div>

            <p>Bonjour,</p>

            <p>Ceci est un email de test envoyé depuis le <strong>CRM API</strong>.</p>

            <p>La configuration Gmail fonctionne parfaitement ! 🎉</p>

            <div class="info-box">
                <h3 style="margin-top: 0;">📋 Détails techniques</h3>
                <ul style="margin: 0; padding-left: 20px;">
                    <li><strong>Expéditeur:</strong> ' . htmlspecialchars($fromEmail) . '</li>
                    <li><strong>Date:</strong> ' . date('d/m/Y à H:i:s') . '</li>
                    <li><strong>Serveur SMTP:</strong> smtp.gmail.com:587</li>
                    <li><strong>Sécurité:</strong> TLS avec App Password</li>
                </ul>
            </div>

            <p>Les fonctionnalités suivantes sont maintenant actives :</p>
            <ul>
                <li>✉️ Envoi d\'emails de vérification</li>
                <li>🔐 Réinitialisation de mot de passe</li>
                <li>🔢 Envoi d\'OTP</li>
                <li>📧 Notifications personnalisées</li>
            </ul>

            <p>Cordialement,<br>
            <strong>CRM API System</strong></p>
        </div>
        <div class="footer">
            <p>Cet email a été généré automatiquement par le système CRM API</p>
        </div>
    </div>
</body>
</html>
        ');

    echo "📧 Envoi de l'email en cours...\n";

    // Envoyer l'email
    $mailer->send($email);

    echo "\n✅ Email envoyé avec succès à alainwill91@gmail.com !\n";
    echo "📬 Vérifiez la boîte de réception (et les spams si besoin).\n\n";

} catch (\Exception $e) {
    echo "\n❌ ERREUR lors de l'envoi:\n";
    echo $e->getMessage() . "\n\n";
    exit(1);
}
