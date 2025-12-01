# 🚀 ROADMAP BACKEND CONSOLIDÉE - CRM SaaS API

**Stack:** Symfony 7.2 + Doctrine ORM + JWT Auth + Stripe
**Status:** En cours
**Dernière mise à jour:** 2025-12-01

---

## 📊 ÉTAT DES LIEUX

### ✅ CE QUI EST FAIT (138 endpoints sur 23 controllers)

#### 🔐 Authentication & User Management (COMPLET)
| Controller | Endpoints | Status | Notes |
|------------|-----------|--------|-------|
| **AuthController** | 9 | ✅ 100% | Login, Register, Verify Email, Reset Password, Refresh, Logout, Me |
| **UserController** | 8 | ✅ 100% | CRUD Users, Roles, Profile |

#### 💳 Billing & SaaS (COMPLET)
| Controller | Endpoints | Status | Notes |
|------------|-----------|--------|-------|
| **PlanController** | 3 | ✅ 100% | List, Get by code, Compare plans |
| **SubscriptionController** | 10 | ✅ 100% | Subscribe, Change plan, Cancel, Usage, Payment methods |
| **StripeWebhookController** | 1 | ✅ 100% | Handle 15+ Stripe events |

**Entités Billing:**
- ✅ Plan (avec quotas)
- ✅ Subscription (lifecycle complet)
- ✅ Invoice
- ✅ Payment
- ✅ Quota (temps réel)
- ✅ UsageMetric (historique)

**Services:**
- ✅ StripeService (wrapper API Stripe complet)
- ✅ SubscriptionManager
- ✅ BillingManager
- ✅ QuotaManager

#### 📊 CRM Core (COMPLET)
| Controller | Endpoints | Status | Notes |
|------------|-----------|--------|-------|
| **ContactController** | 10 | ✅ 100% | List, Search, Info, Edit, Import CSV, Merge, Associate Company, Photo, Delete |
| **CompanyController** | 8 | ✅ 100% | List, Search, Info, Edit, Import, Merge, Photo, Delete |
| **DealController** | 10 | ✅ 100% | Edit, Info, Change step, Win/Lose, Dissociate, Delete |

#### 📅 Activities & Pipeline (COMPLET)
| Controller | Endpoints | Status | Notes |
|------------|-----------|--------|-------|
| **ActivityController** | 5 | ✅ 100% | List, Calendar, Show, Edit, Delete |
| **PipelineController** | 4 | ✅ 100% | List, Info, Edit, Delete |
| **PipelineStepController** | 4 | ✅ 100% | List, List by pipeline, Edit, Delete |

#### 📝 Content & Tags (COMPLET)
| Controller | Endpoints | Status | Notes |
|------------|-----------|--------|-------|
| **NoteController** | 2 | ✅ 100% | Edit, Delete |
| **TagController** | 3 | ✅ 100% | List, Create, Assign |

#### 📧 Communication (COMPLET)
| Controller | Endpoints | Status | Notes |
|------------|-----------|--------|-------|
| **MailController** | 3 | ✅ 100% | List, Edit, Delete |
| **PhoneNumberController** | 4 | ✅ 100% | List, Show, Edit, Delete |

#### 📁 Files & Export (COMPLET)
| Controller | Endpoints | Status | Notes |
|------------|-----------|--------|-------|
| **FileController** | 5 | ✅ 100% | List, Edit, Delete, Upload |
| **ExportController** | 1 | ✅ 100% | Export full CRM as ZIP |

#### ⚙️ Configuration (COMPLET)
| Controller | Endpoints | Status | Notes |
|------------|-----------|--------|-------|
| **PropertyController** | 3 | ✅ 100% | List, Edit, Delete |
| **PropertyModelController** | 3 | ✅ 100% | List, Edit, Delete |
| **ItemTypeController** | 4 | ✅ 100% | List, Show, Edit, Delete |

