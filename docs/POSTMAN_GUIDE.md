# Guide Postman - CRM API Authentication & Billing

## 📥 Installation de la Collection

### Méthode 1: Import direct dans Postman

1. **Ouvrir Postman**
2. **Cliquer sur "Import"** (en haut à gauche)
3. **Sélectionner le fichier**: `CRM_API_Auth_Billing.postman_collection.json`
4. **Cliquer sur "Import"**

### Méthode 2: Import depuis URL (si hébergé)

```
File → Import → Link
URL: https://raw.githubusercontent.com/votre-repo/crm-api/main/CRM_API_Auth_Billing.postman_collection.json
```

---

## 🔧 Configuration de l'Environnement

### Créer un nouvel environnement

1. **Cliquer sur "Environments"** (icône œil en haut à droite)
2. **Cliquer sur "+"** pour créer un nouvel environnement
3. **Nom**: `CRM API - Local` (ou Development, Production, etc.)
4. **Ajouter les variables:**

| Variable | Initial Value | Current Value | Description |
|----------|---------------|---------------|-------------|
| `base_url` | `http://localhost:8000` | `http://localhost:8000` | URL de l'API |
| `jwt_token` | _(vide)_ | _(auto)_ | Token JWT (auto-rempli) |
| `refresh_token` | _(vide)_ | _(auto)_ | Refresh token (auto-rempli) |
| `tenant_id` | `tenant_123` | `tenant_123` | ID du tenant |
| `verification_token` | _(vide)_ | _(auto)_ | Token vérification email |
| `password_reset_token` | _(vide)_ | _(auto)_ | Token reset password |
| `user_id` | _(vide)_ | _(auto)_ | ID utilisateur |
| `subscription_id` | _(vide)_ | _(auto)_ | ID abonnement |

5. **Sauvegarder** et **Sélectionner** cet environnement

### Variables automatiques

La collection utilise des **scripts** pour remplir automatiquement certaines variables :
- ✅ `jwt_token` - Rempli après login réussi
- ✅ `refresh_token` - Rempli après login/refresh
- ✅ `verification_token` - Rempli après register/resend
- ✅ `password_reset_token` - Rempli après forgot-password
- ✅ `user_id` - Rempli après login

---

## 🚀 Quick Start - Test Complet

### Scénario 1: Créer un compte et se connecter

**Étape 1: Register**
```
POST /auth/register
Body:
{
    "email": "test@example.com",
    "password": "SecurePass123!",
    "firstname": "Test",
    "lastname": "User"
}
```
→ Copier le `verificationToken` de la réponse (ou utiliser auto-saved)

**Étape 2: Verify Email**
```
POST /auth/verify-email
Body:
{
    "token": "{{verification_token}}"
}
```
→ Compte activé ✅

**Étape 3: Login**
```
POST /auth/login
Body:
{
    "email": "test@example.com",
    "password": "SecurePass123!"
}
```
→ JWT et refresh token sauvegardés automatiquement ✅

**Étape 4: Get Current User**
```
GET /auth/me
Headers: Authorization: Bearer {{jwt_token}}
```
→ Vérifier que vous êtes connecté ✅

---

### Scénario 2: S'abonner à un plan

**Pré-requis:** Être connecté (avoir un JWT token)

**Étape 1: Lister les plans disponibles**
```
GET /api/plans/list
```
→ Voir tous les plans (Free, Pro, Enterprise)

**Étape 2: Voir les détails d'un plan**
```
GET /api/plans/pro_monthly
```
→ Détails complets du plan Pro

**Étape 3: S'abonner au plan**
```
POST /api/subscription/subscribe
Headers:
  Authorization: Bearer {{jwt_token}}
  X-Tenant-ID: {{tenant_id}}
Body:
{
    "plan_code": "pro_monthly",
    "email": "test@example.com",
    "payment_method_id": "pm_card_visa"
}
```
→ Abonnement créé avec période d'essai ✅

**Note:** Pour `payment_method_id`, utilisez:
- **Test Stripe:** `pm_card_visa` (carte test Visa)
- **Production:** ID obtenu depuis Stripe Elements frontend

**Étape 4: Vérifier l'abonnement**
```
GET /api/subscription/current
Headers:
  Authorization: Bearer {{jwt_token}}
  X-Tenant-ID: {{tenant_id}}
```
→ Voir l'abonnement actif

**Étape 5: Vérifier les quotas**
```
GET /api/subscription/usage
Headers:
  Authorization: Bearer {{jwt_token}}
  X-Tenant-ID: {{tenant_id}}
```
→ Voir l'utilisation des quotas

---

### Scénario 3: Gérer son abonnement

**Changer de plan (Upgrade)**
```
POST /api/subscription/change-plan
Body:
{
    "new_plan_code": "enterprise_monthly"
}
```

