# Guide Analytics & Monitoring Super Admin

## 🎯 Points d'Analyse Critiques

En tant que **Super Administrateur** (gestionnaire du projet SaaS), voici tous les points d'analyse disponibles pour monitorer, supporter et gérer votre plateforme.

---

## 📊 1. DASHBOARD PRINCIPAL

### API Endpoint
```http
GET /api/super-admin/dashboard
Authorization: Bearer {super_admin_token}
```

### Métriques Affichées

#### A. Résumé Global
- **Total Tenants** : Nombre total de clients
- **Abonnements Actifs** : Clients payants actuels
- **Nouveaux ce Mois** : Croissance mensuelle
- **Churn ce Mois** : Clients perdus
- **Taux d'Activation** : Conversion trial → payant

#### B. Revenus
- **MRR** (Monthly Recurring Revenue) : Revenus récurrents mensuels
- **ARR** (Annual Recurring Revenue) : MRR × 12
- **Tendances** : Graphique évolution 12 derniers mois
- **Répartition par Plan** : Starter, Business, Enterprise

#### C. Santé Système
- **Base de Données** : Status + temps de réponse
- **Stockage** : Utilisation disque (%, GB)
- **API Performance** : Temps de réponse moyen
- **Jobs Background** : File d'attente

#### D. Croissance
- **Taux de Croissance** : % variation mensuelle
- **Nouveaux Tenants** : Tendance mensuelle
- **Taux de Rétention** : 100% - Churn Rate

---

## 👥 2. GESTION DES TENANTS

### Voir Tous les Tenants

```http
GET /api/super-admin/tenants
Params:
  - status: active|trialing|past_due|canceled
  - plan: starter|business|enterprise
  - search: acme
```

### Informations par Tenant

Pour **chaque tenant**, vous voyez :

#### Informations Générales
- **ID Tenant** : acme-corp
- **Status Abonnement** : active, trialing, past_due, canceled
- **Plan** : Nom, prix, devise, intervalle facturation
- **Âge** : Jours depuis création
- **Contribution MRR** : Revenu mensuel généré

#### Dates Importantes
- **Créé le** : Date inscription
- **Période actuelle** : Début et fin de facturation
- **Fin trial** : Si en période d'essai
- **Annulé le** : Si applicable

#### Indicateurs
- ✅ **Est actif** : Oui/Non
- 🎯 **En trial** : Oui/Non
- ⚠️ **En retard paiement** : Oui/Non

### Détails Complets d'un Tenant

```http
GET /api/super-admin/tenants/acme-corp
```

#### Retourne :

**1. Informations Abonnement**
- Détails complets plan
- Historique changements plan
- IDs Stripe (customer, subscription)

**2. Base de Données**
- Nom BDD : `wiassur_crm_db`
- Taille : 450 MB
- Nombre de tables : 25
- Existe : Oui/Non

**3. Métriques d'Utilisation**
```json
{
  "contacts_count": 1250,
  "companies_count": 342,
  "deals_count": 89,
  "users_count": 5,
  "storage_used_mb": 380,
  "api_calls_today": 2450
}
```

**4. Historique Facturation**
- Liste des factures
- Montants payés
- Dates de paiement
- Status (paid, failed, pending)

**5. Activité Récente**
- Dernières actions
- Logs d'audit
- Changements importants

**6. Tickets Support**
- Tickets ouverts
- Tickets résolus
- Temps moyen de résolution

---

## 💰 3. MÉTRIQUES FINANCIÈRES DÉTAILLÉES

### MRR (Monthly Recurring Revenue)

```http
GET /api/super-admin/metrics?type=mrr
```

**Analyse :**
```json
{
  "mrr": 15420.50,
  "total_subscriptions": 145,
  "breakdown": {
    "monthly_subscriptions": 120,
    "yearly_subscriptions": 25,
    "monthly_mrr": 13200.00,
    "yearly_mrr": 2220.50
  }
}
```

**Interprétation :**
- MRR croissant = Bonne santé
- Comparer mois par mois
- Objectif : Croissance >5%/mois

### ARR (Annual Recurring Revenue)

```json
{
  "arr": 185046.00,
  "mrr": 15420.50
}
```

### Churn Rate

```http
GET /api/super-admin/metrics?type=churn
```

```json
{
  "customers_at_start": 145,
  "churned_customers": 5,
  "churn_rate": 3.45,
  "retention_rate": 96.55
}
```

**Santé Business :**
- < 3% : Excellent
- 3-5% : Bon
- 5-7% : Acceptable
- > 7% : Problème

### LTV (Lifetime Value)

```json
{
  "ltv": 2500.00,
  "arpu": 106.20,  // Average Revenue Per User
  "avg_lifespan_months": 23.5
}
```

**Utilité :**
- Détermine combien investir en marketing
- Comparer au CAC (voir ci-dessous)

### CAC (Customer Acquisition Cost)

**Nécessite vos coûts marketing :**

```http
POST /api/super-admin/metrics/cac
{
  "total_marketing_cost": 50000,
  "period_start": "2025-11-01",
  "period_end": "2025-11-30"
}
```

