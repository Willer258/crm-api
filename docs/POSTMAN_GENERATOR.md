# 🚀 Postman Collection Generator

Générateur automatique de collections Postman depuis les controllers Symfony.

## 📋 Vue d'ensemble

Ce générateur **parse automatiquement** tous vos controllers Symfony et génère des collections Postman complètes avec :

✅ Tous les endpoints détectés automatiquement
✅ Méthodes HTTP (GET, POST, PUT, PATCH, DELETE)
✅ Descriptions depuis les annotations
✅ Paramètres de path (ex: `/contact/{id}`)
✅ Exemples de body pour POST/PUT/PATCH
✅ Headers d'authentification automatiques
✅ Organisation intelligente par catégories
✅ Variables d'environnement pré-configurées

---

## 🎯 Utilisation

### 1️⃣ Collection unique (tout en un seul fichier)

```bash
php bin/console app:generate-postman-collection
```

**Résultat:**
- Fichier: `CRM_API_Complete.postman_collection.json`
- Contenu: TOUS les endpoints (138 routes)
- Organisation: Par catégories et controllers

### 2️⃣ Collections séparées par catégorie

```bash
php bin/console app:generate-postman-collection --split
```

**Résultat:** Plusieurs fichiers séparés :
- `auth.postman_collection.json` (10 routes)
- `billing.postman_collection.json` (13 routes)
- `crm_core.postman_collection.json` (29 routes)
- `crm_activities.postman_collection.json` (16 routes)
- `crm_content.postman_collection.json` (7 routes)
- `communication.postman_collection.json` (9 routes)
- `files.postman_collection.json` (7 routes)
- `configuration.postman_collection.json` (13 routes)
- `administration.postman_collection.json` (34 routes)

### 3️⃣ Options avancées

**Spécifier le fichier de sortie:**
```bash
php bin/console app:generate-postman-collection --output=MyAPI.postman_collection.json
```

**Spécifier l'URL de base:**
```bash
php bin/console app:generate-postman-collection --base-url=https://api.production.com
```

**Combiner les options:**
```bash
php bin/console app:generate-postman-collection \
  --split \
  --base-url=https://staging-api.example.com \
  --output=staging_collection.json
```

---

## 📦 Catégories générées automatiquement

Le générateur organise intelligemment vos endpoints en **9 catégories** :

| Catégorie | Emoji | Controllers | Routes |
|-----------|-------|-------------|--------|
| **Auth** | 🔐 | AuthController | 10 |
| **Billing** | 💳 | Plan, Subscription, StripeWebhook | 17 |
| **CRM - Core** | 📊 | Contact, Company, Deal | 31 |
| **CRM - Activities** | 📅 | Activity, Pipeline, PipelineStep | 16 |
| **CRM - Content** | 📝 | Note, Tag | 7 |
| **Communication** | 📧 | Mail, PhoneNumber | 9 |
| **Files** | 📁 | File, Export | 7 |
| **Configuration** | ⚙️ | Property, PropertyModel, ItemType | 13 |
| **Administration** | 👥 | User, Admin, Sync, ImportValidation | 28 |

---

## 🔍 Comment ça marche ?

### 1. Scan des controllers

```
src/Controller/
├── ContactController.php     ← 11 routes trouvées
├── CompanyController.php     ← 9 routes trouvées
├── DealController.php        ← 11 routes trouvées
└── ...                       ← 138 routes au total
```

### 2. Parsing des attributs

```php
#[Route('/contact/edit', name: 'edit', methods: ['POST'],
    options: ['description' => 'Crée ou modifie un contact'])]
public function edit(Request $request): JsonResponse
```

**Extrait:**
- Path: `/contact/edit`
- Méthode: `POST`
- Description: `Crée ou modifie un contact`

### 3. Génération Postman

```json
{
  "name": "Edit",
  "request": {
    "method": "POST",
    "url": "{{base_url}}/contact/edit",
    "header": [
      {
        "key": "Content-Type",
        "value": "application/json"
      }
    ],
    "body": {
      "mode": "raw",
      "raw": "{\n  \"email\": \"contact@example.com\",\n  \"firstname\": \"Jane\"\n}"
    },
    "description": "Crée ou modifie un contact"
  }
}
```

---

## ✨ Fonctionnalités intelligentes

### 🔐 Authentification automatique

Le générateur **détecte automatiquement** les endpoints publics vs protégés :