**Annuler l'abonnement (fin de période)**
```
POST /api/subscription/cancel
Body:
{
    "immediately": false
}
```

**Réactiver l'abonnement**
```
POST /api/subscription/reactivate
```

**Voir l'historique de facturation**
```
GET /api/subscription/billing-history
```

---

### Scénario 4: Reset Password

**Étape 1: Demander un reset**
```
POST /auth/forgot-password
Body:
{
    "email": "test@example.com"
}
```
→ Token de reset sauvegardé automatiquement

**Étape 2: Réinitialiser le password**
```
POST /auth/reset-password
Body:
{
    "token": "{{password_reset_token}}",
    "password": "NewSecurePass456!"
}
```
→ Password changé ✅

**Étape 3: Se reconnecter**
```
POST /auth/login
Body:
{
    "email": "test@example.com",
    "password": "NewSecurePass456!"
}
```

---

## 📋 Organisation de la Collection

### 🔐 Authentication (9 endpoints)

| # | Endpoint | Méthode | Description |
|---|----------|---------|-------------|
| 1 | `/auth/register` | POST | Créer un compte |
| 2 | `/auth/verify-email` | POST | Vérifier email |
| 3 | `/auth/resend-verification` | POST | Renvoyer email |
| 4 | `/auth/login` | POST | Se connecter |
| 5 | `/auth/refresh` | POST | Renouveler JWT |
| 6 | `/auth/me` | GET | Infos utilisateur |
| 7 | `/auth/forgot-password` | POST | Demander reset |
| 8 | `/auth/reset-password` | POST | Reset password |
| 9 | `/auth/logout` | POST | Se déconnecter |

### 💳 Plans (3 endpoints)

| # | Endpoint | Méthode | Description |
|---|----------|---------|-------------|
| 1 | `/api/plans/list` | GET | Liste tous les plans |
| 2 | `/api/plans/{code}` | GET | Détails d'un plan |
| 3 | `/api/plans/compare` | GET | Comparer les plans |

### 💰 Subscriptions (10 endpoints)

| # | Endpoint | Méthode | Description |
|---|----------|---------|-------------|
| 1 | `/api/subscription/current` | GET | Abonnement actuel |
| 2 | `/api/subscription/subscribe` | POST | S'abonner |
| 3 | `/api/subscription/change-plan` | POST | Changer de plan |
| 4 | `/api/subscription/cancel` | POST | Annuler |
| 5 | `/api/subscription/reactivate` | POST | Réactiver |
| 6 | `/api/subscription/usage` | GET | Quotas et usage |
| 7 | `/api/subscription/billing-history` | GET | Historique facturation |
| 8 | `/api/subscription/payment-methods` | GET | Liste moyens paiement |
| 9 | `/api/subscription/payment-methods/add` | POST | Ajouter moyen paiement |
| 10 | `/api/subscription/payment-methods/{id}` | DELETE | Supprimer moyen paiement |

### 🔔 Webhooks (1 endpoint)

| # | Endpoint | Méthode | Description |
|---|----------|---------|-------------|
| 1 | `/webhooks/stripe` | POST | Stripe events (ne pas appeler) |

---

## 🧪 Tests Automatiques

La collection inclut des **tests automatiques** :

### Tests globaux (sur chaque requête)

```javascript
// Vérifier le code de statut
pm.test('Response status code is valid', function () {
    pm.expect(pm.response.code).to.be.oneOf([200, 201, 204, 400, 401, 403, 404, 409, 500]);
});

// Vérifier la structure
pm.test('Response has correct structure', function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData).to.have.property('status');
});
```

### Tests spécifiques

**Après login:**
```javascript
// Sauvegarde automatique du JWT
if (pm.response.code === 200) {
    var jsonData = pm.response.json();
    pm.environment.set('jwt_token', jsonData.data.token);
    pm.environment.set('refresh_token', jsonData.data.refreshToken);
}
```

**Après register:**
```javascript
// Sauvegarde du token de vérification
if (pm.response.code === 201) {
    var jsonData = pm.response.json();
    pm.environment.set('verification_token', jsonData.data.verificationToken);
}
```

---

## 🎯 Runner - Exécuter toute la collection

### Tester tout le flow d'authentification

1. **Cliquer sur la collection** "CRM API - Authentication & Billing"
2. **Cliquer sur "Run"** (ou ⌘R / Ctrl+R)
3. **Sélectionner le dossier** "🔐 Authentication"
4. **Ordre d'exécution:**
   - Register → Verify Email → Login → Me → Logout
5. **Cliquer sur "Run CRM API"**

### Tester le flow de billing

