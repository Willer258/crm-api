# Guide d'utilisation de l'authentification OTP avec Postman

## Vue d'ensemble

La collection Postman a été mise à jour pour supporter le système de vérification par email avec code OTP (One-Time Password).

## Flux d'authentification avec OTP

### 1. Inscription (Auth Register)

**Endpoint:** `POST /auth/register`

**Body:**
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
        "otpCode": "123456"  // Visible en dev uniquement
    }
}
```

**Actions automatiques:**
- ✅ `user_id` sauvegardé dans les variables
- ✅ `user_email` sauvegardé dans les variables
- ✅ `otp_code` sauvegardé dans les variables (dev uniquement)
- ✅ Email OTP envoyé à l'utilisateur

### 2. Vérification de l'email (Auth Verify Email OTP)

**Endpoint:** `POST /auth/verify-email-otp`

**Body:**
```json
{
    "email": "{{user_email}}",
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

**Sécurité:**
- Maximum 5 tentatives par code
- Le code expire après 10 minutes
- Rate limiting: 10 tentatives en 15 minutes par IP

### 3. Renvoyer un code OTP (Auth Resend OTP)

**Endpoint:** `POST /auth/resend-otp`

**Body:**
```json
{
    "email": "{{user_email}}"
}
```

**Réponse:**
```json
{
    "status": "success",
    "message": "Verification code sent successfully.",
    "data": {
        "otpCode": "789012"  // Visible en dev uniquement
    }
}
```

**Sécurité:**
- Rate limiting: 5 demandes en 15 minutes par IP
- Les anciens codes sont automatiquement invalidés
- Nouveau code valide pendant 10 minutes

### 4. Connexion (Auth Login)

**Endpoint:** `POST /auth/login`

**Body:**
```json
{
    "email": "{{user_email}}",
    "password": "SecurePassword123!"
}
```

**Réponse:**
```json
{
    "status": "success",
    "message": "Login successful",
    "data": {
        "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
        "refreshToken": "def502001a2b3c...",
        "user": {
            "id": 1,
            "email": "user@example.com",
            "roles": ["ROLE_USER"]
        }
    }
}
```

**Actions automatiques:**
- ✅ `jwt_token` sauvegardé et utilisé pour toutes les requêtes suivantes
- ✅ `refresh_token` sauvegardé
- ✅ Tous les endpoints protégés sont maintenant accessibles

## Variables de collection

Les variables suivantes sont automatiquement gérées:

| Variable | Description | Exemple |
|----------|-------------|---------|
| `base_url` | URL de base de l'API | `http://crm-api.test` |
| `user_email` | Email de l'utilisateur | `user@example.com` |
| `otp_code` | Code OTP (dev uniquement) | `123456` |
| `user_id` | ID de l'utilisateur | `1` |
| `jwt_token` | Token JWT pour l'authentification | `eyJ0eXAi...` |
| `refresh_token` | Token de rafraîchissement | `def50200...` |

## Système alternatif (Token par email)

Si vous préférez le système classique avec token par email:

1. **Auth Register** - Crée le compte
2. **Auth Verify Email** - Vérifier avec le token reçu par email
3. **Auth Resend Verification** - Renvoyer l'email avec token
4. **Auth Login** - Se connecter

## Gestion des erreurs

### Email déjà vérifié
```json
{
    "status": "error",
    "message": "Email is already verified"
}
```

### Code OTP invalide
```json
{
    "status": "error",
    "message": "Invalid or expired verification code"
}
```

### Trop de tentatives
```json
{
    "status": "error",
    "message": "Too many verification attempts. Please try again in 15 minutes."
}
```

### Code expiré
```json
{
    "status": "error",
    "message": "Verification code has expired. Please request a new one."
}
```

## Tests recommandés

### Scénario 1: Inscription et vérification réussies
1. ✅ Auth Register
2. ✅ Auth Verify Email OTP (avec code reçu)
3. ✅ Auth Login

### Scénario 2: Code expiré
1. ✅ Auth Register
2. ⏰ Attendre 10 minutes
3. ❌ Auth Verify Email OTP (code expiré)
4. ✅ Auth Resend OTP
5. ✅ Auth Verify Email OTP (avec nouveau code)

### Scénario 3: Trop de tentatives
1. ✅ Auth Register
2. ❌ Auth Verify Email OTP (mauvais code) x5
3. ❌ Erreur: trop de tentatives
4. ✅ Auth Resend OTP (nouveau code)
5. ✅ Auth Verify Email OTP (avec nouveau code)

## Configuration de l'email (pour développement)

L'application utilise **Mailtrap** pour capturer les emails en développement:

1. Créez un compte sur https://mailtrap.io
2. Copiez vos identifiants SMTP
3. Configurez dans `.env`:
   ```env
   MAILER_DSN=smtp://username:password@smtp.mailtrap.io:2525
   ```

En développement, le code OTP est également visible dans la réponse JSON pour faciliter les tests.

## Production

⚠️ **Important pour la production:**

1. **Retirer le code OTP de la réponse JSON**
   - Modifier `AuthController::register()` ligne ~136
   - Modifier `AuthController::resendOtp()` ligne ~359

2. **Configurer un vrai service d'envoi d'emails**
   - Sendgrid, Mailgun, Amazon SES, etc.
   - Ne jamais utiliser Gmail en production

3. **Surveiller les logs**
   - Échecs d'envoi d'emails
   - Tentatives de fraude (trop de codes demandés)

## Support

Pour plus d'informations:
- Documentation complète: `docs/EMAIL_CONFIGURATION.md`
- Collection Postman: `CRM_API_COMPLETE.postman_collection.json`
