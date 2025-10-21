# Documentation API CRM

## Vue d'ensemble

Cette API CRM est construite avec Symfony 6+ et fournit une interface RESTful complète pour la gestion des relations clients. Elle supporte un système multi-tenant avec authentification JWT et gestion des permissions basée sur les rôles.

### Informations générales
- **Base URL**: Configurée selon l'environnement
- **Authentification**: JWT Token + API Key
- **Format des réponses**: JSON
- **Multi-tenancy**: Oui, via headers ou paramètres de requête

### Format de réponse standard
```json
{
    "status": "success|error",
    "data": {...},
    "message": "Message optionnel"
}
```

---

## 🏢 Contacts

### GET /contact/list
Récupère la liste paginée des contacts.

**Corps de requête** (JSON):
```json
{
    "pagination": {
        "page": 1,
        "limit": 25
    },
    "filters": {
        "search": "terme de recherche"
    }
}
```

**Réponse**:
```json
{
    "status": "success",
    "contacts": [...],
    "page": 1,
    "limit": 25,
    "total": 150
}
```

**Groupes de sérialisation**: `contact:list`

### GET /contact/info/{id}
Récupère les informations détaillées d'un contact.

**Paramètres**:
- `id` (int): ID du contact

**Réponse**:
```json
{
    "status": "success",
    "contact": {
        "id": 1,
        "source": "website",
        "manager": "user@example.com",
        "company": {...},
        "properties": [...],
        "deals": [...],
        "phones": [...],
        "mails": [...],
        "notes": [...],
        "activities": [...]
    }
}
```

**Groupes de sérialisation**: `contact:info`, `userManagement`, `infos`

### POST /contact/edit
Crée ou modifie un contact.

**Corps de requête**:
```json
{
    "id": 1,
    "source": "website",
    "manager": "user@example.com",
    "properties": [
        {
            "propertyModel": {"id": 1},
            "value": "John Doe"
        }
    ]
}
```

**Réponse**:
```json
{
    "status": "success",
    "contact": {...}
}
```

### POST /contact/import
Importe des contacts depuis un fichier.

**Paramètres**: 
- `file` (multipart): Fichier CSV/Excel

### GET /contact/associate/{id}/{idCompany}
Associe un contact à une entreprise.

### GET /contact/unassociate/{id}
Dissocie un contact d'une entreprise.

### GET /contact/merge/{sourceId}/{targetId}
Fusionne deux contacts.

### POST /contact/addPhoto
Ajoute une photo à un contact.

### DELETE /contact/delete/{id}
Supprime un contact.

---

## 🏢 Entreprises

### GET /company/list
Liste paginée des entreprises.

**Corps de requête** (JSON):
```json
{
    "pagination": {
        "page": 1,
        "limit": 25
    }
}
```

**Groupes de sérialisation**: `company:list`

### GET /company/search/{contains}
Recherche d'entreprises par nom.

**Paramètres**:
- `contains` (string): Terme de recherche

### GET /company/info/{id}
Informations détaillées d'une entreprise.

**Groupes de sérialisation**: `company:info`, `userManagement`, `infos`

### POST /company/import
Importe des entreprises depuis un fichier.

### POST /company/edit
Crée ou modifie une entreprise.

**Corps de requête**:
```json
{
    "id": 1,
    "properties": [
        {
            "propertyModel": {"id": 1},
            "value": "ACME Corp"
        }
    ],
    "tags": [...]
}
```

### POST /company/addPhoto
Ajoute un logo à l'entreprise.

---

## 💰 Affaires (Deals)

### POST /deal/edit
Crée ou modifie une affaire.

**Corps de requête**:
```json
{
    "id": 1,
    "object": "Vente logiciel CRM",
    "manager": "commercial@example.com",
    "contact": {"id": 1},
    "company": {"id": 1},
    "step": {"id": 1},
    "products": ["product1", "product2"],
    "participants": [{"id": 2}],
    "tags": [{"id": 1}]
}
```

**Groupes de sérialisation**: `contact:info`

### GET /deal/info/{id}
Informations détaillées d'une affaire.

**Groupes de sérialisation**: `deal:info`, `userManagement`, `infos`

### PATCH /deal/change/step/{id}
Change l'étape d'une affaire.

**Corps de requête**:
```json
{
    "step_id": 2
}
```

### GET /deal/win/{id}
Marque une affaire comme gagnée.

### GET /deal/lose/{id}
Marque une affaire comme perdue.

### GET /deal/unlose/unwin/{id}
Annule le statut gagné/perdu d'une affaire.

