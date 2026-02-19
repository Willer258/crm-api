# Configuration Gmail pour l'envoi d'emails

Ce guide vous explique comment configurer Gmail pour l'envoi d'emails depuis l'application CRM.

## Étape 1 : Modifier le fichier .env

Ouvrez le fichier `.env` à la racine du projet et modifiez la ligne `MAILER_DSN` :

```env
MAILER_DSN=smtp://votre.email@gmail.com:votre-mot-de-passe@smtp.gmail.com:587
```

**Remplacez :**
- `votre.email@gmail.com` par votre adresse Gmail complète
- `votre-mot-de-passe` par votre mot de passe Gmail

**Exemple :**
```env
MAILER_DSN=smtp://john.doe@gmail.com:monMotDePasse123@smtp.gmail.com:587
```

## Étape 2 : Configurer votre compte Gmail

### Option A : Activer "Accès moins sécurisé" (Simple mais moins sécurisé)

1. Allez sur [https://myaccount.google.com/lesssecureapps](https://myaccount.google.com/lesssecureapps)
2. Activez l'option "Autoriser les applications moins sécurisées"
3. Attendez quelques minutes que les modifications prennent effet

**⚠️ Note :** Cette option n'est disponible que si vous n'avez PAS activé la validation en 2 étapes.

### Option B : Utiliser un App Password (Recommandé - Plus sécurisé)

Si vous avez activé la validation en 2 étapes (ou si vous voulez plus de sécurité) :

1. Allez sur [https://myaccount.google.com/apppasswords](https://myaccount.google.com/apppasswords)
2. Connectez-vous si nécessaire
3. Sélectionnez "Autre (nom personnalisé)" dans le menu déroulant
4. Entrez un nom (par exemple : "CRM API")
5. Cliquez sur "Générer"
6. Copiez le mot de passe généré (16 caractères)
7. Utilisez ce mot de passe dans le `MAILER_DSN` au lieu de votre mot de passe Gmail

**Exemple avec App Password :**
```env
MAILER_DSN=smtp://john.doe@gmail.com:abcd efgh ijkl mnop@smtp.gmail.com:587
```

**Note :** Vous pouvez supprimer les espaces dans l'App Password :
```env
MAILER_DSN=smtp://john.doe@gmail.com:abcdefghijklmnop@smtp.gmail.com:587
```

## Étape 3 : Tester la configuration

Exécutez le script de test fourni :

```bash
php test-gmail.php
```

Si tout fonctionne, vous devriez voir :
```
✓ Email envoyé avec succès !
Vérifiez votre boîte de réception Gmail.
```

Vous recevrez un email de test dans votre boîte Gmail.

## Étape 4 : Configurer l'adresse d'expédition (Optionnel)

Si vous souhaitez personnaliser l'adresse et le nom de l'expéditeur, vous pouvez le faire dans vos services.

Dans [src/Service/EmailService.php](../src/Service/EmailService.php), les emails sont envoyés avec l'adresse configurée dans le DSN par défaut.

## Résolution de problèmes

### Erreur : "Username and Password not accepted"

**Solutions :**
1. Vérifiez que votre email et mot de passe sont corrects
2. Activez "Accès moins sécurisé" (voir Option A ci-dessus)
3. Ou utilisez un App Password (voir Option B ci-dessus)

### Erreur : "Could not authenticate"

**Solutions :**
1. Si vous avez la validation en 2 étapes activée, vous DEVEZ utiliser un App Password
2. Allez sur [https://myaccount.google.com/apppasswords](https://myaccount.google.com/apppasswords)
3. Générez un nouveau App Password
4. Utilisez-le dans le MAILER_DSN

### Erreur : "Connection could not be established"

**Solutions :**
1. Vérifiez votre connexion internet
2. Vérifiez que le port 587 n'est pas bloqué par votre firewall
3. Essayez avec le port 465 et SSL :
   ```env
   MAILER_DSN=smtp://votre.email@gmail.com:password@smtp.gmail.com:465?encryption=ssl
   ```

### Gmail bloque l'envoi ou marque comme "activité suspecte"

**Solutions :**
1. Allez sur [https://accounts.google.com/DisplayUnlockCaptcha](https://accounts.google.com/DisplayUnlockCaptcha)
2. Cliquez sur "Continuer"
3. Réessayez d'envoyer l'email de test

## Configuration avancée

### Utiliser un alias Gmail

Si vous avez configuré des alias dans Gmail, vous pouvez les utiliser :

```env
MAILER_DSN=smtp://votre.alias@votredomaine.com:app-password@smtp.gmail.com:587
```

**Note :** Vous devez quand même utiliser le mot de passe de votre compte Gmail principal (ou l'App Password).

### Forcer TLS

Pour forcer l'utilisation de TLS :

```env
MAILER_DSN=smtp://votre.email@gmail.com:password@smtp.gmail.com:587?encryption=tls
```

### Utiliser SSL sur le port 465

```env
MAILER_DSN=smtp://votre.email@gmail.com:password@smtp.gmail.com:465?encryption=ssl
```

## Limites de Gmail

Gmail impose certaines limites :

- **Maximum 500 emails par jour** pour les comptes Gmail gratuits
- **Maximum 2000 emails par jour** pour les comptes Google Workspace
- **Maximum 100 destinataires par email**

Si vous dépassez ces limites, envisagez d'utiliser un service professionnel comme :
- SendGrid
- Mailgun
- Amazon SES
- Postmark

## Utilisation dans le code

Une fois configuré, votre EmailService utilisera automatiquement Gmail :

```php
$this->emailService->sendVerificationEmail($user, $token);
$this->emailService->sendPasswordResetEmail($user, $token);
$this->emailService->sendOtpEmail($user, $otp);
```

## Sécurité

**Important :**
- Ne commitez JAMAIS le fichier `.env` avec vos identifiants
- Le fichier `.env` est déjà dans `.gitignore`
- Utilisez `.env.local` pour vos configurations locales
- En production, utilisez des variables d'environnement système plutôt que le fichier `.env`
- Préférez toujours les App Passwords aux mots de passe Gmail classiques

## Support

Si vous rencontrez des problèmes :
1. Exécutez `php test-gmail.php` pour diagnostiquer
2. Vérifiez les logs Symfony : `var/log/dev.log`
3. Consultez la documentation Symfony Mailer : [https://symfony.com/doc/current/mailer.html](https://symfony.com/doc/current/mailer.html)