#### 👥 Administration (COMPLET)
| Controller | Endpoints | Status | Notes |
|------------|-----------|--------|-------|
| **AdminController** | 2 | ✅ 100% | Get routes, Save route requirements |
| **SyncController** | 8 | ✅ 100% | Sync operations |
| **ImportValidationController** | 6 | ✅ 100% | Import validation |

---

## ⚠️ CE QUI MANQUE OU DOIT ÊTRE AMÉLIORÉ

### 🔴 PRIORITÉ HAUTE - Essentiels pour MVP Frontend

#### 1. Dashboard & Analytics ⚠️ PARTIEL
**Status:** Endpoints manquants pour dashboard frontend

**Ce qui existe:**
- ✅ Comptage entités (via repositories)
- ✅ Export complet

**Ce qui manque:**
```php
// DashboardController (à créer)
GET  /api/dashboard/stats          // Stats globales
GET  /api/dashboard/recent-activities  // 10 dernières activités
GET  /api/dashboard/upcoming-deals     // Deals proches deadline
GET  /api/dashboard/tasks-today        // Tâches du jour
GET  /api/dashboard/pipeline-overview  // Vue d'ensemble pipeline
GET  /api/dashboard/revenue-chart      // Données pour graphiques
```

**Données nécessaires:**
```json
{
  "stats": {
    "total_contacts": 1250,
    "contacts_evolution": "+12%",  // vs mois dernier
    "total_companies": 340,
    "companies_evolution": "+5%",
    "active_deals": 45,
    "deals_value": 125000,
    "conversion_rate": 32.5,
    "conversion_trend": "+2.3%"
  },
  "charts": {
    "deals_by_month": [...],  // 12 derniers mois
    "pipeline_funnel": [...],
    "sources": [...]
  }
}
```

#### 2. Notifications System ❌ MANQUANT
**Status:** Complètement manquant

**Endpoints à créer:**
```php
// NotificationController (à créer)
GET    /api/notifications           // Liste notifications
GET    /api/notifications/unread    // Non lues (avec count)
POST   /api/notifications/{id}/read // Marquer comme lue
POST   /api/notifications/mark-all-read // Tout marquer lu
DELETE /api/notifications/{id}      // Supprimer
```

**Entité Notification (à créer):**
```php
class Notification {
    private User $user;
    private string $type;  // deal_won, new_contact, activity_due, mention
    private string $title;
    private string $message;
    private ?string $link;
    private bool $isRead = false;
    private \DateTimeImmutable $createdAt;
    private ?string $icon;
    private ?string $color;
}
```

**Types de notifications:**
- `deal_won` - Deal gagné
- `deal_lost` - Deal perdu
- `new_contact_assigned` - Nouveau contact assigné
- `activity_due` - Activité à venir (1h avant)
- `activity_overdue` - Activité en retard
- `mention` - Mention dans une note
- `new_comment` - Nouveau commentaire
- `subscription_trial_ending` - Trial se termine dans 3 jours
- `subscription_payment_failed` - Paiement échoué
- `quota_warning` - Quota à 80%
- `quota_exceeded` - Quota dépassé

#### 3. Global Search ⚠️ PARTIEL
**Status:** Search existe par entité, mais pas de search global

**Ce qui existe:**
- ✅ `GET /contact/search/{contains}`
- ✅ `GET /company/search/{contains}`

**Ce qui manque:**
```php
// SearchController (à créer)
GET /api/search?q={query}  // Search global toutes entités
```

**Réponse attendue:**
```json
{
  "status": "success",
  "query": "john",
  "results": {
    "contacts": [
      {"id": 1, "type": "contact", "name": "John Doe", "email": "...", "avatar": "..."}
    ],
    "companies": [
      {"id": 5, "type": "company", "name": "Johnson Corp", "logo": "..."}
    ],
    "deals": [
      {"id": 10, "type": "deal", "name": "John's Deal", "value": 50000}
    ],
    "activities": [...]
  },
  "total": 15
}
```