**Endpoints publics (pas d'auth):**
- `/auth/register`
- `/auth/login`
- `/auth/verify-email`
- `/webhooks/*`

**Endpoints protégés (JWT requis):**
- Tous les autres endpoints
- Header `Authorization: Bearer {{jwt_token}}` ajouté automatiquement

### 🏢 Multi-tenant automatique

Pour les endpoints nécessitant un tenant :
- Header `X-Tenant-ID: {{tenant_id}}` ajouté automatiquement
- Détecté sur les paths `/api/subscription/*`, `/contact/*`, etc.

### 📝 Génération de body examples

Le générateur crée des **exemples intelligents** selon l'endpoint :

**Login:**
```json
{
  "email": "user@example.com",
  "password": "SecurePassword123!"
}
```

**Register:**
```json
{
  "email": "user@example.com",
  "password": "SecurePassword123!",
  "firstname": "John",
  "lastname": "Doe"
}
```

**Subscribe:**
```json
{
  "plan_code": "pro_monthly",
  "email": "user@example.com",
  "payment_method_id": "pm_card_visa"
}
```

**List (pagination):**
```json
{
  "pagination": {
    "page": 1,
    "limit": 25
  },
  "filters": {}
}
```

### 🔗 Path parameters

Pour les routes avec paramètres :
```
/contact/info/{id}  →  /contact/info/:id
```

Variables Postman générées automatiquement :
```json
{
  "variable": [
    {
      "key": "id",
      "value": "example_id"
    }
  ]
}
```

---

## 📊 Structure de la collection générée

```
CRM API - Complete Collection
│
├── 🔐 Auth
│   └── AuthController
│       ├── Register
│       ├── Login
│       ├── Verify Email
│       ├── Refresh
│       └── ...
│
├── 💳 Billing
│   ├── PlanController
│   │   ├── List All Plans
│   │   ├── Get Plan by Code
│   │   └── Compare Plans
│   └── SubscriptionController
│       ├── Get Current Subscription
│       ├── Subscribe
│       ├── Change Plan
│       └── ...
│
├── 📊 CRM - Core
│   ├── ContactController
│   │   ├── Search
│   │   ├── List
│   │   ├── Info
│   │   ├── Edit
│   │   ├── Delete
│   │   └── ...
│   ├── CompanyController
│   │   └── ...
│   └── DealController
│       └── ...
│
└── ... (autres catégories)
```

---

## 🎨 Personnalisation

### Ajouter une nouvelle catégorie

Éditez `src/Command/GeneratePostmanCollectionCommand.php` :

```php
private function categorizeController(string $controllerName): string
{
    $categories = [
        // Existing categories...
        'Your Category' => ['YourController', 'AnotherController'],
    ];
    // ...
}
```

### Modifier les emojis

```php
private function getCategoryEmoji(string $category): string
{
    $emojis = [
        'Your Category' => '🎯',
        // ...
    ];
    // ...
}
```

### Ajouter des body examples personnalisés

```php
private function generateBodyExample(array $route): string
{
    if (strpos($route['path'], '/your/endpoint') !== false) {
        return json_encode([
            'your' => 'data',
        ], JSON_PRETTY_PRINT);
    }
    // ...
}
```

---

## 🔄 Workflow recommandé

### 1. Développement

Après avoir ajouté/modifié des endpoints :

```bash
# Régénérer la collection
php bin/console app:generate-postman-collection --split

# Re-importer dans Postman
# File → Import → Sélectionner les fichiers JSON
```

### 2. Pour chaque environnement

**Local:**
```bash
php bin/console app:generate-postman-collection \
  --base-url=http://localhost:8000 \
  --output=local_collection.json
```

**Staging:**
```bash
php bin/console app:generate-postman-collection \
  --base-url=https://staging-api.example.com \
  --output=staging_collection.json
```

**Production:**
```bash
php bin/console app:generate-postman-collection \
  --base-url=https://api.example.com \
  --output=production_collection.json
```

### 3. CI/CD Integration

Ajoutez à votre pipeline :

```yaml
# .github/workflows/generate-docs.yml
- name: Generate Postman Collection
  run: php bin/console app:generate-postman-collection --split

- name: Commit collections
  run: |
    git add *.postman_collection.json
    git commit -m "docs: Update Postman collections"
    git push
```

---

## 📝 Variables d'environnement générées

Chaque collection inclut ces variables :

| Variable | Valeur par défaut | Description |
|----------|-------------------|-------------|
| `base_url` | `http://localhost:8000` | URL de l'API |
| `jwt_token` | _(vide)_ | Token JWT (auto-rempli après login) |
| `refresh_token` | _(vide)_ | Refresh token |
| `tenant_id` | `tenant_123` | ID du tenant |
| `user_id` | _(vide)_ | ID utilisateur |

---

## 🐛 Troubleshooting

### Erreur: "Class not found"

**Cause:** Autoload Composer pas à jour

**Solution:**
```bash
composer dump-autoload
php bin/console cache:clear
```

### Erreur: "No routes found"

**Cause:** Controllers pas dans `src/Controller/`

**Solution:** Vérifier que vos controllers sont dans le bon dossier

### Collection vide

**Cause:** Attributs `#[Route]` mal formatés

**Solution:** Vérifier la syntaxe PHP 8 des attributs :
```php
#[Route('/path', name: 'name', methods: ['GET'])]
```

---

## 🎯 Exemples de sortie

### Console output

```
🚀 Postman Collection Generator
================================

📁 Scanning controllers...
  ✓ Found: ContactController.php
  ✓ Found: CompanyController.php
  ✓ Found: DealController.php
  ...
  📊 Total: 23 controllers

🔍 Parsing routes...
  📊 Total: 138 routes found

📦 Generating collection(s)...
  ✓ Saved: auth.postman_collection.json (1 controllers)
  ✓ Saved: billing.postman_collection.json (3 controllers)
  ✓ Saved: crm_core.postman_collection.json (3 controllers)
  ...

✅ Postman collection(s) generated successfully!
```

---

## 📚 Ressources

- **Postman Collection v2.1 Format:** https://schema.postman.com/
- **Symfony Routing:** https://symfony.com/doc/current/routing.html
- **PHP Reflection:** https://www.php.net/manual/en/book.reflection.php

---

## 🚀 Améliorations futures

Fonctionnalités à ajouter:

- [ ] Support des annotations Swagger/OpenAPI
- [ ] Génération des tests automatiques
- [ ] Export vers d'autres formats (OpenAPI, Insomnia)
- [ ] Détection des request body depuis les entités Doctrine
- [ ] Génération d'exemples de réponses
- [ ] Support des query parameters
- [ ] Détection des validations Symfony
- [ ] Génération de documentation Markdown

---

**Créé par:** CRM API Development Team
**Version:** 1.0
**Date:** 2025-11-29
