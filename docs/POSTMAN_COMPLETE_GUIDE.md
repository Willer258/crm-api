# Guide Postman - Collection Complète CRM API

## 🎯 Vue d'ensemble

Collection Postman **COMPLÈTE** avec **122 endpoints** et **automation intelligente** pour faciliter les tests frontend.

**URL de base:** `http://crm-api.test`

### ✨ Fonctionnalités clés

- ✅ **Auto-capture JWT token** après login
- ✅ **Auto-capture workspace_id** automatiquement
- ✅ **Auto-capture tous les IDs** (contact, company, deal, activity, etc.)
- ✅ **22 scripts automatiques** pour les endpoints critiques
- ✅ **23 variables** pré-configurées
- ✅ **20 catégories** organisées logiquement
- ✅ **Exemples de données réalistes** dans tous les body
- ✅ **Zéro copier/coller manuel**

## 🚀 Installation rapide

### 1. Importer dans Postman

1. Ouvrir Postman
2. Cliquer sur **Import**
3. Glisser-déposer `CRM_API_COMPLETE.postman_collection.json`
4. Glisser-déposer `CRM_API_COMPLETE.postman_environment.json`
5. Sélectionner l'environnement "CRM API - Complete (Local)" (menu déroulant en haut à droite)

### 2. Démarrage immédiat

**Workflow minimal:**

```
1. Auth > Login
   → ✅ jwt_token, workspace_id, user_id sauvegardés automatiquement

2. Tout est prêt! Tous les autres endpoints fonctionnent maintenant
```

**Workflow complet (avec données de test):**

```
1. Auth > Register (optionnel)
2. Auth > Login
3. Pipeline > Pipeline List (pour avoir pipeline_id)
4. Contact > Contact Edit (créer un contact)
5. Company > Company Edit (créer une entreprise)
6. Contact > Contact Associate (associer contact à l'entreprise)
7. Deal > Deal Edit (créer une affaire)
8. Activity > Activity Edit (créer une activité)
```

## 📁 Structure de la collection (20 catégories)

### 1. Auth (9 endpoints)
| Endpoint | Méthode | Capture automatique |
|----------|---------|---------------------|
| Register | POST | `user_id`, `user_email` |
| Login | POST | `jwt_token`, `refresh_token`, `workspace_id`, `user_id` |
| Refresh | POST | `jwt_token` |
| Verify Email | POST | - |
| Resend Verification | POST | - |
| Logout | POST | - |
| Forgot Password | POST | - |
| Reset Password | POST | - |
| Me | GET | - |

### 2. Workspace (7 endpoints)
| Endpoint | Capture |
|----------|---------|
| Workspace List | `workspace_id` (premier) |
| Workspace Create | `workspace_id` |
| Workspace Info/{id} | - |
| Workspace Update/{id} | - |
| Workspace Switch | - |
| Workspace Members Add | - |
| Workspace Members Remove | - |

### 3. Contact (10 endpoints)
| Endpoint | Capture |
|----------|---------|
| Contact List | `contact_id` (premier) |
| Contact Search/{contains} | - |
| Contact Info/{id} | - |
| Contact Edit | `contact_id` |
| Contact Import | - |
| Contact Associate/{id}/{idCompany} | - |
| Contact Unassociate/{id} | - |
| Contact Merge/{sourceId}/{targetId} | - |
| Contact AddPhoto | - |
| Contact Delete/{id} | - |

### 4. Company (8 endpoints)
| Endpoint | Capture |
|----------|---------|
| Company List | `company_id` (premier) |
| Company Search/{contains} | - |
| Company Info/{id} | - |
| Company Edit | `company_id` |
| Company Import | - |
| Company Merge/{sourceId}/{targetId} | - |
| Company AddPhoto | - |
| Company Delete/{id} | - |

### 5. Deal (10 endpoints)
| Endpoint | Capture |
|----------|---------|
| Deal Edit | `deal_id` |
| Deal Info/{id} | - |
| Deal Change Step/{id} | - |
| Deal Win/{id} | - |
| Deal Lose/{id} | - |
| Deal Unlose Unwin/{id} | - |
| Deal Dissociate Contact/{id} | - |
| Deal Dissociate Company/{id} | - |
| Deal Remove Participant/{dealId}/{contactId} | - |
| Deal Delete/{id} | - |