#### 4. Team Management ⚠️ PARTIEL
**Status:** UserController existe mais incomplet pour frontend

**Ce qui existe:**
- ✅ CRUD Users basique

**Ce qui manque:**
```php
// UserController - Endpoints supplémentaires
POST /api/users/invite           // Inviter par email
GET  /api/users/team             // Liste équipe (sans admins système)
PATCH /api/users/{id}/role       // Changer rôle
PATCH /api/users/{id}/activate   // Activer/désactiver
GET  /api/users/{id}/stats       // Stats utilisateur (deals, contacts)
```

**Entité UserInvitation (à créer):**
```php
class UserInvitation {
    private string $email;
    private string $token;
    private User $invitedBy;
    private array $roles;
    private \DateTimeImmutable $expiresAt;
    private bool $isUsed = false;
}
```

#### 5. Activity Types & Statuses ❌ MANQUANT
**Status:** Pas d'endpoints pour gérer les types d'activités

**Endpoints à créer:**
```php
// ActivityTypeController (à créer)
GET  /api/activity-types        // Liste types (Call, Email, Meeting, Task, Demo)
POST /api/activity-types        // Créer type personnalisé
```

**Amélioration ActivityController:**
```php
POST /api/activities/{id}/complete  // Marquer comme fait
POST /api/activities/{id}/cancel    // Annuler
GET  /api/activities/upcoming       // Activités à venir (7 jours)
GET  /api/activities/overdue        // Activités en retard
```

### 🟡 PRIORITÉ MOYENNE - Améliorations UX

#### 6. Commentaires / Timeline ❌ MANQUANT
**Status:** System de commentaires manquant

**Endpoints à créer:**
```php
// CommentController (à créer)
GET    /api/{entity}/{id}/comments       // Liste commentaires
POST   /api/{entity}/{id}/comments       // Ajouter commentaire
PUT    /api/comments/{id}                // Éditer
DELETE /api/comments/{id}                // Supprimer
POST   /api/comments/{id}/react          // Réaction emoji
```

**Entité Comment:**
```php
class Comment {
    private User $author;
    private string $entityType;  // contact, company, deal
    private string $entityId;
    private string $content;
    private ?Comment $parentComment;  // Pour threads
    private array $mentions = [];     // @user mentions
    private array $reactions = [];    // emoji reactions
    private \DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $editedAt;
}
```

#### 7. Bulk Operations ⚠️ PARTIEL
**Status:** Certaines bulk ops manquent

**Ce qui manque:**
```php
// BulkController (à créer)
POST /api/bulk/contacts/delete       // Suppression multiple
POST /api/bulk/contacts/tag          // Tagging multiple
POST /api/bulk/contacts/assign       // Assignation multiple
POST /api/bulk/deals/change-step     // Changer étape multiple
POST /api/bulk/activities/complete   // Compléter multiple
```

#### 8. Filters & Saved Views ❌ MANQUANT
**Status:** Filtres existent mais pas de sauvegarde

**Endpoints à créer:**
```php
// FilterController (à créer)
GET    /api/filters/{entity}          // Liste filtres sauvegardés
POST   /api/filters                   // Sauvegarder filtre
PUT    /api/filters/{id}              // Modifier
DELETE /api/filters/{id}              // Supprimer
POST   /api/filters/{id}/apply        // Appliquer filtre
```

**Entité SavedFilter:**
```php
class SavedFilter {
    private User $owner;
    private string $name;
    private string $entityType;  // contacts, companies, deals
    private array $filters;
    private bool $isDefault = false;
    private bool $isShared = false;
}
```

#### 9. Email Integration ❌ MANQUANT
**Status:** Aucune intégration email

**Endpoints à créer (si souhaité dans MVP):**
```php
// EmailController (à créer)
POST /api/emails/send              // Envoyer email depuis CRM
GET  /api/emails/templates         // Templates d'emails
POST /api/emails/templates         // Créer template
GET  /api/emails/history           // Historique emails envoyés
```

