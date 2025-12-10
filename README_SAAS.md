# 🚀 CRM SaaS Multi-Tenant - Production Ready

> Plateforme CRM SaaS B2B complète avec provisioning automatique, gestion abonnements, métriques avancées et dashboard Super Admin.

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![PHP](https://img.shields.io/badge/PHP-8.2+-purple.svg)
![Symfony](https://img.shields.io/badge/Symfony-7.2-black.svg)
![License](https://img.shields.io/badge/license-MIT-green.svg)

---

## 📋 Table des Matières

- [Fonctionnalités](#-fonctionnalités)
- [Architecture](#-architecture)
- [Installation Rapide](#-installation-rapide)
- [Documentation](#-documentation)
- [API Reference](#-api-reference)
- [Commandes CLI](#-commandes-cli)
- [Démo](#-démo)
- [Support](#-support)

---

## ✨ Fonctionnalités

### 🎯 Core SaaS

- ✅ **Multi-Tenant Database-per-Tenant** - Isolation totale des données
- ✅ **Provisioning Automatique** - Création BDD + migrations + admin user
- ✅ **Intégration Stripe Complète** - Paiements, abonnements, webhooks
- ✅ **Mode Test/Sandbox** - Développement sans Stripe
- ✅ **Onboarding Guidé** - 6 étapes pour nouveaux clients

### 💰 Gestion Financière

- ✅ **MRR/ARR** - Revenus récurrents mensuels/annuels
- ✅ **Churn Rate** - Taux de désabonnement
- ✅ **LTV** - Lifetime Value clients
- ✅ **CAC** - Coût d'acquisition client
- ✅ **NRR** - Net Revenue Retention
- ✅ **Dunning Automatisé** - Workflow impayés (J+1, J+3, J+7, J+14, J+30)

### 👑 Super Admin

- ✅ **Dashboard Complet** - Vue 360° de la plateforme
- ✅ **Monitoring Temps Réel** - Health check système
- ✅ **Gestion Tenants** - Liste, détails, suspension, impersonation
- ✅ **Support Intégré** - Système de tickets complet
- ✅ **Analytics Avancés** - Tendances, prédictions, exports

### 🔒 Sécurité & Performance

- ✅ **Rate Limiting par Tenant** - Quotas API personnalisés
- ✅ **Backups Automatiques** - mysqldump + compression gzip
- ✅ **Audit Logs** - Traçabilité complète
- ✅ **Quotas Dynamiques** - Contacts, deals, stockage, API calls

---

## 🏗️ Architecture

```
┌─────────────────────────────────────────────────┐
│         BASE CENTRALE (master_db)               │
│  ┌─────────────┐  ┌──────────────┐            │
│  │Subscriptions│  │ Plans        │            │
│  │Invoices     │  │ SupportTickets│           │
│  │Payments     │  │ DunningAttempts│         │
│  └─────────────┘  └──────────────┘            │
└─────────────────────────────────────────────────┘
                     ↓
        ┌────────────┬────────────┬────────────┐
        │ acme_      │ startup_   │ corp_      │
        │ crm_db     │ crm_db     │ crm_db     │
        │            │            │            │
        │ Contacts   │ Contacts   │ Contacts   │
        │ Companies  │ Companies  │ Companies  │
        │ Deals      │ Deals      │ Deals      │
        │ Users      │ Users      │ Users      │
        └────────────┴────────────┴────────────┘
```

### Stack Technique

| Composant | Technologie |
|-----------|-------------|
| **Backend** | Symfony 7.2 + PHP 8.2+ |
| **Database** | MySQL 8.0+ (multi-tenancy) |
| **Paiements** | Stripe API |
| **Cache** | Redis (optionnel) |
| **Queue** | Symfony Messenger |
| **Email** | Symfony Mailer |

---

## ⚡ Installation Rapide

### Prérequis

- PHP 8.2+
- MySQL 8.0+
- Composer
- Symfony CLI (optionnel)

### Étapes

```bash
# 1. Cloner le repo
git clone https://github.com/votre-org/crm-api.git
cd crm-api

# 2. Installer dépendances
composer install

# 3. Configurer environnement
cp .env .env.local
# Éditer .env.local avec vos credentials

# 4. Créer base de données
php bin/console doctrine:database:create

# 5. Exécuter migrations
php bin/console doctrine:migrations:migrate

# 6. Charger données de test
php bin/console doctrine:fixtures:load

# 7. Créer super admin
php bin/console app:create-super-admin

# 8. Lancer serveur
symfony server:start
# ou
php -S localhost:8000 -t public
```

### Configuration Stripe

```env
# .env.local
STRIPE_SECRET_KEY=sk_test_votre_cle
STRIPE_PUBLIC_KEY=pk_test_votre_cle
STRIPE_WEBHOOK_SECRET=whsec_votre_secret
```

---

## 📚 Documentation

| Document | Description |
|----------|-------------|
| [SAAS_IMPLEMENTATION_GUIDE.md](SAAS_IMPLEMENTATION_GUIDE.md) | **Guide complet** - Installation, API, workflows |
| [SUPER_ADMIN_ANALYTICS.md](SUPER_ADMIN_ANALYTICS.md) | **Analytics & Monitoring** - Dashboard, KPIs, alertes |
| [IMPLEMENTATION_SUMMARY.md](IMPLEMENTATION_SUMMARY.md) | **Résumé technique** - Fichiers, prochaines étapes |

---

## 🔌 API Reference

### Authentication

```http
POST /api/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password"
}

Response:
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
}
```

### Super Admin Dashboard

```http
GET /api/super-admin/dashboard
Authorization: Bearer {token}

Response:
{
  "summary": {
    "total_tenants": 145,
    "active_subscriptions": 132,
    "mrr": 15420.50
  },
  "revenue": {...},
  "health": {...}
}
```

### Créer Nouveau Tenant

```http
POST /api/super-admin/tenants/provision
Authorization: Bearer {super_admin_token}
Content-Type: application/json

{
  "tenant_id": "new-startup",
  "plan_code": "starter",
  "admin_email": "admin@startup.com",
  "admin_password": "SecurePass123!"
}
```

### S'abonner (Client)

```http
POST /api/subscription/subscribe
Headers:
  X-Tenant-ID: acme-corp
  Content-Type: application/json

{
  "plan_code": "business",
  "email": "billing@acme-corp.com",
  "payment_method_id": "pm_xxx"
}
```

**Plus d'endpoints** : Voir [SAAS_IMPLEMENTATION_GUIDE.md](SAAS_IMPLEMENTATION_GUIDE.md#api-endpoints)

---

## 🖥️ Commandes CLI

### Gestion Tenants

```bash
# Changer de tenant actif
php bin/console app:tenant:set acme-corp

# Provisionner nouveau tenant
php bin/console app:provision-tenant new-startup starter

# Backup tenant
php bin/console app:backup-tenant acme-corp --compress

# Backup tous les tenants
php bin/console app:backup-tenant all
```

### Dunning & Facturation

```bash
# Traiter impayés
php bin/console app:process-dunning

# Mode simulation
php bin/console app:process-dunning --dry-run
```

### Métriques

```bash
# Calculer MRR actuel
php bin/console app:metrics:mrr

# Export rapport mensuel
php bin/console app:metrics:export --month=2025-12
```

---

## 🎮 Démo

### Mode Sandbox (Sans Stripe)

```bash
# Activer mode dev
APP_ENV=dev

# Créer 10 tenants de test
php bin/console app:sandbox:seed 10

# Résultat: 10 abonnements factices créés
# - test-tenant-001 à test-tenant-010
# - Pas de vraie facturation Stripe
```

### Tester Super Admin

```bash
# 1. Créer super admin
php bin/console app:create-super-admin
# Email: admin@example.com
# Password: generated

# 2. Obtenir token JWT
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"password"}'

# 3. Accéder dashboard
curl http://localhost:8000/api/super-admin/dashboard \
  -H "Authorization: Bearer {token}"
```

---

## 📊 Métriques Clés

### Exemple Dashboard

```
┌─────────────────────────────────────────────┐
│  📊 DASHBOARD SUPER ADMIN                   │
├─────────────────────────────────────────────┤
│  Total Tenants:        145                  │
│  Actifs:              132 (91%)             │
│  MRR:                 15,420.50 €           │
│  ARR:                 185,046.00 €          │
│  Churn Rate:          3.2%                  │
│  LTV:                 2,500.00 €            │
│  CAC:                 425.50 €              │
│  LTV:CAC:             5.87:1  ✅            │
│  ──────────────────────────────────────────│
│  Status Système:      🟢 HEALTHY           │
│  Uptime:              99.95%                │
│  API Latency:         120ms                 │
│  DB Size:             45 GB                 │
└─────────────────────────────────────────────┘
```

---

## 🎯 Cas d'Usage

### 1. Nouvel Abonnement Client

```mermaid
sequenceDiagram
    Client->>API: POST /subscription/subscribe
    API->>Stripe: Create customer
    Stripe-->>API: Customer ID
    API->>Stripe: Create subscription
    Stripe-->>API: Subscription + Invoice
    API->>DB: Save subscription
    API->>Provisioning: Create tenant DB
    Provisioning-->>API: DB created
    API->>Onboarding: Initialize steps
    API-->>Client: Success + credentials
```

### 2. Dunning Workflow

```
Payment Failed (J0)
    ↓
Email Notification (J+1)
    ↓
Reminder Email (J+3)
    ↓
Restrict Access (J+7)
    ↓
Suspend Account (J+14)
    ↓
Schedule Deletion (J+30)
```

### 3. Support Ticket

```
Client crée ticket
    ↓
Email confirmation → Client
    ↓
Notification → Support Team
    ↓
Agent assigne ticket
    ↓
Agent répond
    ↓
Email réponse → Client
    ↓
Client répond
    ↓
Agent résout
    ↓
Email résolution → Client
```

---

## 🔧 Configuration Production

### Variables d'Environnement

```env
# .env.local
APP_ENV=prod
APP_DEBUG=0

# Database
DATABASE_URL=mysql://user:pass@localhost:3306/crm

# Stripe
STRIPE_SECRET_KEY=sk_live_...
STRIPE_PUBLIC_KEY=pk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...

# Email
MAILER_DSN=smtp://user:pass@smtp.example.com:465

# Support
SUPPORT_EMAIL=support@votre-entreprise.com

# Features
RATE_LIMIT_ENABLED=true
```

### Cron Jobs

```cron
# Dunning quotidien
0 9 * * * cd /path/to/app && php bin/console app:process-dunning

# Backups hebdomadaires
0 2 * * 0 cd /path/to/app && php bin/console app:backup-tenant all --compress

# Reset quotas
0 0 * * * cd /path/to/app && php bin/console app:quota:reset-daily
```

---

## 🤝 Support

### Questions ?

- 📧 **Email** : support@votre-entreprise.com
- 📖 **Docs** : Voir dossier `/docs`
- 🐛 **Bugs** : Ouvrir une issue GitHub
- 💬 **Slack** : #crm-support

### Contribuer

1. Fork le projet
2. Créer une branche (`git checkout -b feature/amazing`)
3. Commit changements (`git commit -m 'Add amazing feature'`)
4. Push vers branche (`git push origin feature/amazing`)
5. Ouvrir Pull Request

---

## 📈 Roadmap

### Q1 2026

- [ ] Analytics avancés (cohort analysis)
- [ ] Webhooks sortants
- [ ] Portail self-service client
- [ ] API publique documentée

### Q2 2026

- [ ] Multi-région (EU, US, APAC)
- [ ] Referral program
- [ ] Advanced quotas (soft/hard)
- [ ] ML churn prediction

### Q3 2026

- [ ] Marketplace intégrations
- [ ] White label
- [ ] SSO/SAML
- [ ] Compliance certifications

---

## 📜 License

MIT License - Voir [LICENSE](LICENSE)

---

## 🙏 Remerciements

- **Symfony** - Framework PHP
- **Stripe** - Gestion paiements
- **Doctrine** - ORM
- **Claude (Anthropic)** - Assistance implémentation

---

## 📞 Contact

**Équipe Développement**
- Email : dev@votre-entreprise.com
- Site : https://votre-entreprise.com

---

<div align="center">

**Fait avec ❤️ par votre équipe**

[Documentation](SAAS_IMPLEMENTATION_GUIDE.md) • [Analytics Guide](SUPER_ADMIN_ANALYTICS.md) • [Résumé Technique](IMPLEMENTATION_SUMMARY.md)

</div>