```json
{
  "cac": 425.50,
  "new_customers_acquired": 117,
  "ltv": 2500.00,
  "ltv_cac_ratio": 5.87,
  "health_status": "excellent"
}
```

**LTV:CAC Ratio :**
- > 3:1 = Excellent
- 2-3:1 = Bon
- 1-2:1 = Acceptable
- < 1:1 = Problème critique

### NRR (Net Revenue Retention)

```json
{
  "nrr": 112.5,  // > 100% = Expansion
  "start_mrr": 14000,
  "end_mrr": 15750,
  "expansion_mrr": 1750,
  "health_status": "healthy"
}
```

**Interprétation :**
- NRR > 100% : Clients dépensent plus (upgrades)
- NRR = 100% : Stable
- NRR < 100% : Downgrades/churn

---

## 🎫 4. SUPPORT TECHNIQUE

### Vue d'Ensemble Support

```http
GET /api/super-admin/support/statistics
```

**Métriques Clés :**

```json
{
  "total": 145,
  "by_status": {
    "open": 12,
    "in_progress": 8,
    "waiting_customer": 3,
    "resolved": 120,
    "closed": 2
  },
  "by_priority": {
    "urgent": 2,
    "high": 10,
    "medium": 85,
    "low": 48
  },
  "by_category": {
    "technical": 60,
    "billing": 25,
    "feature_request": 30,
    "bug": 20,
    "question": 10
  },
  "avg_response_time_minutes": 25,
  "avg_resolution_time_hours": 6.5
}
```

### KPIs Support à Surveiller

1. **First Response Time** : < 1h pour urgent
2. **Resolution Time** : < 24h pour high priority
3. **Customer Satisfaction** : > 90%
4. **Ticket Volume** : Tendance à la baisse = bon produit

### Gestion des Tickets

#### Lister tous les tickets
```http
GET /api/super-admin/support/tickets?status=open&priority=urgent
```

#### Détails d'un ticket
```http
GET /api/super-admin/support/tickets/123
```

**Contient :**
- Historique complet des messages
- Tenant concerné
- Temps écoulé
- Agent assigné
- Tags & métadonnées

#### Assigner un ticket
```http
PATCH /api/super-admin/support/tickets/123/assign
{
  "agent_user_id": 5,
  "agent_name": "Sarah Johnson"
}
```

#### Répondre
```http
POST /api/super-admin/support/tickets/123/messages
{
  "message": "J'ai identifié le problème...",
  "is_from_agent": true,
  "is_internal": false  // false = client voit
}
```

#### Notes internes
```http
POST /api/super-admin/support/tickets/123/messages
{
  "message": "Problème lié au quota contacts",
  "is_from_agent": true,
  "is_internal": true  // true = client ne voit pas
}
```

---

## 🔧 5. ACTIONS SUPER ADMIN

### Provisionner un Nouveau Tenant

```http
POST /api/super-admin/tenants/provision
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

**Actions automatiques :**
1. Création BDD `eu_crm_db`
2. Exécution migrations
3. Initialisation données par défaut
4. Création utilisateur admin
5. Initialisation onboarding

### Suspendre un Tenant (Impayé)

```http
PATCH /api/super-admin/tenants/acme-corp/access
{
  "enabled": false,
  "reason": "Payment overdue 30 days"
}
```

**Effet :**
- Désactive accès API
- Mode lecture seule (optionnel)
- Notification client

### Réactiver un Tenant

```http
PATCH /api/super-admin/tenants/acme-corp/access
{
  "enabled": true,
  "reason": "Payment received"
}
```

### Impersonation (Se Connecter en tant que Client)

**Pour débugger ou assister un client :**

```http
POST /api/super-admin/tenants/acme-corp/impersonate
```

**Retourne :**
```json
{
  "impersonation_token": "imp_a1b2c3d4...",
  "expires_at": "2025-12-10 11:30:00"
}
```

**Utilisation :**
```http
GET /api/contact/list
Authorization: Bearer {impersonation_token}
X-Tenant-ID: acme-corp
```

Vous voyez exactement ce que le client voit.

---

## 📈 6. TENDANCES & FORECASTING

### Tendances Revenus (12 Mois)

```http
GET /api/super-admin/metrics/export
```

**Graphiques recommandés :**

1. **MRR Evolution**
```
Mois     | MRR
---------|--------
2024-12  | 12,450
2025-01  | 13,200
2025-02  | 14,100
2025-03  | 15,420
```

2. **Nouveaux vs Churned**
```
Mois     | Nouveaux | Churned | Net
---------|----------|---------|----
2025-01  | 15       | 3       | +12
2025-02  | 18       | 2       | +16
2025-03  | 12       | 5       | +7
```

3. **Répartition Plans**
```
Plan        | Count | MRR Contrib
------------|-------|------------
Starter     | 80    | 3,200
Business    | 50    | 7,500
Enterprise  | 15    | 4,720
```

### Prédictions (Formules)

**MRR Projeté (3 mois) :**
```
MRR_actuel × (1 + taux_croissance_moyen)^3
```

**Tenants dans 6 mois :**
```
Tenants_actuels × (1 + taux_croissance - churn_rate)^6
```

---

## 🚨 7. ALERTES & NOTIFICATIONS

### Créer des Alertes Automatiques

**Scénarios à monitorer :**

1. **Churn Rate > 5%** → Email urgent
2. **MRR diminue** → Investigation
3. **Ticket urgent non assigné** → Notif équipe
4. **Espace disque > 90%** → Alerte infrastructure
5. **Temps réponse API > 500ms** → Alerte performance
6. **Nouveau tenant** → Notif commercial
7. **Paiement échoue** → Dunning activé

### Exemple d'Implémentation

```php
// À ajouter dans un service de monitoring

