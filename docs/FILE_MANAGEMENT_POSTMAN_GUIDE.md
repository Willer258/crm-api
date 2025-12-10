# Guide d'utilisation Postman - Gestion de fichiers CRM API

## 📋 Vue d'ensemble

Cette collection Postman permet de tester le système de gestion de fichiers du CRM API avec support du **chunked upload** (upload par morceaux).

**Base URL:** `http://crm-api.test`

## 🚀 Installation rapide

### 1. Importer la collection

1. Ouvrir Postman
2. Cliquer sur **Import**
3. Sélectionner le fichier `CRM_FILE_MANAGEMENT.postman_collection.json`
4. La collection "CRM API - File Management" apparaît dans votre sidebar

### 2. Importer l'environnement

1. Cliquer sur **Import**
2. Sélectionner le fichier `CRM_FILE_MANAGEMENT.postman_environment.json`
3. Sélectionner l'environnement "CRM API - File Management (Local)" dans le menu déroulant en haut à droite

### 3. Configuration initiale

L'environnement contient ces variables pré-configurées :

| Variable | Valeur par défaut | Description |
|----------|-------------------|-------------|
| `base_url` | `http://crm-api.test` | URL de base de l'API |
| `jwt_token` | *(vide)* | Token JWT (rempli automatiquement après login) |
| `workspace_id` | `1` | ID du workspace actif |
| `file_id` | *(vide)* | ID du dernier fichier listé |
| `file_uniq_id` | *(vide)* | ID unique pour chunked upload |
| `uploaded_file_uuid` | *(vide)* | UUID du fichier uploadé |

## 📁 Structure de la collection

### 1. Authentication
- **Login** : Connexion pour obtenir le token JWT
- **Get User Profile** : Récupère le profil et le workspace actif

### 2. File Upload - Simple
- **Upload File (Simple)** : Upload classique d'un fichier via form-data
- **Get Upload Config** : Configuration (taille max, types autorisés)

### 3. File Upload - Chunked
- **Chunked Upload - Chunk 1** : Premier morceau (loaded=0)
- **Chunked Upload - Chunk 2** : Deuxième morceau (loaded=1)
- **Chunked Upload - Chunk 3 (Final)** : Dernier morceau (loaded=2)

### 4. File Management
- **List Files** : Liste tous les fichiers du workspace
- **Delete File** : Supprime un fichier

### 5. Workspace Management
- **List Workspaces** : Liste les workspaces de l'utilisateur
- **Switch Workspace** : Change de workspace actif

### 6. Test Scenarios
- **Upload Image PNG** : Test avec une image
- **Upload Excel XLSX** : Test avec un fichier Excel
- **Upload with Invalid Type** : Test de validation (devrait échouer)

## 🎯 Scénarios d'utilisation

### Scénario 1 : Premier test complet

1. **Authentification**
   ```
   Exécuter : 1. Authentication > Login

   Body :
   {
       "email": "admin@example.com",
       "password": "password123"
   }

   Résultat attendu : Status 200
   Variables automatiquement remplies : jwt_token, workspace_id
   ```

2. **Lister les fichiers existants**
   ```
   Exécuter : 4. File Management > List Files

   Résultat attendu : Status 200
   {
       "status": "success",
       "data": [ ... ],
       "workspace": { "id": 1, "name": "Mon Workspace" }
   }
   ```

3. **Upload simple d'un fichier**
   ```
   Exécuter : 2. File Upload - Simple > Upload File (Simple)

   Dans l'onglet Body > form-data :
   - Sélectionner un fichier pour la clé "file"
   - (Optionnel) Ajouter contact/company/deal IDs

   Résultat attendu : Status 201
   {
       "status": "success",
       "message": "Fichier uploadé avec succès",
       "file": { ... }
   }
   ```

### Scénario 2 : Chunked Upload (gros fichiers)

Le chunked upload permet d'envoyer un fichier en plusieurs morceaux.

1. **Initialisation**
   ```
   Exécuter : 3. File Upload - Chunked > Chunked Upload - Chunk 1

   Le script pré-requête génère automatiquement :
   - file_uniq_id : Identifiant unique du fichier
   - file_size : 5MB
   - chunk_size : 2MB
   - total_chunks : 3
   ```