### DELETE /deal/delete/{id}
Supprime une affaire (suppression logique).

---

## 📋 Activités

### GET /activity/index
Liste des activités.

### GET /activity/calendar
Vue calendrier des activités.

### GET /activity/show/{id}
Détails d'une activité.

### POST /activity/edit
Crée ou modifie une activité.

**Corps de requête**:
```json
{
    "name": "Appel commercial",
    "type": "call",
    "startDate": "2024-01-15T14:00:00Z",
    "endDate": "2024-01-15T14:30:00Z",
    "location": "Bureau",
    "performed": false,
    "notify": true,
    "notifyDate": "2024-01-15T13:45:00Z",
    "description": "Appel de suivi",
    "deal": {"id": 1},
    "contact": {"id": 1},
    "company": {"id": 1}
}
```

### DELETE /activity/delete/{id}
Supprime une activité.

---

## 🔄 Pipelines

### GET /pipeline/list
Liste des pipelines.

**Groupes de sérialisation**: `pipeline:list`

### GET /pipeline/info/{id}
Détails d'un pipeline avec ses étapes.

**Groupes de sérialisation**: `pipeline:info`

### POST /pipeline/edit
Crée ou modifie un pipeline.

**Corps de requête**:
```json
{
    "name": "Ventes B2B",
    "description": "Pipeline pour les ventes B2B",
    "roles": ["ROLE_COMMERCIAL", "ROLE_MANAGER"]
}
```

### GET /pipeline/show/{id}
Affiche un pipeline spécifique.

### DELETE /pipeline/delete/{id}
Supprime un pipeline.

---

## 📊 Étapes de Pipeline

### GET /pipeline-step/list
Liste des étapes de pipeline.

### GET /pipeline-step/show/{id}
Détails d'une étape.

### POST /pipeline-step/edit
Crée ou modifie une étape.

**Corps de requête**:
```json
{
    "name": "Qualification",
    "description": "Qualification du prospect",
    "successProbability": 25.0,
    "color": "#007bff",
    "ranking": "1",
    "pipeline": {"id": 1}
}
```

### DELETE /pipeline-step/delete/{id}
Supprime une étape.

---

## 🏷️ Tags

### GET /tag/list
Liste des tags.

### GET /tag/show/{id}
Détails d'un tag.

### POST /tag/edit
Crée ou modifie un tag.

**Corps de requête**:
```json
{
    "label": "Client VIP",
    "code": "vip",
    "description": "Client à forte valeur",
    "color": "#ffc107"
}
```

### DELETE /tag/delete/{id}
Supprime un tag.

---

## 🔧 Types d'éléments

### GET /item-type/list
Liste des types d'éléments.

### GET /item-type/show/{id}
Détails d'un type avec ses modèles de propriétés.

### POST /item-type/edit
Crée ou modifie un type.

### DELETE /item-type/delete/{id}
Supprime un type.

---

## 📝 Modèles de propriétés

### GET /property-model/list
Liste des modèles de propriétés.

### GET /property-model/show/{id}
Détails d'un modèle.

### POST /property-model/edit
Crée ou modifie un modèle de propriété.

**Corps de requête**:
```json
{
    "label": "Prénom",
    "identifier": true,
    "type": "text",
    "class": "form-control",
    "itemType": {"id": 1}
}
```

**Types disponibles**:
- `text` - Texte simple
- `number` - Nombre  
- `datetime` - Date et heure
- `site` - URL de site web
- `localisation` - Adresse/localisation

### DELETE /property-model/delete/{id}
Supprime un modèle.

---

## 📄 Notes

### GET /note/index
Liste des notes.

### POST /note/edit
Crée ou modifie une note.

**Corps de requête**:
```json
{
    "content": "Note importante sur le client",
    "deal": {"id": 1},
    "contact": {"id": 1},
    "company": {"id": 1},
    "activity": {"id": 1}
}
```

---

## 📧 Emails et 📞 Téléphones

### POST /phone-number/edit
Gère les numéros de téléphone.

### POST /mail/index
Gère les adresses email.

---

## 📁 Fichiers (Assets)

### GET /file/list
Liste tous les fichiers/ressources.

**Réponse**:
```json
{
    "status": "success",
    "files": [
        {
            "id": 1,
            "name": "document.pdf",
            "size": 1024,
            "mimeType": "application/pdf",
            "contact": {...},
            "company": {...},
            "deal": {...}
        }
    ]
}
```

**Groupes de sérialisation**: `file:list`

### POST /file/edit
Crée ou modifie un fichier/ressource.