if ($churnRate > 5) {
    $mailer->send(
        to: 'management@votre-entreprise.com',
        subject: '🚨 ALERTE: Churn Rate Élevé',
        body: "Churn rate atteint {$churnRate}%. Analyse requise."
    );
}
```

---

## 📊 8. RAPPORTS PERSONNALISÉS

### Export Mensuel Complet

```http
GET /api/super-admin/metrics/export
```

**Contenu :**
- Tous les KPIs
- Tendances graphiques
- Top 10 clients (MRR)
- Tickets support
- Santé système

### Rapport Exécutif (Board)

**KPIs Essentiels pour Direction :**

1. **ARR** : 185,046 €
2. **Croissance MRR** : +8.5% ce mois
3. **Clients Totaux** : 145
4. **Churn Rate** : 3.2%
5. **LTV:CAC** : 5.8:1
6. **NRR** : 112%

**Statut : 🟢 HEALTHY**

### Rapport Technique (CTO)

1. **Uptime** : 99.95%
2. **API Latency P95** : 180ms
3. **Database Size** : 45 GB
4. **Active Tenants DBs** : 142
5. **Backup Success Rate** : 100%
6. **Queue Jobs Pending** : 23

**Statut : 🟢 OPTIMAL**

---

## 🎓 9. FORMATION ÉQUIPE SUPPORT

### Points de Contrôle Support Agent

**Lorsqu'un ticket arrive :**

1. ✅ **Vérifier statut abonnement**
   - Client actif ?
   - Plan en cours ?
   - Paiement à jour ?

2. ✅ **Consulter quotas**
   - Limites atteintes ?
   - Usage actuel vs max

3. ✅ **Historique tickets**
   - Problèmes récurrents ?
   - Résolutions précédentes

4. ✅ **Logs d'activité**
   - Que faisait le client ?
   - Erreurs système ?

5. ✅ **Si besoin : Impersonation**
   - Reproduire le problème
   - Vérifier exact view client

### Escalade

**Quand escalader en Super Admin :**

- ❌ Problème technique complexe
- ❌ Demande de remboursement
- ❌ Bug critique affectant tous les tenants
- ❌ Demande de fonctionnalité majeure
- ❌ Menace juridique
- ❌ Demande d'export données (RGPD)

---

## 📋 10. CHECKLIST QUOTIDIENNE SUPER ADMIN

### Matin (15 min)

- [ ] Vérifier dashboard principal
- [ ] Checker nouveaux tickets urgents
- [ ] Voir résultats dunning (si jour ouvré)
- [ ] Vérifier santé système
- [ ] Nouveaux tenants créés hier

### Hebdomadaire (1h)

- [ ] Analyser MRR evolution
- [ ] Review churn clients
- [ ] Analyser tickets support (catégories)
- [ ] Vérifier backups
- [ ] Review quotas tenants proches limites

### Mensuel (3h)

- [ ] Rapport exécutif complet
- [ ] Analyse ROI marketing (CAC vs LTV)
- [ ] Tendances support
- [ ] Planification capacité (scaling)
- [ ] Review pricing si nécessaire

---

## 🔑 Points Clés de Décision

### Quand Augmenter Prix ?

**Indicateurs :**
- ✅ LTV:CAC > 5:1
- ✅ Churn < 3%
- ✅ NRR > 110%
- ✅ Feature set significativement amélioré

### Quand Ajouter un Plan ?

**Signaux :**
- 🎯 Beaucoup de downgrades Enterprise → Business
- 🎯 Clients "Starter" dépassent quotas
- 🎯 Demandes features intermédiaires

### Quand Investir en Marketing ?

**Si :**
- ✅ CAC < LTV/3
- ✅ Churn stable < 5%
- ✅ Product-market fit validé
- ✅ Support scalable

---

## 📞 CONTACT D'URGENCE

**Incidents Critiques :**
- Base de données down
- Paiements Stripe non traités
- Données client exposées
- Performance dégradée globalement

**Actions :**
1. Notifier équipe technique
2. Activer mode maintenance
3. Communication clients si nécessaire
4. Post-mortem après résolution

---

**Ce guide vous donne une vue 360° de votre plateforme SaaS.**
**Tous les outils sont en place pour monitorer, supporter et faire croître votre business.**

---

**Version** : 1.0.0
**Dernière mise à jour** : 2025-12-10