#### 10. Webhooks Outgoing ❌ MANQUANT
**Status:** Stripe webhooks IN existent, mais pas OUT

**Endpoints à créer (pour intégrations futures):**
```php
// WebhookController (à créer)
GET    /api/webhooks               // Liste webhooks configurés
POST   /api/webhooks               // Créer webhook
DELETE /api/webhooks/{id}          // Supprimer
GET    /api/webhooks/{id}/logs     // Logs deliveries
POST   /api/webhooks/{id}/test     // Test webhook
```

### 🟢 PRIORITÉ BASSE - Nice to Have

#### 11. Custom Fields UI ⚠️ PARTIEL
**Status:** PropertyModel existe mais pas d'UI management complet

**Améliorations:**
```php
// PropertyModelController - Endpoints supplémentaires
POST /api/property-models/{id}/reorder    // Réordonner champs
GET  /api/property-models/by-entity/{type} // Par type d'entité
POST /api/property-models/{id}/duplicate   // Dupliquer modèle
```

#### 12. Reports & Analytics ❌ MANQUANT
**Status:** Pas de système de rapports

**Endpoints future (V2):**
```php
GET /api/reports/revenue           // Rapport revenus
GET /api/reports/pipeline          // Rapport pipeline
GET /api/reports/activities        // Rapport activités
GET /api/reports/conversion        // Rapport conversion
POST /api/reports/custom           // Rapport personnalisé
```

#### 13. Automation Rules ❌ MANQUANT
**Status:** Pas d'automation

**Future (V2):**
```php
// AutomationController
GET  /api/automations              // Liste automations
POST /api/automations              // Créer automation
```

**Exemples d'automation:**
- Deal à étape X → Créer activité
- Contact créé → Assigner à user
- Deal gagné → Envoyer email

#### 14. Integrations ❌ MANQUANT
**Status:** Seul Stripe intégré

**Future intégrations (V2):**
- Gmail/Outlook sync
- Calendar sync (Google/Outlook)
- Zapier webhooks
- Slack notifications
- WhatsApp integration

---

## 🔧 AMÉLIORATIONS TECHNIQUES NÉCESSAIRES

### 1. Quotas Enforcement ⚠️ À IMPLÉMENTER
**Status:** QuotaManager existe mais pas utilisé partout

**À faire:**
```php
// Middleware QuotaChecker (à créer)
- Vérifier avant création contact
- Vérifier avant création company
- Vérifier avant création deal
- Vérifier avant upload file
- Vérifier avant API call (si quota API)
```

**Intégrer dans controllers:**
```php
class ContactController {
    #[Route('/contact/edit')]
    public function edit(Request $request) {
        // ⚠️ AJOUTER ICI
        if (!$this->quotaManager->canCreateContact($tenantId)) {
            return $this->json([
                'status' => 'error',
                'message' => 'Quota contacts dépassé. Upgradez votre plan.',
                'code' => 'QUOTA_EXCEEDED'
            ], 403);
        }

        // ... reste du code
    }
}
```

### 2. Rate Limiting ❌ MANQUANT
**Status:** Pas de rate limiting

**À implémenter:**
```yaml
# config/packages/rate_limiter.yaml
framework:
    rate_limiter:
        api:
            policy: 'sliding_window'
            limit: 100
            interval: '1 minute'
```

### 3. Audit Logs ⚠️ PARTIEL
**Status:** LoginHistory existe, mais pas d'audit général

**À créer:**
```php
class AuditLog {
    private User $user;
    private string $action;  // created, updated, deleted, viewed
    private string $entityType;
    private string $entityId;
    private ?array $changes;  // Old vs new values
    private string $ipAddress;
    private \DateTimeImmutable $createdAt;
}
```