**Corps de requête**:
```json
{
    "id": 1,
    "name": "Contrat signé.pdf",
    "description": "Contrat client signé",
    "contact": {"id": 1},
    "company": {"id": 1},
    "deal": {"id": 1}
}
```

**Réponse**:
```json
{
    "status": "success",
    "file": {...}
}
```

**Groupes de sérialisation**: `file:edit`

### DELETE /file/delete/{id}
Supprime un fichier.

**Réponse**:
```json
{
    "status": "success",
    "message": "Fichier supprimé"
}
```

---

## 📧 Gestion des Emails

### GET /mail/
Liste toutes les adresses email.

**Réponse**:
```json
[
    {
        "id": 1,
        "email": "contact@example.com",
        "type": "professionnel",
        "contact": {...},
        "company": {...}
    }
]
```

### POST /mail/edit
Crée ou modifie une adresse email.

**Corps de requête**:
```json
{
    "id": 1,
    "email": "nouveau@example.com",
    "type": "personnel",
    "contact": {"id": 1},
    "company": {"id": 1}
}
```

**Réponse**:
```json
{
    "status": "success",
    "mail": {...}
}
```

**Groupes de sérialisation**: `contact:info`

### DELETE /mail/delete/{id}
Supprime une adresse email.

**Réponse**:
```json
{
    "status": "success",
    "message": "Mail supprimé"
}
```

---

## 📞 Numéros de téléphone

### GET /phone/number/
Liste tous les numéros de téléphone.

**Réponse**:
```json
[
    {
        "id": 1,
        "number": "+33 1 23 45 67 89",
        "type": "mobile",
        "contact": {...},
        "company": {...}
    }
]
```

### GET /phone/number/{id}
Affiche un numéro de téléphone spécifique.

### POST /phone/number/edit
Crée ou modifie un numéro de téléphone.

**Corps de requête**:
```json
{
    "id": 1,
    "number": "+33 6 12 34 56 78",
    "type": "mobile",
    "contact": {"id": 1},
    "company": {"id": 1}
}
```

**Réponse**:
```json
{
    "status": "success",
    "phoneNumber": {...}
}
```

**Groupes de sérialisation**: `contact:info`

### DELETE /phone/number/delete/{id}
Supprime un numéro de téléphone.

**Réponse**:
```json
{
    "status": "success",
    "message": "Numéro supprimé"
}
```

---

## 📝 Notes (complétées)

### POST /note/edit
Crée ou modifie une note.

**Corps de requête**:
```json
{
    "id": 1,
    "content": "Note importante concernant le client",
    "deal": {"id": 1},
    "contact": {"id": 1},
    "company": {"id": 1},
    "activity": {"id": 1}
}
```

**Réponse**:
```json
{
    "status": "success",
    "note": {
        "id": 1,
        "content": "Note importante concernant le client",
        "createdAt": "2024-01-15T10:00:00Z",
        "createBy": "user@example.com"
    }
}
```

**Groupes de sérialisation**: `note:edit`, `userManagement`, `infos`

### DELETE /note/delete/{id}
Supprime une note.

**Réponse**:
```json
{
    "status": "success",
    "message": "Note supprimée"
}
```

---

## 🔧 Administration

### GET /admin/get/routes
Récupère la liste complète des endpoints avec leurs permissions.

**Réponse**:
```json
{
    "status": "success",
    "routes": [
        {
            "path": "/contact/list",
            "name": "app_contact_list",
            "options": {
                "description": "Liste tous les contacts"
            },
            "roles": ["ROLE_USER", "ROLE_COMMERCIAL"]
        }
    ]
}
```

### POST /admin/save/route/requirements
Enregistre les droits nécessaires pour accéder à une route.

**Corps de requête**:
```json
{
    "name": "app_contact_list",
    "path": "/contact/list",
    "roles": ["ROLE_USER", "ROLE_COMMERCIAL"],
    "options": {
        "description": "Liste tous les contacts"
    }
}
```

**Réponse**:
```json
{
    "status": "success",
    "routes": {...}
}
```

---

## 📊 Export complet

### GET /export-full
Exporte toutes les données CRM au format ZIP contenant des fichiers CSV.

**Réponse**: Fichier ZIP contenant:
- `contacts.csv` - Export des contacts avec toutes leurs propriétés
- `companies.csv` - Export des entreprises avec toutes leurs propriétés

**Headers de réponse**:
```
Content-Type: application/zip
Content-Disposition: attachment; filename="export_crm.zip"
```

**Structure des CSV**:

**contacts.csv**:
```csv
item_type,email,number,tags,Prénom,Nom,Entreprise,...
contact,john@example.com,,VIP;Prospect,John,Doe,ACME Corp,...
```

**companies.csv**:
```csv
item_type,email,number,tags,Nom entreprise,Secteur,...
company,contact@acme.com,,Client,ACME Corp,Technologie,...
```

---

## 🔐 Authentification

L'API utilise l'authentification JWT. Incluez le token dans le header :
```
Authorization: Bearer <your-jwt-token>
```

Pour l'authentification par API Key :
```
X-API-KEY: <your-api-key>
```

---

## 🏢 Multi-tenancy

Le système supporte plusieurs tenants. Spécifiez le tenant via :
- Header: `X-Tenant: tenant_name`
- Paramètre de requête: `?tenant=tenant_name`

---

## 📋 Codes de réponse

- `200` - Succès
- `201` - Créé avec succès
- `400` - Requête invalide
- `401` - Non autorisé
- `403` - Accès refusé
- `404` - Ressource non trouvée
- `422` - Erreur de validation
- `500` - Erreur serveur

---

## 🔍 Groupes de sérialisation

Les réponses utilisent différents groupes de sérialisation selon le contexte :

- `list` - Vue liste (données minimales)
- `info` - Vue détaillée
- `edit` - Données d'édition
- `userManagement` - Informations de gestion utilisateur
- `infos` - Métadonnées générales
- `pipeline:info` - Informations complètes du pipeline

---

## 🚀 Exemples d'utilisation

### Créer un contact complet
```bash
curl -X POST https://api.crm.example.com/contact/edit \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -d '{
    "source": "website",
    "manager": "commercial@example.com",
    "properties": [
      {
        "propertyModel": {"id": 1},
        "value": "John Doe"
      },
      {
        "propertyModel": {"id": 2}, 
        "value": "john@example.com"
      }
    ],
    "company": {"id": 1},
    "tags": [{"id": 1}]
  }'
```

### Créer une affaire
```bash
curl -X POST https://api.crm.example.com/deal/edit \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -d '{
    "object": "Vente CRM Enterprise",
    "contact": {"id": 1},
    "company": {"id": 1},
    "step": {"id": 1},
    "products": ["CRM_ENTERPRISE", "SUPPORT_PREMIUM"]
  }'
```

---

## 🚀 Fonctionnalités avancées

### Propriétés personnalisées (Properties)
Le système utilise un mécanisme de propriétés dynamiques via les entités `Property` et `PropertyModel`:

**Types de propriétés disponibles**:
- `text` - Texte simple (nom, description, etc.)
- `number` - Valeurs numériques  
- `datetime` - Dates et heures
- `site` - URLs de sites web
- `localisation` - Adresses géographiques

**Exemple d'usage**:
```json
{
    "properties": [
        {
            "propertyModel": {"id": 1},
            "value": "John Doe"
        },
        {
            "propertyModel": {"id": 2},
            "value": "john.doe@example.com"
        }
    ]
}
```

### Système de Tags
Les tags permettent de catégoriser tous les éléments du CRM:
- **Contacts**: Classification par type de prospect, statut, etc.
- **Entreprises**: Secteur d'activité, taille, importance
- **Affaires**: Type de vente, produit, urgence

### Pipeline de vente configurable
- **Pipelines multiples**: Différents processus de vente
- **Étapes personnalisables**: Nom, couleur, probabilité de succès
- **Gestion des transitions**: Suivi automatique des changements d'étape

### Gestion multi-tenant
- **Isolation des données**: Chaque tenant a ses propres données
- **Configuration par tenant**: Pipelines, tags, types spécifiques
- **Authentification séparée**: Utilisateurs isolés par tenant

### Traçabilité complète (UserObjectTrait)
Tous les objets métier incluent automatiquement:
- **Audit trail**: Qui a créé/modifié/supprimé quoi et quand
- **Adresses IP**: Traçage des actions par adresse IP
- **UUID**: Identifiants uniques universels
- **Suppression logique**: `removeAt` au lieu de suppression physique

### Import/Export de données
- **Import CSV/Excel**: Contacts et entreprises via fichiers
- **Export complet**: Toutes les données au format ZIP/CSV
- **Mapping automatique**: Association des colonnes aux propriétés

### Système de notifications
- **Activités programmées**: Notifications avant échéances
- **États des affaires**: Alertes sur changements de statut
- **Intégration Firebase**: Push notifications en temps réel

---

## 🔍 Cas d'usage avancés

