# Configuration de l'envoi d'emails avec OTP

## Vue d'ensemble

Le système utilise Symfony Mailer pour envoyer des codes OTP (One-Time Password) par email pour la vérification des comptes.

## Configuration

### 1. Configurer le MAILER_DSN dans `.env`

Le `MAILER_DSN` définit comment les emails seront envoyés. Voici les options courantes :

#### Option 1 : Gmail (Recommandé pour le développement)

```env
MAILER_DSN=gmail+smtp://votre-email@gmail.com:votre-mot-de-passe-app@default
```

**Note importante pour Gmail:**
- Vous devez créer un **mot de passe d'application** (App Password) depuis votre compte Google
- Allez sur : https://myaccount.google.com/apppasswords
- Créez un nouveau mot de passe d'application pour "Mail"
- Utilisez ce mot de passe généré dans le MAILER_DSN

#### Option 2 : SMTP Générique

```env
MAILER_DSN=smtp://username:password@smtp.example.com:587
```

#### Option 3 : Mailtrap (Idéal pour les tests)

```env
MAILER_DSN=smtp://username:password@smtp.mailtrap.io:2525
```

Mailtrap capture tous les emails envoyés sans les délivrer réellement. Parfait pour le développement !
- Créez un compte sur https://mailtrap.io
- Copiez vos identifiants SMTP

#### Option 4 : Sendgrid

```env
MAILER_DSN=sendgrid://API_KEY@default
```

#### Option 5 : Mailgun

```env
MAILER_DSN=mailgun://API_KEY:DOMAIN@default
```

#### Option 6 : Mode Null (Développement - Aucun email envoyé)

```env
MAILER_DSN=null://null
```

### 2. Configurer les paramètres de l'expéditeur

Dans `/config/services.yaml`, vous pouvez configurer :

```yaml
parameters:
    app.mail.sender_email: 'noreply@crm-api.com'
    app.mail.sender_name: 'CRM API'
```

Ou directement dans le service EmailService si vous préférez.

## Endpoints API disponibles

### 1. Inscription avec OTP

**POST** `/auth/register`

```json
{
    "email": "user@example.com",
    "password": "SecurePassword123!",
    "firstName": "John",
    "lastName": "Doe"
}
```

**Réponse:**
```json
{
    "status": "success",
    "message": "User registered successfully. Please check your email for the verification code.",
    "data": {
        "userId": 1,
        "email": "user@example.com",
        "otpCode": "123456"  // Retirez ceci en production!
    }
}
```

### 2. Vérifier l'email avec OTP

**POST** `/auth/verify-email-otp`

```json
{
    "email": "user@example.com",
    "code": "123456"
}
```

**Réponse:**
```json
{
    "status": "success",
    "message": "Email verified successfully. You can now log in.",
    "data": {
        "userId": 1,
        "email": "user@example.com"
    }
}
```

### 3. Renvoyer le code OTP

**POST** `/auth/resend-otp`

```json
{
    "email": "user@example.com"
}
```

**Réponse:**
```json
{
    "status": "success",
    "message": "Verification code sent successfully.",
    "data": {
        "otpCode": "789012"  // Retirez ceci en production!
    }
}
```

## Caractéristiques de sécurité

### 1. Expiration du code
- Les codes OTP expirent après **10 minutes**
- Un nouveau code doit être demandé si le délai est dépassé

### 2. Limitation des tentatives
- Maximum **5 tentatives** par code OTP
- Le code est invalide après 5 échecs

### 3. Rate limiting
- Maximum **10 tentatives de vérification** en 15 minutes par IP
- Maximum **5 demandes de nouveau code** en 15 minutes par IP

### 4. Sécurité des codes
- Codes à 6 chiffres générés aléatoirement
- Chaque code est unique et lié à un email spécifique
- Les anciens codes sont automatiquement invalidés lors d'une nouvelle demande

## Template d'email

L'email OTP contient :
- Un header visuel attrayant avec gradient
- Le code OTP dans un format facile à lire (grandes lettres espacées)
- Le temps d'expiration
- Un avertissement de sécurité
- Un footer professionnel

## Test en développement

### Avec Mailtrap (Recommandé)

1. Créez un compte sur https://mailtrap.io
2. Copiez vos identifiants SMTP
3. Configurez le MAILER_DSN:
   ```env
   MAILER_DSN=smtp://[username]:[password]@smtp.mailtrap.io:2525
   ```
4. Tous les emails seront capturés dans votre inbox Mailtrap

### Avec le mode Null (Tests unitaires)

```env
MAILER_DSN=null://null
```

Les emails ne seront pas envoyés, mais le code OTP sera visible dans la réponse JSON (en mode développement uniquement).

## Production

### Points importants pour la production :

1. **Retirez le code OTP de la réponse JSON**
   - Dans `AuthController::register()` ligne 136
   - Dans `AuthController::resendOtp()` ligne 359

2. **Configurez un vrai service d'envoi d'emails**
   - Utilisez un service professionnel (Sendgrid, Mailgun, Amazon SES)
   - Ne jamais utiliser Gmail en production

3. **Configurez les logs**
   - Surveillez les échecs d'envoi d'emails
   - Mettez en place des alertes pour les problèmes

4. **Monitoring**
   - Surveillez le taux de vérification des emails
   - Détectez les tentatives de fraude (trop de codes demandés)

## Commande de nettoyage

Pour nettoyer les OTP expirés depuis plus de 7 jours :

```php
// Créez une commande Symfony ou exécutez via un cron job
$this->emailOtpRepository->cleanExpiredOtps(7);
```

## Dépannage

### Les emails ne sont pas envoyés

1. Vérifiez le MAILER_DSN dans `.env`
2. Vérifiez les logs Symfony : `var/log/dev.log`
3. Testez la connexion SMTP manuellement
4. Vérifiez que le pare-feu n'bloque pas le port SMTP (587 ou 465)

### Les emails arrivent en spam

1. Configurez SPF, DKIM et DMARC pour votre domaine
2. Utilisez un service professionnel avec bonne réputation
3. Assurez-vous que l'adresse d'expéditeur existe réellement

### Rate limiting trop strict

Modifiez les limites dans `AuthController` :
- Ligne 166 : `countRecentAttempts($request->getClientIp() ?? 'unknown', 15)` - temps en minutes
- Ligne 167 : `if ($recentAttempts > 10)` - nombre maximum de tentatives

## Support

Pour toute question, consultez la documentation Symfony Mailer :
https://symfony.com/doc/current/mailer.html