**Endpoints:**
```php
GET /api/audit-logs                    // Liste (admin only)
GET /api/audit-logs/{entity}/{id}      // Par entité
```

### 4. Pagination Standardisée ⚠️ À UNIFORMISER
**Status:** Pagination existe mais format varie

**Standardiser:**
```json
{
  "status": "success",
  "data": [...],
  "pagination": {
    "page": 1,
    "limit": 25,
    "total": 1250,
    "pages": 50,
    "has_next": true,
    "has_prev": false
  }
}
```

### 5. Error Handling Uniforme ⚠️ À UNIFORMISER
**Status:** Gestion erreurs existe mais codes varie

**Standardiser codes erreur:**
```json
{
  "status": "error",
  "message": "Message utilisateur",
  "code": "VALIDATION_FAILED",
  "errors": {
    "email": ["Email déjà utilisé"],
    "password": ["Minimum 8 caractères"]
  },
  "timestamp": "2025-12-01T10:00:00Z"
}
```

**Codes standards:**
- `VALIDATION_FAILED` - Validation échouée
- `UNAUTHORIZED` - Non authentifié
- `FORBIDDEN` - Pas de permission
- `NOT_FOUND` - Ressource introuvable
- `QUOTA_EXCEEDED` - Quota dépassé
- `DUPLICATE_ENTRY` - Doublon
- `INTERNAL_ERROR` - Erreur serveur

### 6. Caching ⚠️ À IMPLÉMENTER
**Status:** Pas de cache

**À implémenter:**
```php
// Cache stats dashboard (5 min)
// Cache liste plans (1h)
// Cache quotas (30s)
```

### 7. Background Jobs ❌ MANQUANT
**Status:** Pas de queue

**À implémenter (Symfony Messenger):**
```php
// Jobs asynchrones
- Import CSV contacts (background)
- Export données (background)
- Envoi emails bulk
- Calcul statistiques lourdes
```

---

## 📋 PLAN D'ACTION PRIORITAIRE

### 🔥 Phase 1: Essentiels Dashboard (1-2 jours)
```
1. Créer DashboardController
2. Endpoints stats globales
3. Endpoints activités récentes
4. Endpoints deals upcoming
5. Endpoints données graphiques
```

### 🔥 Phase 2: Notifications (2-3 jours)
```
1. Créer entité Notification
2. Créer NotificationController
3. Créer NotificationService (génération notifications)
4. Intégrer dans events (deal won, etc.)
5. WebSocket/SSE pour temps réel (optionnel)
```

### 🔥 Phase 3: Search Global (1 jour)
```
1. Créer SearchController
2. Implémenter search multi-entités
3. Optimiser avec Elasticsearch (optionnel MVP)
```

### 🔥 Phase 4: Team Management (1-2 jours)
```
1. UserInvitation entité + controller
2. Endpoints invite/activate
3. Email service invitation
```

### 🔥 Phase 5: Quotas Enforcement (1 jour)
```
1. Créer QuotaChecker middleware
2. Intégrer dans tous les controllers création
3. Messages d'erreur clairs
```

### 🟡 Phase 6: Commentaires (2-3 jours)
```
1. Créer entité Comment
2. Créer CommentController
3. Supporter mentions @user
4. Reactions emoji
```

### 🟡 Phase 7: Bulk Operations (1-2 jours)
```
1. Créer BulkController
2. Implémenter delete/tag/assign bulk
3. Validation et sécurité
```

### 🟡 Phase 8: Polish Technique (2-3 jours)
```
1. Uniformiser pagination
2. Uniformiser error codes
3. Rate limiting
4. Caching stratégique
5. Audit logs
```

---

## 📊 RÉCAPITULATIF GAPS