### 6. Activity (5 endpoints)
| Endpoint | Capture |
|----------|---------|
| Activity List | `activity_id` (premier) |
| Activity Calendar | - |
| Activity/{id} | - |
| Activity Edit | `activity_id` |
| Activity Delete/{id} | - |

### 7. Pipeline (8 endpoints)
| Endpoint | Capture |
|----------|---------|
| Pipeline List | `pipeline_id` (premier) |
| Pipeline Info/{id} | `pipeline_step_id` (premier step) |
| Pipeline Edit | `pipeline_id` |
| Pipeline Delete/{id} | - |
| Pipeline Step List | `pipeline_step_id` (premier) |
| Pipeline Step List/{pipelineId} | - |
| Pipeline Step Edit | `pipeline_step_id` |
| Pipeline Step Delete/{id} | - |

### 8. Tag (3 endpoints)
| Endpoint | Capture |
|----------|---------|
| Tag List | `tag_id` (premier) |
| Tag Create | `tag_id` |
| Tag Assign | - |

### 9. Note (2 endpoints)
| Endpoint | Capture |
|----------|---------|
| Note Edit | `note_id` |
| Note Delete/{id} | - |

### 10. File (6 endpoints)
| Endpoint | Capture |
|----------|---------|
| File Uploader (chunked) | `file_uuid` |
| File Upload (simple) | `file_id` |
| File List | `file_id` (premier) |
| File Config | - |
| File Edit | - |
| File Delete/{id} | - |

### 11-20. Autres catégories
- **Mail** (3 endpoints): Liste, Edit, Delete
- **Phone** (4 endpoints): Liste, Show, Edit, Delete
- **Property** (6 endpoints): List, Edit, Delete + Property Model endpoints
- **Item** (4 endpoints): Item Type management
- **Import** (6 endpoints): Validation Contact/Company files
- **Export** (1 endpoint): Export-full
- **Api** (13 endpoints): API management endpoints
- **Webhooks** (1 endpoint): Stripe webhooks
- **Sync** (8 endpoints): Synchronization endpoints
- **User** (8 endpoints): User profile and management

## 🤖 Scripts automatiques - Comment ça marche

### Exemple 1: Login auto-save

Après exécution de `Auth > Login`:

```javascript
✅ JWT Token saved
✅ Logged in - Workspace: Mon Workspace
```

Variables automatiquement remplies:
- `{{jwt_token}}` = "eyJ0eXAiOiJKV1QiLCJhbGc..."
- `{{refresh_token}}` = "def502..."
- `{{workspace_id}}` = "1"
- `{{user_id}}` = "42"

### Exemple 2: Create Contact auto-save

Après exécution de `Contact > Contact Edit`:

```javascript
✅ Contact saved: Jean Dupont ID: 123
```

Variable automatiquement remplie:
- `{{contact_id}}` = "123"

### Exemple 3: List Contacts auto-save

Après exécution de `Contact > Contact List`:

```javascript
✅ First contact: Marie Martin ID: 456
```

Variable automatiquement remplie:
- `{{contact_id}}` = "456" (premier contact de la liste)

## 📊 Variables disponibles (23 au total)