1. **Runner** → Sélectionner "💳 Plans & Subscriptions"
2. **Pré-requis:** JWT token valide (lancer auth d'abord)
3. **Ordre:**
   - List Plans → Get Plan → Subscribe → Get Current → Usage

---

## 🔑 Authentification dans les requêtes

### JWT Bearer Token (automatique)

La plupart des endpoints utilisent JWT. C'est déjà configuré dans la collection:

```
Authorization: Bearer {{jwt_token}}
```

Le token est **automatiquement ajouté** après login.

### Header X-Tenant-ID

Pour les endpoints de subscription, ajouter:

```
X-Tenant-ID: {{tenant_id}}
```

Déjà configuré dans les requêtes appropriées.

---

## 📊 Exemples de Réponses

### Succès - Login

```json
{
    "status": "success",
    "message": "Login successful",
    "data": {
        "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
        "refreshToken": "xyz789abc123...",
        "expiresAt": "2025-12-29T10:00:00+00:00",
        "user": {
            "id": "550e8400-e29b-41d4-a716-446655440000",
            "email": "john.doe@example.com",
            "firstname": "John",
            "lastname": "Doe",
            "roles": ["ROLE_USER"]
        }
    }
}
```

### Erreur - Validation

```json
{
    "status": "error",
    "message": "Email and password are required"
}
```

### Erreur - Unauthorized

```json
{
    "status": "error",
    "message": "Invalid credentials"
}
```

### Succès - Current Subscription

```json
{
    "status": "success",
    "data": {
        "id": "550e8400-e29b-41d4-a716-446655440010",
        "tenant_id": "tenant_123",
        "status": "active",
        "plan": {
            "code": "pro_monthly",
            "name": "Pro Monthly",
            "price": 49.99,
            "currency": "USD",
            "billing_interval": "monthly"
        },
        "trial": {
            "is_in_trial": false,
            "trial_ends_at": null,
            "days_remaining": 0
        },
        "billing": {
            "current_period_start": "2025-11-01 00:00:00",
            "current_period_end": "2025-12-01 00:00:00",
            "days_until_renewal": 15,
            "cancel_at_period_end": false
        }
    }
}
```

---

## 🐛 Troubleshooting

### Problème: "Unauthorized" sur /auth/me

**Cause:** JWT token manquant ou expiré

**Solution:**
1. Vérifier que `{{jwt_token}}` a une valeur dans l'environnement
2. Se reconnecter avec `/auth/login`
3. Ou utiliser `/auth/refresh` avec le refresh token

### Problème: Variables pas sauvegardées automatiquement

**Cause:** Environnement pas sélectionné

**Solution:**
1. Cliquer sur l'icône œil en haut à droite
2. Sélectionner l'environnement créé
3. Vérifier qu'il est bien actif (marqué d'une coche)

### Problème: "Invalid verification token"

**Cause:** Token expiré (24h pour email, 1h pour password)

**Solution:**
1. Utiliser `/auth/resend-verification` pour email
2. Utiliser `/auth/forgot-password` pour password reset

### Problème: Payment method invalid

**Cause:** `payment_method_id` invalide

**Solution (test):**
- Utiliser `pm_card_visa` pour les tests Stripe
- En production: obtenir l'ID depuis Stripe Elements

### Problème: Stripe webhook signature failed

**Cause:** Normal si appelé manuellement

**Solution:**
- Ne PAS appeler `/webhooks/stripe` manuellement
- Configurer le webhook dans Stripe Dashboard
- Tester avec Stripe CLI: `stripe listen --forward-to localhost:8000/webhooks/stripe`

---

## 📚 Documentation Complète

Pour plus de détails, consulter:

- **API Authentication:** `docs/AUTH_TESTING_GUIDE.md`
- **Stripe Integration:** `docs/STRIPE_INTEGRATION.md`
- **SaaS Billing System:** `SAAS_BILLING_SYSTEM.md`
- **Project Instructions:** `CLAUDE.md`

---

## 🎨 Personnalisation

### Ajouter vos propres tests

Éditer la requête → onglet **Tests** :

```javascript
pm.test("Subscription is active", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData.data.status).to.eql("active");
});

pm.test("Plan is Pro Monthly", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData.data.plan.code).to.eql("pro_monthly");
});
```

### Environnements multiples

Créer plusieurs environnements:

- **Local** - `http://localhost:8000`
- **Development** - `https://dev-api.example.com`
- **Staging** - `https://staging-api.example.com`
- **Production** - `https://api.example.com`

Basculer entre eux facilement !

---

## 📞 Support

**Problèmes avec l'API:**
- Vérifier les logs Symfony: `tail -f var/log/dev.log`
- Vérifier la base de données
- Consulter la documentation

**Problèmes avec Postman:**
- [Documentation Postman](https://learning.postman.com/docs/)
- [Community Forums](https://community.postman.com/)

---

**Version:** 1.0
**Date:** 2025-11-29
**Auteur:** Development Team
