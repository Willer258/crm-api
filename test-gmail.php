<?php

/**
 * Script de test pour la configuration Gmail
 * Usage: php test-gmail.php
 */

require __DIR__.'/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;

// Charger les variables d'environnement
$dotenv = new Dotenv();
$dotenv->load(__DIR__.'/.env');

echo "=== Test de configuration Gmail ===\n\n";

// Récupérer le DSN
$dsn = $_ENV['MAILER_DSN'] ?? '';
echo "MAILER_DSN configuré : " . (empty($dsn) ? "❌ Non configuré" : "✓ Configuré") . "\n";

if (empty($dsn) || strpos($dsn, 'VOTRE_EMAIL') !== false) {
    echo "\n❌ ERREUR: Vous devez d'abord configurer votre email et mot de passe dans le fichier .env\n";
    echo "Modifiez la ligne MAILER_DSN avec vos identifiants Gmail.\n\n";
    exit(1);
}

echo "\n--- Tentative d'envoi d'un email de test ---\n";

try {
    // Créer le transport
    $transport = Transport::fromDsn($dsn);
    $mailer = new Mailer($transport);

    // Extraire l'email de l'expéditeur du DSN
    // Format: smtp://email:password@host:port
    preg_match('/smtp:\/\/([^:]+)/', $dsn, $matches);
    $fromEmail = $matches[1] ?? 'noreply@example.com';

    // Créer l'email
    $email = (new Email())
        ->from($fromEmail)
        ->to($fromEmail) // Envoyer à soi-même pour le test
        ->subject('Test de configuration Gmail - CRM API')
        ->text('Félicitations ! Votre configuration Gmail fonctionne correctement.')
        ->html('<p>Félicitations ! Votre configuration Gmail fonctionne correctement.</p>');

    echo "Envoi d'un email de test à : $fromEmail\n";

    // Envoyer l'email
    $mailer->send($email);

    echo "\n✓ Email envoyé avec succès !\n";
    echo "Vérifiez votre boîte de réception Gmail.\n\n";

} catch (\Exception $e) {
    echo "\n❌ ERREUR lors de l'envoi de l'email:\n";
    echo $e->getMessage() . "\n\n";

    echo "Solutions possibles :\n";
    echo "1. Vérifiez que votre email et mot de passe sont corrects dans .env\n";
    echo "2. Activez 'Accès moins sécurisé' sur votre compte Gmail:\n";
    echo "   https://myaccount.google.com/lesssecureapps\n";
    echo "3. OU utilisez un App Password (recommandé):\n";
    echo "   - Activez la validation en 2 étapes\n";
    echo "   - Générez un App Password sur https://myaccount.google.com/apppasswords\n";
    echo "   - Utilisez ce mot de passe dans le MAILER_DSN\n\n";

    exit(1);
}
