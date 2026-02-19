<?php

/**
 * Test d'envoi direct sans cache ni framework
 */

use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;

require __DIR__.'/vendor/autoload.php';

echo "=== Test d'envoi direct Gmail ===\n\n";

try {
    // Configuration directe
    $transport = new EsmtpTransport('smtp.gmail.com', 587);
    $transport->setUsername('wilfriedhouinlindjonon91@gmail.com');
    $transport->setPassword('qxpqpqveenbhyohd');

    $mailer = new Mailer($transport);

    $email = (new Email())
        ->from('wilfriedhouinlindjonon91@gmail.com')
        ->to('alainwill91@gmail.com')
        ->subject('🔥 Test Direct Gmail - ' . date('H:i:s'))
        ->html('
            <h1>Test Direct Gmail</h1>
            <p>Cet email a été envoyé directement sans passer par le service Symfony.</p>
            <p><strong>Heure:</strong> ' . date('d/m/Y à H:i:s') . '</p>
            <p><strong>Code de test:</strong> <span style="font-size: 24px; color: blue;">987654</span></p>
        ')
        ->text('Test direct Gmail - Code: 987654');

    echo "📧 Envoi en cours vers alainwill91@gmail.com...\n";

    $mailer->send($email);

    echo "✅ Email envoyé avec succès !\n";
    echo "📬 Vérifiez votre boîte Gmail maintenant.\n\n";

} catch (\Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    exit(1);
}