### Créer un pipeline commercial complet
```bash
# 1. Créer le pipeline
curl -X POST /pipeline/edit \
  -H "Authorization: Bearer TOKEN" \
  -d '{
    "name": "Ventes B2B Tech",
    "description": "Pipeline pour produits technologiques B2B",
    "roles": ["ROLE_COMMERCIAL", "ROLE_MANAGER"]
  }'

# 2. Créer les étapes
curl -X POST /pipeline-step/edit \
  -d '{
    "name": "Qualification",
    "successProbability": 10,
    "color": "#ffc107",
    "ranking": "1",
    "pipeline": {"id": 1}
  }'

curl -X POST /pipeline-step/edit \
  -d '{
    "name": "Proposition",
    "successProbability": 50,
    "color": "#007bff",
    "ranking": "2",
    "pipeline": {"id": 1}
  }'
```

### Workflow complet de gestion client
```bash
# 1. Créer l'entreprise
curl -X POST /company/edit \
  -d '{
    "properties": [
      {"propertyModel": {"id": 1}, "value": "ACME Corp"},
      {"propertyModel": {"id": 2}, "value": "Technologie"}
    ],
    "tags": [{"id": 1}]
  }'

# 2. Créer le contact principal
curl -X POST /contact/edit \
  -d '{
    "source": "website",
    "company": {"id": 1},
    "properties": [
      {"propertyModel": {"id": 10}, "value": "John Doe"},
      {"propertyModel": {"id": 11}, "value": "Directeur IT"}
    ]
  }'

# 3. Créer l'affaire
curl -X POST /deal/edit \
  -d '{
    "object": "Migration vers le cloud",
    "contact": {"id": 1},
    "company": {"id": 1},
    "step": {"id": 1},
    "products": ["CLOUD_MIGRATION", "SUPPORT_PREMIUM"]
  }'

# 4. Programmer une activité
curl -X POST /activity/edit \
  -d '{
    "name": "Appel de qualification",
    "type": "call",
    "startDate": "2024-02-01T14:00:00Z",
    "deal": {"id": 1},
    "contact": {"id": 1}
  }'
```

### Recherche et filtrage avancé
```bash
# Recherche d'entreprises
curl -X GET "/company/search/technologie"

# Liste paginée avec filtres
curl -X POST /contact/list \
  -d '{
    "pagination": {"page": 1, "limit": 50},
    "filters": {
      "search": "john",
      "tags": ["vip", "prospect"],
      "company": "ACME"
    },
    "sort": {
      "field": "createdAt",
      "direction": "desc"
    }
  }'
```

---

## 🛠️ Bonnes pratiques d'utilisation

### 1. Authentification et sécurité
- Toujours inclure le token JWT dans le header `Authorization`
- Vérifier les permissions avant chaque action sensible
- Utiliser HTTPS en production
- Implémenter le refresh token pour les sessions longues

### 2. Gestion d'erreurs
```json
// Réponse d'erreur standard
{
    "status": "error",
    "message": "Contact non trouvé",
    "code": 404,
    "details": {
        "field": "id",
        "value": "999"
    }
}
```

### 3. Performance et pagination
- Toujours paginer les listes importantes (limit max: 100)
- Utiliser les groupes de sérialisation appropriés
- Éviter les requêtes en cascade (N+1 problème)
- Mettre en cache les données de référence (tags, types, etc.)

### 4. Multi-tenancy
```bash
# Spécifier le tenant via header
curl -H "X-Tenant: client_a" /contact/list

# Ou via paramètre
curl "/contact/list?tenant=client_a"
```

### 5. Workflow recommandé
1. **Import initial**: Utiliser `/contact/import` et `/company/import`
2. **Configuration**: Créer pipelines, étapes, tags, types
3. **Utilisation courante**: API CRUD standard
4. **Rapports**: Export périodique via `/export-full`

---

## 📈 Métriques et monitoring

### Endpoints de santé
```bash
# Vérifier l'état de l'API
curl /admin/get/routes

# Statistiques d'utilisation
curl /admin/stats
```

### Logs d'audit
Tous les objets incluent automatiquement:
- `createdAt` / `updatedAt` - Timestamps
- `createBy` / `updateBy` - Utilisateur responsable  
- `createdFromIp` / `updatedFromIp` - Adresse IP source
- `removeAt` / `removeBy` - Suppression logique

Cette documentation complète couvre l'ensemble des fonctionnalités de l'API CRM. Pour des besoins spécifiques ou des questions d'implémentation, consultez le code source des contrôleurs et managers correspondants.