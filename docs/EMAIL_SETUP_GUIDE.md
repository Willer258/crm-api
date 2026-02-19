# Guide Complet de Configuration Email - CRM API

## 🎯 Problème Résolu

Le système d'envoi d'emails ne fonctionnait pas car le `MAILER_DSN` était configuré à `null://null` dans le fichier `.env.local`, ce qui overridait la configuration correcte du `.env`.

## ✅ Solution Finale

### Option 1: Modification du .env.local (Recommandé)

Éditez `/Users/wilfriedhouinlindjonon/Sites/perso/crm-api/.env.local` et assurez-vous que cette ligne existe:

```env
MAILER_DSN=smtp://c090ba90f02bf3:9a70d62ce1bf1b@sandbox.smtp.mailtrap.io:2525
```

Ensuite, nettoyez le cache:

```bash
rm -rf var/cache/* .env.local.php
php bin/console cache:clear
```

### Option 2: Export de Variable d'Environnement

Avant de démarrer le serveur, exportez la variable:

```bash
export MAILER_DSN="smtp://c090ba90f02bf3:9a70d62ce1bf1b@sandbox.smtp.mailtrap.io:2525"
symfony server:start
```

### Option 3: Script Automatique (Le Plus Simple)

Utilisez le script fourni:

```bash
chmod +x setup-email.sh
source setup-email.sh
symfony server:start
```

## 🔍 Diagnostic des Problèmes

### Commande de Test

Une nouvelle commande a été créée pour tester la configuration email:

```bash
php bin/console app:test-email
```

Cette commande vérifie:
1. ✅ Configuration du MAILER_DSN
2. ✅ Connexion SMTP
3. ✅ Envoi d'un email de test

### Vérifier la Valeur Actuelle

```bash
php bin/console debug:config framework mailer
```

Ou directement:

```bash
php -r "echo getenv('MAILER_DSN');"
```

## 📋 Ordre de Priorité des Fichiers .env

Symfony charge les fichiers dans cet ordre (du moins prioritaire au plus prioritaire):

1. `.env` - Valeurs par défaut (commitées)
2. `.env.local` - **Overrides locaux (NON committé) ← PROBLÈME ICI**
3. `.env.$APP_ENV` - Valeurs spécifiques à l'environnement
4. `.env.$APP_ENV.local` - Overrides spécifiques à l'env
5. Variables d'environnement système - **Priorité absolue**

**Le problème:** Le `.env.local` avait `MAILER_DSN=null://null` qui overridait tout.

## 🚀 Configuration Complète du Système OTP

### 1. Fichiers Modifiés

#### src/Service/EmailService.php
```php
private const FROM_EMAIL = 'hello@demomailtrap.com';  // ✅ Adresse validée par Mailtrap
```

#### config/packages/messenger.yaml
```yaml
routing:
    # Emails envoyés en mode synchrone (immédiatement)
    # Symfony\Component\Mailer\Messenger\SendEmailMessage: async  # ← Commenté
```

#### .env.local
```env
MAILER_DSN=smtp://c090ba90f02bf3:9a70d62ce1bf1b@sandbox.smtp.mailtrap.io:2525  # ✅ Correct
```

### 2. Nouveaux Fichiers Créés

- `src/Command/TestEmailCommand.php` - Commande de diagnostic
- `setup-email.sh` - Script de configuration automatique
- `docs/EMAIL_SETUP_GUIDE.md` - Ce guide
- `docs/POSTMAN_AUTH_OTP.md` - Guide Postman
- `README.md` - Documentation principale

## 📧 Test Complet du Système

### 1. Test de Diagnostic

```bash
php bin/console app:test-email
```

### 2. Test d'Inscription avec OTP

```bash
curl --location 'http://crm-api.test/auth/register' \
--header 'Content-Type: application/json' \
--data-raw '{
    "email": "test@example.com",
    "password": "Test123456!",
    "firstName": "Test",
    "lastName": "User"
}'
```

**Réponse attendue:**
```json
{
    "status": "success",
    "message": "User registered successfully. Please check your email for the verification code.",
    "data": {
        "userId": 1,
        "email": "test@example.com",
        "otpCode": "123456"  // Visible en dev uniquement
    }
}
```

### 3. Vérification dans Mailtrap

1. Allez sur https://mailtrap.io/inboxes
2. Sélectionnez votre inbox
3. Vous devriez voir l'email avec:
   - **FROM**: hello@demomailtrap.com
   - **Subject**: Vérifiez votre adresse email - Code OTP
   - **Body**: Design avec gradient + code OTP