2. **Envoi des chunks suivants**
   ```
   Exécuter séquentiellement :
   - Chunked Upload - Chunk 2 (loaded=1)
   - Chunked Upload - Chunk 3 (loaded=2)

   Le dernier chunk déclenche la fusion et retourne :
   {
       "success": true,
       "status": "finished",
       "id": "uuid-du-fichier",
       "filename": "uuid.pdf",
       "link": "uploads/1/uuid.pdf"
   }
   ```

### Scénario 3 : Tests de validation

1. **Test type de fichier invalide**
   ```
   Exécuter : 6. Test Scenarios > Upload with Invalid Type

   Résultat attendu : Status 400
   {
       "status": "error",
       "message": "Type de fichier application/x-msdownload non autorisé"
   }
   ```

2. **Test workspace isolation**
   ```
   1. Changer workspace_id dans l'environnement
   2. Exécuter : 4. File Management > List Files
   3. Vérifier que seuls les fichiers du nouveau workspace apparaissent
   ```

## 📊 Comprendre le Chunked Upload

### Fonctionnement

Le chunked upload divise un fichier en morceaux de 2MB (configurable) :

```
Fichier de 5MB = 3 chunks
├─ Chunk 0 (0-2MB)    → loaded=0
├─ Chunk 1 (2-4MB)    → loaded=1
└─ Chunk 2 (4-5MB)    → loaded=2 → FUSION
```

### Structure de la requête

```json
{
    "loaded": 0,                    // Numéro du chunk (0-indexed)
    "fileName": "document.pdf",     // Nom original
    "fileSize": 5242880,            // Taille totale en bytes
    "fileType": "application/pdf",  // Type MIME
    "fileHash": "abc123",           // Hash du fichier (optionnel)
    "uniqId": "unique-id-12345",    // ID unique pour ce fichier
    "chunk": "data:application/pdf;base64,..." // Chunk encodé en base64
}
```

### Réponses possibles

**En cours d'upload :**
```json
{
    "success": true,
    "status": "loaded",
    "message": "continue",
    "received": 1,
    "full": 3
}
```

**Chunk manquant :**
```json
{
    "success": true,
    "status": "missing",
    "message": "missing file-id.filePart1",
    "part": 1,
    "full": 3,
    "received": 2
}
```

**Upload terminé :**
```json
{
    "success": true,
    "status": "finished",
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "filename": "550e8400-e29b-41d4-a716-446655440000.pdf",
    "type": "application/pdf",
    "link": "uploads/1/550e8400-e29b-41d4-a716-446655440000.pdf"
}
```

## 🔐 Authentification

Toutes les requêtes (sauf Login) nécessitent :

**Header :**
```
Authorization: Bearer {{jwt_token}}
X-Workspace-Id: {{workspace_id}}
```

Le token est automatiquement ajouté via l'authentification Bearer au niveau de la collection.

## 📝 Types de fichiers supportés

### Images
- `image/jpeg`, `image/png`, `image/gif`
- `image/svg+xml`, `image/webp`

### Documents
- `application/pdf`
- `application/msword` (DOC)
- `application/vnd.openxmlformats-officedocument.wordprocessingml.document` (DOCX)
- `text/plain` (TXT)

### Tableurs
- `application/vnd.ms-excel` (XLS)
- `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet` (XLSX)

### Audio
- `audio/mp3`, `audio/mpeg`
- `audio/ogg`, `audio/webm`

### Vidéo
- `video/mp4`, `video/webm`

## 🧪 Tests automatisés

La collection inclut des **scripts de test** qui s'exécutent automatiquement :

### Login
```javascript
// Sauvegarde automatique du token JWT
if (pm.response.code === 200) {
    const response = pm.response.json();
    pm.collectionVariables.set('jwt_token', response.token);
    pm.collectionVariables.set('workspace_id', response.user.currentWorkspace.id);
}
```

### List Files
```javascript
// Sauvegarde l'ID du premier fichier pour la suppression
if (response.data.length > 0) {
    pm.collectionVariables.set('file_id', response.data[0].id);
}
```

### Chunked Upload
```javascript
// Génération automatique d'un ID unique
const uniqId = Date.now() + '-' + Math.random().toString(36).substr(2, 9);
pm.collectionVariables.set('file_uniq_id', uniqId);
```

## 🚨 Dépannage

### Erreur 401 Unauthorized
- Vérifier que `jwt_token` est renseigné
- Exécuter "1. Authentication > Login" pour obtenir un nouveau token

### Erreur 400 Workspace not found
- Vérifier que `workspace_id` est correct
- Exécuter "5. Workspace Management > List Workspaces"

