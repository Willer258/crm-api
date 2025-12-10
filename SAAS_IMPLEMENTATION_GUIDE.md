# Guide Complet d'Implémentation SaaS Multi-Tenant

## 📚 Table des Matières

1. [Vue d'ensemble](#vue-densemble)
2. [Architecture](#architecture)
3. [Installation et Configuration](#installation-et-configuration)
4. [Fonctionnalités Implémentées](#fonctionnalités-implémentées)
5. [Guide Super Admin](#guide-super-admin)
6. [Mode Test/Sandbox](#mode-test-sandbox)
7. [API Endpoints](#api-endpoints)
8. [Commandes CLI](#commandes-cli)
9. [Workflows Automatisés](#workflows-automatisés)
10. [Monitoring et Analytics](#monitoring-et-analytics)
11. [Support Technique](#support-technique)
12. [Troubleshooting](#troubleshooting)

---

## 🎯 Vue d'ensemble

Cette application CRM est maintenant **production-ready** pour un modèle **SaaS B2B multi-tenant** avec :

- ✅ **Provisioning automatique** des tenants
- ✅ **Gestion complète des abonnements** (Stripe)
- ✅ **Mode Test/Sandbox** pour développement
- ✅ **Onboarding guidé** des nouveaux clients
- ✅ **Dunning automatisé** pour impayés
- ✅ **Métriques SaaS avancées** (MRR, ARR, Churn, LTV, CAC)
- ✅ **Rate limiting** par tenant
- ✅ **Système de support** avec tickets
- ✅ **Dashboard Super Admin** complet
- ✅ **Backups automatisés** par tenant

---

## 🏗️ Architecture

### Modèle Multi-Tenant : Database-per-Tenant

```
┌─────────────────────────────────────────┐
│  BASE CENTRALE (master_db)              │
│  - Subscriptions                        │
│  - Plans                                │
│  - Invoices/Payments                    │
│  - SupportTickets                       │
│  - OnboardingSteps                      │
│  - DunningAttempts                      │
└─────────────────────────────────────────┘
                ↓
┌──────────────┬──────────────┬────────────┐
│ acme_crm_db  │ startup_     │ corp_      │
│              │ crm_db       │ crm_db     │
│ - Contacts   │ - Contacts   │ - Contacts │
│ - Companies  │ - Companies  │ - Companies│
│ - Deals      │ - Deals      │ - Deals    │
│ - Users      │ - Users      │ - Users    │
└──────────────┴──────────────┴────────────┘
```

### Services Principaux

1. **TenantProvisioningService** - Création automatique des tenants
2. **SandboxModeService** - Mode test sans paiements réels
3. **OnboardingService** - Workflow d'intégration
4. **DunningService** - Gestion des impayés
5. **SaasMetricsService** - Analytics business
6. **SuperAdminService** - Monitoring global
7. **SupportTicketService** - Support client

---

## ⚙️ Installation et Configuration

### 1. Migrations Base de Données

```bash
# Créer les nouvelles tables
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate

# Générer les tables:
# - subscription
# - plan
# - invoice
# - payment
# - quota
# - usage_metric
# - onboarding_step
# - dunning_attempt
# - support_ticket
# - support_ticket_message
```

### 2. Configuration Stripe

Créer un fichier `.env.local` :

```env
# Stripe
STRIPE_SECRET_KEY=sk_test_your_key
STRIPE_PUBLIC_KEY=pk_test_your_key
STRIPE_WEBHOOK_SECRET=whsec_your_secret

# Mode Sandbox (dev/test)
APP_ENV=dev
```

### 3. Créer les Plans de Base

```bash
php bin/console app:create-plans
```

Ou via SQL :

```sql
INSERT INTO plan (code, name, description, price, currency, billing_interval, trial_days, max_contacts, max_companies, max_deals, max_users, max_storage_bytes, max_api_calls_per_day, features, created_at)
VALUES
('starter', 'Starter', 'Plan pour petites équipes', 9.99, 'EUR', 'monthly', 14, 1000, 100, 50, 3, 5368709120, 1000, '["crm_basic"]', NOW()),
('business', 'Business', 'Plan pour entreprises', 29.99, 'EUR', 'monthly', 14, 10000, 1000, 500, 15, 53687091200, 10000, '["crm_basic","automation","reporting"]', NOW()),
('enterprise', 'Enterprise', 'Plan illimité', 99.99, 'EUR', 'monthly', 14, NULL, NULL, NULL, NULL, 536870912000, NULL, '["all"]', NOW());
```

### 4. Synchroniser Plans avec Stripe

```php
// Via code
use App\Service\StripeService;
use App\Repository\PlanRepository;

$plans = $planRepository->findAll();
foreach ($plans as $plan) {
    $stripeData = $stripeService->syncPlanWithStripe($plan);
    $plan->setStripeProductId($stripeData['product_id']);
    $plan->setStripePriceId($stripeData['price_id']);
}
$em->flush();
```

---

## 🚀 Fonctionnalités Implémentées

### 1. Provisioning Automatique

**Lors de l'inscription d'un nouveau client** :

```php
use App\Service\TenantProvisioningService;

$result = $provisioningService->provisionTenant(
    tenantId: 'acme-corp',
    plan: $plan,
    adminData: [
        'email' => 'admin@acme-corp.com',
        'password' => 'SecurePass123!',
        'firstName' => 'John',
        'lastName' => 'Doe'
    ],
    zoneName: 'eu'
);

// Résultat:
// - BDD créée: eu_crm_db
// - Migrations exécutées
// - Données par défaut initialisées
// - Utilisateur admin créé
```

### 2. Mode Sandbox (Sans Stripe)

**Pour le développement sans paiements réels** :

```php
use App\Service\SandboxModeService;

if ($sandboxService->isSandboxMode()) {
    // Créer abonnement test
    $subscription = $sandboxService->createSandboxSubscription(
        tenantId: 'test-company',
        plan: $starterPlan,
        userEmail: 'test@example.com',
        trialDays: 14
    );

    // IDs Stripe factices générés automatiquement
    // Pas de vraie facturation
}

// Simuler des scénarios
$sandboxService->simulateRenewal($subscription);
$sandboxService->simulatePaymentFailure($subscription);
$sandboxService->simulateCancellation($subscription);
```

### 3. Onboarding Guidé

**6 étapes automatiques pour nouveaux clients** :

```php
use App\Service\OnboardingService;

// Initialiser onboarding
$steps = $onboardingService->initializeOnboarding('acme-corp');

// Progression
$progress = $onboardingService->getOnboardingProgress('acme-corp');
// {
//   "total": 6,
//   "completed": 2,
//   "percentage": 33.33,
//   "next_step": {...}
// }

// Compléter une étape
$onboardingService->completeStep(
    'acme-corp',
    OnboardingStep::STEP_EMAIL_VERIFICATION,
    metadata: ['verified_at' => '2025-12-10 10:30:00']
);
```

### 4. Dunning Automatisé

**Workflow complet pour impayés** :

- **J+1** : Email notification
- **J+3** : Rappel avec lien paiement
- **J+7** : Restriction mode lecture seule
- **J+14** : Suspension compte
- **J+30** : Programmation suppression

```bash
# Exécuter manuellement
php bin/console app:process-dunning

# Configurer cron (quotidien)
0 9 * * * cd /path/to/app && php bin/console app:process-dunning
```

### 5. Métriques SaaS

```php
use App\Service\SaasMetricsService;

// MRR (Monthly Recurring Revenue)
$mrr = $metricsService->calculateMRR();
// { "mrr": 15420.50, "total_subscriptions": 145 }

// ARR (Annual Recurring Revenue)
$arr = $metricsService->calculateARR();
// { "arr": 185046.00 }

// Taux de Churn
$churn = $metricsService->calculateChurnRate(
    new \DateTimeImmutable('-1 month'),
    new \DateTimeImmutable()
);
// { "churn_rate": 3.5, "retention_rate": 96.5 }

// LTV (Lifetime Value)
$ltv = $metricsService->calculateLTV();
// { "ltv": 2500.00, "arpu": 106.20 }

// CAC (Customer Acquisition Cost)
$cac = $metricsService->calculateCAC(
    totalMarketingCost: 50000,
    startDate: new \DateTimeImmutable('-1 month'),
    endDate: new \DateTimeImmutable()
);
// { "cac": 425.50, "ltv_cac_ratio": 5.87, "health_status": "excellent" }

// Dashboard complet
$dashboard = $metricsService->getDashboardMetrics();
```

---

## 👑 Guide Super Admin

### Accès Dashboard

```http
GET /api/super-admin/dashboard
Authorization: Bearer {super_admin_token}

Response:
{
  "status": "success",
  "data": {
    "summary": {
      "total_tenants": 145,
      "active_subscriptions": 132,
      "new_tenants_this_month": 12,
      "churned_this_month": 3
    },
    "revenue": { "mrr": {...}, "arr": {...} },
    "health": { "database": "healthy", "storage": "healthy" },
    "growth": { "growth_rate_percent": 8.5 }
  }
}
```

### Voir Tous les Tenants

```http
GET /api/super-admin/tenants?status=active&plan=business
```

### Détails d'un Tenant

```http
GET /api/super-admin/tenants/acme-corp

Response:
{
  "tenant_info": {...},
  "database_info": { "size_mb": 450, "table_count": 25 },
  "usage_metrics": { "contacts_count": 1250, "deals_count": 89 },
  "billing_history": [...],
  "support_tickets": [...]
}
```

### Provisionner un Nouveau Tenant

```http
POST /api/super-admin/tenants/provision
Content-Type: application/json

{
  "tenant_id": "new-startup",
  "plan_code": "starter",
  "zone": "eu",
  "admin_email": "admin@new-startup.com",
  "admin_password": "SecurePass123!",
  "admin_first_name": "Jane",
  "admin_last_name": "Smith"
}
```

### Suspendre/Activer un Tenant

```http
PATCH /api/super-admin/tenants/acme-corp/access

{
  "enabled": false,
  "reason": "Non-payment - suspended pending payment"
}
```

### Impersonation (Support)

```http
POST /api/super-admin/tenants/acme-corp/impersonate

Response:
{
  "impersonation_token": "imp_a1b2c3d4e5f6...",
  "expires_at": "2025-12-10 11:30:00"
}

// Utiliser ce token pour accéder au tenant en tant qu'admin
```

### Métriques Globales

```http
GET /api/super-admin/metrics?type=all
GET /api/super-admin/metrics/export  # Export complet
```

---

## 🧪 Mode Test/Sandbox

### Activer le Mode Sandbox

En environnement `dev` ou `test`, le mode sandbox est **automatiquement activé**.

```php
// .env
APP_ENV=dev  # Active sandbox mode
```

### Créer des Données de Test

```php
use App\Service\SandboxModeService;
use App\Repository\PlanRepository;

// Créer 10 abonnements tests
$plans = $planRepository->findAll();
$subscriptions = $sandboxService->seedTestSubscriptions($plans, count: 10);

// Résultat: 10 tenants créés avec IDs Stripe factices
// - test-tenant-001 à test-tenant-010
// - Pas de vraie facturation
```

### Cartes de Test

```php
$testCards = $sandboxService->getTestCards();
// [
//   { "number": "4242424242424242", "description": "Success" },
//   { "number": "4000000000009995", "description": "Declined" }
// ]
```

### Nettoyer les Données Test

```bash
php bin/console app:sandbox:cleanup

# Ou via code
$deleted = $sandboxService->cleanupSandboxData();
```

---

## 📡 API Endpoints

### Abonnements

```http
# Client obtient son abonnement actuel
GET /api/subscription/current
Headers: X-Tenant-ID: acme-corp

# S'abonner à un plan (MODE PRODUCTION)
POST /api/subscription/subscribe
{
  "plan_code": "business",
  "email": "billing@acme-corp.com",
  "payment_method_id": "pm_xxx"  # De Stripe.js
}

# S'abonner à un plan (MODE SANDBOX)
POST /api/subscription/subscribe
{
  "plan_code": "business",
  "email": "test@acme-corp.com"
  # Pas de payment_method_id nécessaire en sandbox
}

# Changer de plan
POST /api/subscription/change-plan
{ "new_plan_code": "enterprise" }

# Annuler
POST /api/subscription/cancel
{ "immediately": false }  # Fin de période ou immédiat

# Réactiver
POST /api/subscription/reactivate

# Voir utilisation & quotas
GET /api/subscription/usage

# Historique facturation
GET /api/subscription/billing-history

# Moyens de paiement
GET /api/subscription/payment-methods
POST /api/subscription/payment-methods/add
DELETE /api/subscription/payment-methods/{id}
```

### Support Tickets (Client)

```http
# Créer un ticket
POST /api/support/tickets
{
  "tenant_id": "acme-corp",
  "subject": "Cannot import contacts",
  "description": "Getting error 500 when importing CSV...",
  "category": "technical",
  "priority": "high",
  "requester_email": "john@acme-corp.com",
  "requester_name": "John Doe"
}

# Ajouter un message
POST /api/support/tickets/{id}/messages
{
  "message": "I tried the solution but still getting the error",
  "is_from_agent": false
}
```

### Super Admin (voir section précédente)

---

## 🖥️ Commandes CLI

### Gestion des Tenants

```bash
# Changer de tenant actif (pour commandes)
php bin/console app:tenant:set acme-corp

# Voir tenant actuel
php bin/console app:tenant:get

# Exécuter une commande pour chaque tenant
php bin/console app:tenant:foreach doctrine:migrations:migrate
```

### Dunning

```bash
# Traiter les impayés
php bin/console app:process-dunning

# Mode dry-run (simulation)
php bin/console app:process-dunning --dry-run
```

### Backups

```bash
# Backup d'un tenant
php bin/console app:backup-tenant acme-corp --zone=eu --compress

# Backup de tous les tenants
php bin/console app:backup-tenant all --zone=eu

# Avec rétention personnalisée
php bin/console app:backup-tenant acme-corp --retention-days=60

# Spécifier répertoire de sortie
php bin/console app:backup-tenant acme-corp --output-dir=/backups
```

### Initialisation

```bash
# Créer données par défaut
php bin/console app:init-fixtures
```

---

## 🤖 Workflows Automatisés

### Cron Jobs Recommandés

```cron
# Dunning quotidien (9h du matin)
0 9 * * * cd /path/to/app && php bin/console app:process-dunning

# Reset quotas quotidiens (minuit)
0 0 * * * cd /path/to/app && php bin/console app:reset-daily-quotas

# Backup hebdomadaire (dimanche 2h du matin)
0 2 * * 0 cd /path/to/app && php bin/console app:backup-tenant all --compress

# Métriques mensuelles (1er du mois)
0 8 1 * * cd /path/to/app && php bin/console app:export-monthly-metrics
```

### Webhooks Stripe

```php
# src/Controller/StripeWebhookController.php

// À configurer dans Stripe Dashboard:
// URL: https://votre-domaine.com/webhook/stripe
// Events:
// - invoice.payment_succeeded
// - invoice.payment_failed
// - customer.subscription.updated
// - customer.subscription.deleted
```

---

## 📊 Monitoring et Analytics

### Health Check

```http
GET /api/super-admin/health

Response:
{
  "database": { "status": "healthy", "connections": 15 },
  "storage": { "status": "healthy", "usage_percent": 45.2 },
  "api_performance": { "avg_response_time_ms": 120 },
  "background_jobs": { "status": "healthy", "pending_jobs": 5 }
}
```

### Logs Importants

```bash
# Logs Symfony
tail -f var/log/dev.log

# Filtrer par tenant
grep "acme-corp" var/log/prod.log

# Erreurs seulement
grep "ERROR" var/log/prod.log
```

### Métriques à Surveiller

1. **MRR** : Croissance mensuelle >5%
2. **Churn Rate** : <5% acceptable, <3% excellent
3. **LTV:CAC Ratio** : >3:1 idéal
4. **Temps de réponse support** : <1h pour high priority
5. **Uptime** : >99.9%

---

## 🎫 Support Technique

### Workflow Support

1. **Client crée ticket** via API ou interface
2. **Notification envoyée** à l'équipe support
3. **Super Admin assigne** le ticket à un agent
4. **Agent répond** via dashboard
5. **Client notifié** par email
6. **Résolution** et fermeture

### Statistiques Support

```http
GET /api/super-admin/support/statistics

Response:
{
  "total": 145,
  "by_status": {
    "open": 12,
    "in_progress": 8,
    "resolved": 120,
    "closed": 5
  },
  "by_priority": {
    "urgent": 2,
    "high": 10,
    "medium": 85,
    "low": 48
  },
  "avg_response_time_minutes": 25,
  "avg_resolution_time_hours": 6.5
}
```

---

## 🔧 Troubleshooting

### Problème : Tenant ne peut pas créer de contacts

**Vérifier quotas** :

```php
use App\Managers\QuotaManager;

$canCreate = $quotaManager->canCreateContact('acme-corp');
if (!$canCreate) {
    // Quota dépassé
    $quota = $quotaManager->getQuota('acme-corp', Quota::TYPE_CONTACTS);
    echo "Limite: " . $quota->getLimitValue();
    echo "Utilisé: " . $quota->getCurrentUsage();
}
```

**Solution** : Upgrade plan ou augmenter quota manuellement

### Problème : Paiement échoue en production

1. Vérifier webhook Stripe configuré
2. Vérifier clés API Stripe
3. Checker logs :

```bash
grep "Stripe" var/log/prod.log | tail -20
```

### Problème : Migration base de données échoue

```bash
# Vérifier état
php bin/console doctrine:migrations:status

# Rollback dernière migration
php bin/console doctrine:migrations:migrate prev

# Re-exécuter
php bin/console doctrine:migrations:migrate
```

### Problème : Rate limit atteint

```php
// Augmenter temporairement
$quota = $quotaManager->getQuota('acme-corp', Quota::TYPE_API_CALLS);
$quota->setLimitValue(20000); // Augmenter de 10k à 20k
$em->flush();

// Ou désactiver temporairement
// Dans config/services.yaml:
App\EventListener\RateLimitListener:
    arguments:
        $rateLimitEnabled: false
```

---

## ✅ Checklist Mise en Production

- [ ] Configurer clés Stripe production
- [ ] Créer les plans dans Stripe
- [ ] Synchroniser plans avec BDD
- [ ] Configurer webhooks Stripe
- [ ] Configurer cron jobs
- [ ] Tester provisioning tenant
- [ ] Tester workflow complet inscription
- [ ] Tester dunning workflow
- [ ] Configurer backups automatiques
- [ ] Configurer monitoring (Sentry, New Relic, etc.)
- [ ] Tester support tickets
- [ ] Documenter pour l'équipe
- [ ] Former équipe support

---

## 📞 Contact & Support

Pour toute question sur cette implémentation :

- **Email** : support@votre-domaine.com
- **Documentation Stripe** : https://stripe.com/docs
- **Symfony** : https://symfony.com/doc/current

---

**Version** : 1.0.0
**Dernière mise à jour** : 2025-12-10
**Auteur** : Claude (Anthropic) + Votre Équipe