| Variable | Description | Exemple |
|----------|-------------|---------|
| `base_url` | URL de l'API | `http://crm-api.test` |
| `jwt_token` | Token JWT actif | `eyJ0eXAi...` |
| `refresh_token` | Token de rafraîchissement | `def502...` |
| `workspace_id` | Workspace actif | `1` |
| `user_id` | ID utilisateur connecté | `42` |
| `user_email` | Email utilisateur | `demo@example.com` |
| `contact_id` | Dernier contact créé/listé | `123` |
| `company_id` | Dernière entreprise créée/listée | `456` |
| `deal_id` | Dernière affaire créée | `789` |
| `activity_id` | Dernière activité créée/listée | `111` |
| `pipeline_id` | Dernier pipeline créé/listé | `222` |
| `pipeline_step_id` | Dernière étape créée/listée | `333` |
| `tag_id` | Dernier tag créé/listé | `444` |
| `note_id` | Dernière note créée | `555` |
| `file_id` | Dernier fichier uploadé | `666` |
| `file_uuid` | UUID dernier fichier chunked | `uuid-...` |
| `mail_id` | Dernier email créé | `777` |
| `phone_id` | Dernier téléphone créé | `888` |
| `property_id` | Dernière propriété créée | `999` |
| `property_model_id` | Dernier modèle créé | `1010` |
| `item_type_id` | Dernier type d'item | `1111` |
| `subscription_id` | Dernière souscription | `1212` |
| `plan_id` | Dernier plan | `1313` |

## 🔐 Authentification

### Headers automatiques (au niveau collection)

Tous les endpoints (sauf Auth publics) ont automatiquement:

```
Authorization: Bearer {{jwt_token}}
X-Workspace-Id: {{workspace_id}}
```

### Endpoints publics (sans auth)

- POST /auth/register
- POST /auth/login
- POST /auth/verify-email
- POST /auth/forgot-password
- POST /auth/reset-password

## 📝 Exemples de données

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

### Create Company
```json
{
    "name": "Acme Corp",
    "email": "contact@acme.com",
    "phone": "+33123456789",
    "website": "https://acme.com"
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

### Create Activity
```json
{
    "title": "Appel de suivi",
    "type": "call",
    "contact": "{{contact_id}}",
    "scheduledAt": "2025-12-15T14:00:00Z",
    "duration": 30
}
```

## 🎯 Workflows recommandés

### Workflow 1: Setup complet (première fois)

```
1. Auth > Register
   → Créer un nouveau compte

2. Auth > Login
   → ✅ jwt_token, workspace_id sauvegardés

3. Pipeline > Pipeline List
   → ✅ pipeline_id sauvegardé

4. Contact > Contact Edit
   → ✅ contact_id sauvegardé

5. Company > Company Edit
   → ✅ company_id sauvegardé

6. Contact > Contact Associate {id} {idCompany}
   → Associer le contact à l'entreprise

7. Deal > Deal Edit
   → ✅ deal_id sauvegardé
   → Utilise automatiquement {{contact_id}}, {{company_id}}, {{pipeline_id}}

8. Activity > Activity Edit
   → ✅ activity_id sauvegardé
   → Utilise automatiquement {{contact_id}}
```

### Workflow 2: Tests quotidiens

```
1. Auth > Login
   → ✅ Tout est prêt

2. Contact > Contact List
   → Voir tous les contacts

3. Deal > Deal Info {id}
   → Détails d'une affaire

4. File > File Upload
   → Tester l'upload
