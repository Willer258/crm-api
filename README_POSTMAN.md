# 📮 Collection Postman Complète - CRM API

## 🎯 Quick Start (30 secondes)

### 1. Importer dans Postman

```
1. Ouvrir Postman
2. Import → CRM_API_COMPLETE.postman_collection.json
3. Import → CRM_API_COMPLETE.postman_environment.json
4. Sélectionner l'environnement "CRM API - Complete (Local)"
```

### 2. Premier test

```
1. Auth > Login
2. ✅ Done! Le token et workspace sont automatiquement sauvegardés
3. Tous les autres endpoints fonctionnent maintenant
```

## ✨ Fonctionnalités

- ✅ **122 endpoints** - Couverture complète de l'API
- ✅ **22 scripts automatiques** - Capture automatique des tokens et IDs
- ✅ **23 variables** - jwt_token, workspace_id, contact_id, company_id, etc.
- ✅ **20 catégories** - Organisation logique (Auth, Workspace, Contact, Company, Deal, etc.)
- ✅ **Zéro copier/coller** - Tout est automatique!

## 📊 Couverture complète

| Catégorie | Endpoints | Auto-Capture |
|-----------|-----------|--------------|
| **Auth** | 9 | jwt_token, refresh_token, workspace_id, user_id |
| **Workspace** | 7 | workspace_id |
| **Contact** | 10 | contact_id |
| **Company** | 8 | company_id |
| **Deal** | 10 | deal_id |
| **Activity** | 5 | activity_id |
| **Pipeline** | 8 | pipeline_id, pipeline_step_id |
| **Tag** | 3 | tag_id |
| **Note** | 2 | note_id |
| **File** | 6 | file_id, file_uuid |
| **+ 10 autres** | 54 | mail_id, phone_id, property_id, etc. |

## 🚀 Workflows

### Workflow 1: Setup complet

```
1. Auth > Register (créer un compte)
2. Auth > Login (→ ✅ tokens sauvegardés)
3. Pipeline > Pipeline List (→ ✅ pipeline_id)
4. Contact > Contact Edit (→ ✅ contact_id)
5. Company > Company Edit (→ ✅ company_id)
6. Deal > Deal Edit (→ utilise automatiquement les IDs)
```

### Workflow 2: Tests quotidiens

```
1. Auth > Login
2. Contact > Contact List
3. Deal > Deal Info {id}
4. Activity > Activity Edit
```

## 🤖 Scripts automatiques

### Après Login

```javascript
✅ JWT Token saved
✅ Logged in - Workspace: Mon Workspace
```

Variables remplies:
- `{{jwt_token}}`
- `{{workspace_id}}`
- `{{user_id}}`

### Après Create Contact

```javascript
✅ Contact saved: Jean Dupont ID: 123
```

Variable remplie:
- `{{contact_id}}` = 123

### Console Postman

Ouvrir **View > Show Postman Console** pour voir tous les logs:

```
✅ Contact saved: Jean Dupont ID: 123
✅ Company saved: Acme Corp ID: 456
✅ Deal saved: Deal 50K€ ID: 789
```

## 📝 Exemples de données

Tous les body de requête incluent des exemples réalistes:

### Register
```json
{
    "email": "demo@example.com",
    "password": "Demo123456!",
    "firstName": "Demo",
    "lastName": "User"
}
```

### Create Contact
```json
{
    "firstName": "Jean",
    "lastName": "Dupont",
    "email": "jean.dupont@example.com",
    "phone": "+33612345678",
    "company": "{{company_id}}"
}
```

### Create Deal
```json
{
    "title": "Deal 50K€",
    "value": 50000,
    "currency": "EUR",
    "contact": "{{contact_id}}",
    "company": "{{company_id}}",
    "pipeline": "{{pipeline_id}}",
    "expectedCloseDate": "2025-12-31"
}
```

## 🔐 Authentification automatique

Headers ajoutés automatiquement à toutes les requêtes (sauf Auth publics):

```
Authorization: Bearer {{jwt_token}}
X-Workspace-Id: {{workspace_id}}
```

## 📁 Fichiers

- **CRM_API_COMPLETE.postman_collection.json** (144KB)
  - Collection complète avec 122 endpoints
  - Scripts automatiques intégrés

- **CRM_API_COMPLETE.postman_environment.json** (2.3KB)
  - 23 variables pré-configurées
  - URL base: http://crm-api.test

- **docs/POSTMAN_COMPLETE_GUIDE.md** (14KB)
  - Guide complet d'utilisation
  - Workflows détaillés
  - Troubleshooting
  - Cas d'usage avancés

- **bin/generate-complete-postman.php**
  - Script de génération automatique
  - À réexécuter si l'API évolue

## 🐛 Dépannage

### Erreur 401 Unauthorized
→ Exécuter `Auth > Login`

### Variable {{contact_id}} vide
→ Exécuter `Contact > Contact Edit` ou `Contact > Contact List`

### Workspace not found
→ Exécuter `Auth > Login` ou `Workspace > Workspace List`

## 💡 Astuces

1. **Console Postman** - Voir les logs de capture d'IDs
2. **Collection Runner** - Exécuter plusieurs requêtes en séquence
3. **Variables** - Utiliser `{{variable}}` dans tous les champs
4. **Export cURL** - Clic droit > Export > Copy as cURL

## 📊 Statistiques

- **122 endpoints** couverts
- **20 catégories** organisées
- **22 scripts auto** pour capture d'IDs
- **23 variables** gérées automatiquement
- **144KB** de collection
- **Gain de temps:** ~80% sur les tests API

## 🔄 Mise à jour

Si l'API évolue, régénérer la collection:

```bash
php bin/generate-complete-postman.php
```

## 📚 Documentation

- [Guide complet Postman](docs/POSTMAN_COMPLETE_GUIDE.md)
- [Documentation API](docs/API_DOCUMENTATION.md)
- [Architecture](IMPLEMENTATION_SUMMARY.md)

---

**Version:** 2.0.0
**Endpoints:** 122
**Auto-Scripts:** 22
**Variables:** 23
**Base URL:** http://crm-api.test
