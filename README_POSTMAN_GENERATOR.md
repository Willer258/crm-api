# 📮 Générateur Automatique de Collections Postman

## 🚀 Quick Start

Générer la collection complète (116 endpoints) :
```bash
php bin/generate-postman-improved.php
```

Générer des collections séparées par catégorie :
```bash
php bin/generate-postman-improved.php --split
```

## 📊 Résultats

### Collection unique
- **Fichier:** `CRM_API_Complete.postman_collection.json` (233 KB)
- **Contenu:** 116 endpoints organisés en 9 catégories
- **Import:** Postman → Import → Sélectionner le fichier

### Collections séparées (--split)

| Collection | Endpoints | Taille | Description |
|------------|-----------|--------|-------------|
| `auth.postman_collection.json` | 9 | 15 KB | 🔐 Authentification (register, login, etc.) |
| `billing.postman_collection.json` | 14 | 24 KB | 💳 Plans, Subscriptions, Stripe |
| `crm___core.postman_collection.json` | 28 | 54 KB | 📊 Contacts, Companies, Deals |
| `crm___activities.postman_collection.json` | 13 | 21 KB | 📅 Activities, Pipelines |
| `crm___content.postman_collection.json` | 5 | 9 KB | 📝 Notes, Tags |
| `communication.postman_collection.json` | 7 | 12 KB | 📧 Emails, Phone Numbers |
| `files.postman_collection.json` | 6 | 9 KB | 📁 Files, Export |
| `configuration.postman_collection.json` | 10 | 15 KB | ⚙️ Properties, ItemTypes |
| `administration.postman_collection.json` | 24 | 47 KB | 👥 Users, Admin, Sync |

**Total: 116 endpoints** ✅

## 🎯 Options

```bash
# URL personnalisée
php bin/generate-postman-improved.php --base-url=https://api.production.com

# Fichier de sortie personnalisé
php bin/generate-postman-improved.php --output=MyAPI.json

# Combinaison
php bin/generate-postman-improved.php --split --base-url=https://staging.example.com
```

## ✨ Fonctionnalités

✅ **Détection automatique** de tous les endpoints
✅ **Méthodes HTTP** (GET, POST, PUT, PATCH, DELETE)
✅ **Descriptions** depuis les annotations Symfony
✅ **Paramètres** de path (ex: `/contact/{id}`)
✅ **Headers** d'authentification automatiques
✅ **Body examples** intelligents
✅ **Organisation** par catégories
✅ **Variables** d'environnement pré-configurées

## 📝 Variables d'environnement

Chaque collection inclut ces variables (auto-remplies après utilisation) :

- `base_url` - URL de l'API
- `jwt_token` - Token JWT (auto-rempli après login)
- `refresh_token` - Refresh token
- `tenant_id` - ID du tenant (default: tenant_123)
- `user_id` - ID utilisateur
- `id` - ID générique

## 📚 Documentation complète

Voir `docs/POSTMAN_GENERATOR.md` pour la documentation détaillée.

## 🔄 Régénération

Après avoir ajouté/modifié des endpoints :

```bash
# Régénérer
php bin/generate-postman-improved.php --split

# Re-importer dans Postman
```

---

**Généré automatiquement depuis les controllers Symfony** 🎉