### Erreur 403 Access denied
- Le fichier appartient à un autre workspace
- Vérifier que `X-Workspace-Id` correspond au workspace du fichier

### Erreur 400 Type de fichier non autorisé
- Vérifier la liste des types autorisés
- Exécuter "2. File Upload - Simple > Get Upload Config"

### Chunk manquant
- Renvoyer le chunk spécifié dans `part`
- Ne pas changer le `uniqId` entre les chunks

## 📈 Monitoring

### Variables à surveiller

Pendant les tests, vérifier dans l'onglet **Console** (View > Show Postman Console) :

```
File upload initialized:
- Unique ID: 1702389456-abc123def
- File Size: 5242880 bytes
- Chunk Size: 2097152 bytes
- Total Chunks: 3

Token JWT sauvegardé: eyJ0eXAiOiJKV1QiLCJhbGc...
Workspace ID sauvegardé: 1

File uploaded successfully!
UUID: 550e8400-e29b-41d4-a716-446655440000
Filename: 550e8400-e29b-41d4-a716-446655440000.pdf
Link: uploads/1/550e8400-e29b-41d4-a716-446655440000.pdf
```

## 🔄 Workflow recommandé

```
1. Login
   ↓
2. List Workspaces (optionnel)
   ↓
3. Get Upload Config (optionnel)
   ↓
4. Upload File (Simple OU Chunked)
   ↓
5. List Files (vérification)
   ↓
6. Delete File (si nécessaire)
```

## 💡 Conseils

1. **Utiliser l'environnement** : Les variables sont automatiquement mises à jour
2. **Consulter la console** : Utile pour debugger les scripts
3. **Tester en séquence** : Exécuter les chunks dans l'ordre
4. **Vérifier les headers** : `X-Workspace-Id` doit être présent
5. **Base64 encoding** : Les chunks doivent être encodés en base64 avec préfixe data URI

## 📚 Ressources

- Documentation API complète : `/docs/API_DOCUMENTATION.md`
- Guide d'architecture : `/IMPLEMENTATION_SUMMARY.md`
- Code source FileService : `/src/Service/FileService.php`
- Code source FileController : `/src/Controller/FileController.php`

## 🎓 Exemples de code

### JavaScript (Frontend)

```javascript
async function uploadFileInChunks(file, workspaceId, jwtToken) {
    const chunkSize = 2 * 1024 * 1024; // 2MB
    const totalChunks = Math.ceil(file.size / chunkSize);
    const uniqId = Date.now() + '-' + Math.random().toString(36).substr(2, 9);

    for (let i = 0; i < totalChunks; i++) {
        const start = i * chunkSize;
        const end = Math.min(start + chunkSize, file.size);
        const chunk = file.slice(start, end);

        // Convertir le chunk en base64
        const base64Chunk = await blobToBase64(chunk);

        const response = await fetch('http://crm-api.test/file/uploader', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${jwtToken}`,
                'X-Workspace-Id': workspaceId
            },
            body: JSON.stringify({
                loaded: i,
                fileName: file.name,
                fileSize: file.size,
                fileType: file.type,
                uniqId: uniqId,
                chunk: base64Chunk
            })
        });

        const result = await response.json();

        if (result.status === 'finished') {
            console.log('Upload terminé!', result);
            return result;
        }
    }
}

function blobToBase64(blob) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onloadend = () => resolve(reader.result);
        reader.onerror = reject;
        reader.readAsDataURL(blob);
    });
}
```

### Curl

```bash
# Upload simple
curl -X POST http://crm-api.test/file/upload \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "X-Workspace-Id: 1" \
  -F "file=@/path/to/document.pdf"

# Chunked upload (chunk 1)
curl -X POST http://crm-api.test/file/uploader \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "X-Workspace-Id: 1" \
  -H "Content-Type: application/json" \
  -d '{
    "loaded": 0,
    "fileName": "document.pdf",
    "fileSize": 5242880,
    "fileType": "application/pdf",
    "uniqId": "test-123",
    "chunk": "data:application/pdf;base64,JVBERi0xLjQ..."
  }'

# Liste des fichiers
curl -X GET http://crm-api.test/file/list \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "X-Workspace-Id: 1"

# Suppression
curl -X DELETE http://crm-api.test/file/delete/1 \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "X-Workspace-Id: 1"
```

---

**Version:** 1.0.0
**Date:** 2024-12-10
**Auteur:** Claude Code