| Catégorie | Status | Priorité | Effort |
|-----------|--------|----------|--------|
| **Dashboard Analytics** | ⚠️ 40% | 🔴 Haute | 2j |
| **Notifications** | ❌ 0% | 🔴 Haute | 3j |
| **Search Global** | ⚠️ 50% | 🔴 Haute | 1j |
| **Team Management** | ⚠️ 60% | 🔴 Haute | 2j |
| **Quotas Enforcement** | ⚠️ 30% | 🔴 Haute | 1j |
| **Commentaires** | ❌ 0% | 🟡 Moyenne | 3j |
| **Bulk Operations** | ⚠️ 20% | 🟡 Moyenne | 2j |
| **Saved Filters** | ❌ 0% | 🟡 Moyenne | 2j |
| **Email Integration** | ❌ 0% | 🟢 Basse | 5j |
| **Webhooks OUT** | ❌ 0% | 🟢 Basse | 3j |
| **Reports** | ❌ 0% | 🟢 Basse | 5j |
| **Automation** | ❌ 0% | 🟢 Basse | 10j |

**Total effort estimé (Priorités Hautes):** ~9 jours
**Total effort estimé (Toutes priorités):** ~39 jours

---

## ✅ DÉFINITION OF DONE - Backend MVP

Le backend est **production-ready pour MVP** quand:

### Fonctionnel
- [x] Auth JWT complète
- [x] CRUD toutes entités core (Contact, Company, Deal, Activity)
- [x] Billing SaaS avec Stripe
- [x] Quotas définis
- [ ] Dashboard analytics endpoints
- [ ] Notifications system
- [ ] Search global
- [ ] Team management complet
- [ ] Quotas enforcement actif

### Technique
- [x] 138 endpoints documentés
- [ ] Pagination uniformisée
- [ ] Error handling standardisé
- [ ] Rate limiting actif
- [ ] Caching stratégique
- [ ] Tests E2E critiques
- [ ] Documentation API à jour

### Sécurité
- [x] JWT authentication
- [x] Refresh tokens
- [x] Password hashing
- [ ] Rate limiting
- [ ] CORS configuré
- [ ] Validation inputs stricte
- [ ] Quotas enforced

---

## 🎯 ROADMAP BILLING SPÉCIFIQUE

### ✅ Déjà implémenté
- [x] Plans avec quotas multiples
- [x] Subscriptions avec lifecycle
- [x] Stripe integration complète
- [x] Webhook handling 15+ events
- [x] Payment methods management
- [x] Invoice generation
- [x] Usage tracking
- [x] Quota checking (service)

### ⚠️ À compléter
- [ ] Quota enforcement dans controllers
- [ ] Email notifications billing
  - Trial ending (3 days before)
  - Payment failed
  - Quota warnings (80%, 100%)
  - Invoice ready
- [ ] Commandes Symfony cron:
  - `app:subscriptions:process-renewals`
  - `app:subscriptions:check-trials`
  - `app:quotas:reset-daily`
  - `app:quotas:calculate-usage`
- [ ] Dashboard billing pour admin:
  - MRR/ARR
  - Churn rate
  - Active subscriptions
  - Revenue forecast

---

## 📞 NOTES IMPORTANTES

### Priorités Ajustables
Si MVP rapide (6 semaines):
- **Garder:** Dashboard basique, Quotas enforcement, Team management
- **Reporter V2:** Notifications, Commentaires, Saved Filters, Reports

### Dépendances Frontend
La roadmap frontend suppose:
1. ✅ Tous endpoints core existent (Fait)
2. ⚠️ Dashboard analytics endpoints (À faire)
3. ⚠️ Notifications endpoints (À faire)
4. ✅ Search par entité (Fait)
5. ⚠️ Search global (À faire)

### Intégrations Tierces
Pour V2.0:
- Zapier webhooks
- Email providers (SendGrid, Mailgun)
- Calendar sync (Google, Outlook)
- SMS providers (Twilio)
- WhatsApp Business API

---

**Version:** 2.0
**Date:** 2025-12-01
**Mainteneur:** Backend Team
**Status:** 🟡 MVP Backend 85% complet, gaps identifiés