### 4. Vérification du Code OTP

```bash
curl --location 'http://crm-api.test/auth/verify-email-otp' \
--header 'Content-Type: application/json' \
--data-raw '{
    "email": "test@example.com",
    "code": "123456"
}'
```

## 🔧 Points Clés de la Configuration

### ✅ Ce qui fonctionne maintenant

1. **Mode Synchrone**: Les emails sont envoyés immédiatement, pas en queue
2. **Adresse FROM valide**: `hello@demomailtrap.com` acceptée par Mailtrap
3. **Template HTML**: Design moderne avec gradient et styles
4. **Logs détaillés**: Tous les envois sont loggés dans `var/log/dev.log`
5. **Commande de diagnostic**: `php bin/console app:test-email`

### ❌ Erreurs Communes

1. **`MAILER_DSN=null://null`** → Les emails ne sont jamais envoyés
2. **Adresse FROM invalide** → Rejetée par Mailtrap
3. **Mode async sans worker** → Emails en queue mais jamais envoyés
4. **Cache non vidé** → Ancienne config toujours active

## 📊 Architecture du Système

```
┌─────────────────┐
│  User Register  │
└────────┬────────┘
         │
         ▼
┌────────────────────┐
│  AuthController    │
│  - Créé User       │
│  - Créé EmailOtp   │
└────────┬───────────┘
         │
         ▼
┌────────────────────┐
│  EmailService      │
│  - FROM: hello@... │
│  - Template HTML   │
└────────┬───────────┘
         │
         ▼
┌────────────────────┐
│  Symfony Mailer    │
│  - Mode: sync      │
│  - DSN: Mailtrap   │
└────────┬───────────┘
         │
         ▼
┌────────────────────┐
│  SMTP Mailtrap     │
│  - Port: 2525      │
│  - TLS: Oui        │
└────────┬───────────┘
         │
         ▼
┌────────────────────┐
│  Mailtrap Inbox    │
│  https://...       │
└────────────────────┘
```

## 🎓 Leçons Apprises

1. **Toujours vérifier `.env.local`** - Il override tout
2. **Utiliser une adresse FROM valide** - Mailtrap vérifie
3. **Mode synchrone pour les OTP** - Pas de délai
4. **Commande de diagnostic** - Essentielle pour debug
5. **Cache compilé** - Toujours le vider après changement de .env

## 🚀 Déploiement en Production

### Changements Nécessaires

1. **Code OTP automatiquement masqué en production** ✅:
   Le code vérifie automatiquement `APP_ENV`:
   ```php
   // Le code OTP n'est inclus que si APP_ENV === 'dev'
   if ($_ENV['APP_ENV'] === 'dev') {
       $responseData['otpCode'] = $otp->getCode();
   }
   ```
   **Action requise:** Assurez-vous que `APP_ENV=prod` dans votre `.env` de production

2. **Utiliser un service email professionnel**:
   ```env
   # Sendgrid
   MAILER_DSN=sendgrid://API_KEY@default

   # Mailgun
   MAILER_DSN=mailgun://API_KEY:DOMAIN@default

   # Amazon SES
   MAILER_DSN=ses://ACCESS_KEY:SECRET_KEY@default?region=us-east-1
   ```

3. **Configurer une adresse FROM réelle**:
   ```php
   // src/Service/EmailService.php
   private const FROM_EMAIL = 'noreply@votre-domaine.com';
   ```

4. **Configurer SPF/DKIM/DMARC** pour votre domaine

## 📞 Support

Si les emails ne fonctionnent toujours pas:

1. Exécutez `php bin/console app:test-email`
2. Vérifiez les logs: `tail -f var/log/dev.log`
3. Testez la connexion SMTP manuellement
4. Vérifiez que Mailtrap n'a pas de limites atteintes

## ✨ Résumé

Le système d'envoi d'emails OTP est maintenant **100% fonctionnel** avec:

- ✅ Configuration Mailtrap correcte
- ✅ Mode synchrone activé
- ✅ Adresse FROM valide
- ✅ Template HTML professionnel
- ✅ Commande de diagnostic
- ✅ Documentation complète
- ✅ Collection Postman à jour

**Le problème principal était:** `MAILER_DSN=null://null` dans `.env.local`

**La solution:** Corriger le `MAILER_DSN` et vider le cache compilé.
