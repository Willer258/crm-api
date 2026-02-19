<?php

/**
 * Script de test direct pour Gmail (sans cache)
 * Usage: php test-gmail-direct.php
 */

require __DIR__.'/vendor/autoload.php';

use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;

echo "=== Test de configuration Gmail ===\n\n";

// Lire directement le fichier .env
$envFile = __DIR__.'/.env';
if (!file_exists($envFile)) {
    echo "❌ Fichier .env non trouvé\n";
    exit(1);
}

$envContent = file_get_contents($envFile);
preg_match('/MAILER_DSN=(.+)/', $envContent, $matches);
$dsn = trim($matches[1] ?? '');

echo "MAILER_DSN trouvé : " . ($dsn ? "✓" : "❌") . "\n";

if (empty($dsn) || strpos($dsn, 'VOTRE_EMAIL') !== false) {
    echo "\n❌ ERREUR: Vous devez d'abord configurer votre email et mot de passe dans le fichier .env\n";
    exit(1);
}

echo "DSN: $dsn\n";

// Extraire l'email
preg_match('/smtp:\/\/([^:]+)/', $dsn, $matches);
$fromEmail = $matches[1] ?? '';

if (empty($fromEmail) || strpos($fromEmail, '@') === false) {
    echo "\n❌ ERREUR: Impossible d'extraire l'email du DSN\n";
    exit(1);
}

echo "Email détecté: $fromEmail\n";
echo "\n--- Tentative d'envoi d'un email de test ---\n";

try {
    // Créer le transport
    $transport = Transport::fromDsn($dsn);
    $mailer = new Mailer($transport);

    // Créer l'email
    $email = (new Email())
        ->from($fromEmail)
        ->to($fromEmail) // Envoyer à soi-même pour le test
        ->subject('✅ Test Gmail CRM API - Succès!')
        ->text('Félicitations ! Votre configuration Gmail fonctionne parfaitement. Vous pouvez maintenant envoyer des emails depuis votre CRM.')
        ->html('
            <h2>✅ Configuration Gmail réussie!</h2>
            <p>Félicitations ! Votre configuration Gmail fonctionne parfaitement.</p>
            <p>Vous pouvez maintenant envoyer des emails depuis votre CRM API.</p>
            <hr>
            <p><small>Cet email a été envoyé le ' . date('d/m/Y à H:i:s') . '</small></p>
        ');

    echo "📧 Envoi d'un email de test à : $fromEmail\n";

    // Envoyer l'email
    $mailer->send($email);

    echo "\n✅ Email envoyé avec succès !\n";
    echo "🎉 Vérifiez votre boîte de réception Gmail : $fromEmail\n";
    echo "\nVotre configuration est opérationnelle.\n\n";

} catch (\Exception $e) {
    echo "\n❌ ERREUR lors de l'envoi de l'email:\n";
    echo $e->getMessage() . "\n\n";

    // Messages d'erreur spécifiques
    $errorMsg = $e->getMessage();

    if (strpos($errorMsg, 'Username and Password not accepted') !== false) {
        echo "🔐 Problème d'authentification détecté.\n\n";
        echo "Solutions:\n";
        echo "1. Vérifiez que votre mot de passe est correct dans .env\n";
        echo "2. Activez 'Accès moins sécurisé' sur Gmail:\n";
        echo "   👉 https://myaccount.google.com/lesssecureapps\n\n";
        echo "3. OU (RECOMMANDÉ) Utilisez un App Password:\n";
        echo "   a) Activez la validation en 2 étapes sur votre compte Google\n";
        echo "   b) Générez un App Password:\n";
        echo "      👉 https://myaccount.google.com/apppasswords\n";
        echo "   c) Remplacez votre mot de passe dans .env par l'App Password\n\n";
    } elseif (strpos($errorMsg, 'Could not authenticate') !== false) {
        echo "🔐 Échec d'authentification.\n\n";
        echo "Si vous avez la validation en 2 étapes:\n";
        echo "👉 Vous DEVEZ utiliser un App Password\n";
        echo "👉 https://myaccount.google.com/apppasswords\n\n";
    } elseif (strpos($errorMsg, 'Connection') !== false) {
        echo "🌐 Problème de connexion.\n\n";
        echo "Solutions:\n";
        echo "1. Vérifiez votre connexion internet\n";
        echo "2. Vérifiez que le port 587 n'est pas bloqué\n";
        echo "3. Essayez avec le port 465:\n";
        echo "   MAILER_DSN=smtp://$fromEmail:PASSWORD@smtp.gmail.com:465?encryption=ssl\n\n";
    }

    exit(1);
}