```

### Workflow 3: Tests frontend intégration

```
1. Auth > Login
2. Contact > Contact List (pagination test)
3. Contact > Contact Search (search test)
4. Contact > Contact Edit (create test)
5. Contact > Contact Info {id} (read test)
6. Contact > Contact Edit (update test)
7. Contact > Contact Delete {id} (delete test)
```

## 🐛 Dépannage

### Erreur 401 Unauthorized

**Cause:** Token JWT expiré ou absent

**Solution:**
1. Exécuter `Auth > Login`
2. Vérifier que la variable `{{jwt_token}}` est remplie
3. Vérifier l'environnement sélectionné (menu en haut à droite)

### Erreur 400 Workspace not found

**Cause:** `workspace_id` vide ou invalide

**Solution:**
1. Exécuter `Auth > Login` (sauvegarde le workspace_id)
2. OU exécuter `Workspace > Workspace List` puis `Workspace > Workspace Switch`

### Erreur 404 Not Found avec {id}

**Cause:** Variable `{{contact_id}}` (ou autre) vide

**Solution:**
1. Exécuter d'abord `Contact > Contact Edit` pour créer un contact
2. OU exécuter `Contact > Contact List` pour capturer l'ID du premier

### Variable non capturée

**Cause:** Script de test non exécuté ou réponse inattendue

**Solution:**
1. Ouvrir la **Console Postman** (View > Show Postman Console)
2. Ré-exécuter la requête
3. Vérifier les logs console (messages ✅)
4. Vérifier la structure de la réponse JSON

### IDs ne correspondent pas

**Cause:** Vous utilisez des données de différents workspaces

**Solution:**
1. Rester dans le même workspace
2. Ou re-créer les données dans le workspace actif

## 📈 Console Postman - Logs utiles

Ouvrir **View > Show Postman Console** pour voir:

```
✅ JWT Token saved
✅ Logged in - Workspace: Mon Workspace
✅ Contact saved: Jean Dupont ID: 123
✅ Company saved: Acme Corp ID: 456
✅ Deal saved: Deal 50K€ ID: 789
✅ First pipeline: Pipeline Ventes ID: 222
✅ File uploaded: document.pdf ID: 666
```

En cas d'erreur:
```
❌ Error 400 : { "status": "error", "message": "..." }
```

## 💡 Astuces pro

### 1. Utiliser les variables Postman

Au lieu d'écrire en dur:
```json
{
    "contact": 123
}
```

Utiliser:
```json
{
    "contact": "{{contact_id}}"
}
```

### 2. Chaîner les requêtes

Utiliser **Collection Runner** pour exécuter plusieurs requêtes en séquence:

1. Collection Runner
2. Sélectionner les requêtes à exécuter
3. Run

### 3. Tests automatisés

Ajouter vos propres tests dans l'onglet "Tests":

```javascript
pm.test("Status code is 200", function () {
    pm.response.to.have.status(200);
});

pm.test("Response has data", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData.data).to.not.be.empty;
});
```

### 4. Pre-request scripts personnalisés

Générer des données aléatoires:

```javascript
pm.collectionVariables.set("random_email",
    "user" + Math.floor(Math.random() * 10000) + "@example.com"
);
```

### 5. Copier curl pour debug

1. Clic droit sur une requête
2. "Export" > "Copy as cURL"
3. Tester en ligne de commande

## 📚 Ressources

- **API Documentation complète**: `/docs/API_DOCUMENTATION.md`
- **Architecture**: `/IMPLEMENTATION_SUMMARY.md`
- **File Upload Guide**: `/docs/FILE_MANAGEMENT_POSTMAN_GUIDE.md`
- **Source code**: `/src/Controller/`

## 🎓 Cas d'usage avancés

### Test de pagination

```
1. Contact > Contact List
   Body: { "pagination": { "page": 1, "limit": 10 } }

2. Vérifier response.page, response.limit, response.total
```

### Test de recherche

```
1. Contact > Contact Search {contains}
   URL: /contact/search/dupont

2. Vérifier les résultats filtrés
```

### Test d'association

```
1. Contact > Contact Edit (créer contact)
2. Company > Company Edit (créer company)
3. Contact > Contact Associate {id} {idCompany}
   → Utilise {{contact_id}} et {{company_id}} automatiquement
```

### Test de workflow deal

```
1. Pipeline > Pipeline List (obtenir pipeline_id)
2. Pipeline > Pipeline Info {id} (obtenir steps)
3. Deal > Deal Edit (créer deal dans step 1)
4. Deal > Deal Change Step {id} (déplacer vers step 2)
5. Deal > Deal Win {id} (marquer comme gagné)
```

## ✅ Checklist de vérification

Avant de commencer les tests frontend:

- [ ] Collection importée
- [ ] Environnement sélectionné
- [ ] `Auth > Login` exécuté avec succès
- [ ] Variable `{{jwt_token}}` remplie
- [ ] Variable `{{workspace_id}}` remplie
- [ ] Console Postman ouverte pour voir les logs
- [ ] Toutes les variables essentielles capturées

## 🚀 Performance

- **122 endpoints** organisés en 20 catégories
- **22 scripts automatiques** pour éviter le copier/coller
- **23 variables** auto-gérées
- **Gain de temps estimé:** 80% sur les tests API

---

**Version:** 2.0.0
**Date:** 2024-12-10
**Endpoints:** 122
**Scripts auto:** 22
**Variables:** 23
