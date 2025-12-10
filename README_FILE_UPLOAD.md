# 📁 Gestion de fichiers - Guide rapide

## 🚀 Démarrage rapide avec Postman

### 1. Importer les fichiers

Dans Postman, importer :
1. `CRM_FILE_MANAGEMENT.postman_collection.json` (Collection)
2. `CRM_FILE_MANAGEMENT.postman_environment.json` (Environnement)

### 2. Sélectionner l'environnement

En haut à droite de Postman : **"CRM API - File Management (Local)"**

### 3. Se connecter

Exécuter : **1. Authentication > Login**

✅ Le token JWT et workspace_id sont automatiquement sauvegardés

### 4. Tester l'upload

**Option A - Upload simple (fichiers < 50MB) :**
```
2. File Upload - Simple > Upload File (Simple)
```

**Option B - Chunked upload (gros fichiers) :**
```
3. File Upload - Chunked > Chunked Upload - Chunk 1
3. File Upload - Chunked > Chunked Upload - Chunk 2
3. File Upload - Chunked > Chunked Upload - Chunk 3 (Final)
```

## 📚 Documentation complète

Voir [docs/FILE_MANAGEMENT_POSTMAN_GUIDE.md](docs/FILE_MANAGEMENT_POSTMAN_GUIDE.md)

## 🔑 Endpoints principaux

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| POST | `/file/uploader` | Upload par morceaux (chunked) |
| POST | `/file/upload` | Upload simple via form-data |
| GET | `/file/list` | Liste des fichiers du workspace |
| GET | `/file/config` | Configuration d'upload |
| DELETE | `/file/delete/{id}` | Suppression d'un fichier |

## 🎯 Chunked Upload - Exemple

### Étape 1 : Premier chunk
```json
POST /file/uploader
{
    "loaded": 0,
    "fileName": "document.pdf",
    "fileSize": 5242880,
    "fileType": "application/pdf",
    "uniqId": "unique-id-12345",
    "chunk": "data:application/pdf;base64,..."
}
```

### Étape 2 : Chunks suivants
```json
POST /file/uploader
{
    "loaded": 1,
    ...
}
```

### Étape 3 : Dernier chunk
```json
POST /file/uploader
{
    "loaded": 2,
    ...
}
```

Réponse finale :
```json
{
    "success": true,
    "status": "finished",
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "filename": "550e8400-e29b-41d4-a716-446655440000.pdf",
    "link": "uploads/1/550e8400-e29b-41d4-a716-446655440000.pdf"
}
```

## 🔐 Authentification

Toutes les requêtes nécessitent :
```
Authorization: Bearer YOUR_JWT_TOKEN
X-Workspace-Id: 1
```

## ✅ Types de fichiers supportés

- **Images** : JPEG, PNG, GIF, SVG, WebP
- **Documents** : PDF, DOC, DOCX, TXT
- **Tableurs** : XLS, XLSX
- **Audio** : MP3, OGG, WebM
- **Vidéo** : MP4, WebM

## 🏗️ Architecture

```
public/uploads/
└── {workspace_id}/
    ├── {uuid}.pdf
    ├── {uuid}.png
    └── {uuid}.xlsx

public/uploads/temp/
└── {workspace_id}/
    └── {uniq_id}/
        ├── {uniq_id}.filePart0
        ├── {uniq_id}.filePart1
        └── {uniq_id}.filePart2
```

## 💻 Exemple JavaScript

```javascript
// Upload par morceaux
const file = document.querySelector('input[type="file"]').files[0];
const chunkSize = 2 * 1024 * 1024; // 2MB
const totalChunks = Math.ceil(file.size / chunkSize);
const uniqId = Date.now() + '-' + Math.random().toString(36).substr(2, 9);

for (let i = 0; i < totalChunks; i++) {
    const chunk = file.slice(i * chunkSize, (i + 1) * chunkSize);
    const base64 = await blobToBase64(chunk);

    const response = await fetch('http://crm-api.test/file/uploader', {
        method: 'POST',
        headers: {
            'Authorization': `Bearer ${token}`,
            'X-Workspace-Id': workspaceId,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            loaded: i,
            fileName: file.name,
            fileSize: file.size,
            fileType: file.type,
            uniqId: uniqId,
            chunk: base64
        })
    });

    const result = await response.json();
    if (result.status === 'finished') {
        console.log('Upload terminé!', result.link);
        break;
    }
}
```

## 🐛 Dépannage

| Erreur | Solution |
|--------|----------|
| 401 Unauthorized | Exécuter "Login" pour obtenir un token |
| 400 Workspace not found | Vérifier la variable `workspace_id` |
| 403 Access denied | Le fichier appartient à un autre workspace |
| 400 Type non autorisé | Consulter la liste des types supportés |
| Missing chunk | Renvoyer le chunk spécifié dans `part` |

## 📊 Base de données

Les fichiers sont stockés dans la table `asset` avec ces champs :

- `id` : Identifiant auto-incrémenté
- `uuid` : UUID unique du fichier
- `src` : Nom technique du fichier
- `real_name` : Nom original du fichier
- `name` : Nom d'affichage
- `type` : Type MIME
- `workspace_id` : Workspace propriétaire (isolation stricte)
- `contact_id` / `company_id` / `deal_id` : Associations optionnelles
- UserObjectTrait : createdAt, updatedAt, createdBy, etc.

## 🔗 Liens utiles

- [Guide Postman complet](docs/FILE_MANAGEMENT_POSTMAN_GUIDE.md)
- [Documentation API](docs/API_DOCUMENTATION.md)
- [Code source FileService](src/Service/FileService.php)
- [Code source FileController](src/Controller/FileController.php)

---

**Version:** 1.0.0
**URL Base:** http://crm-api.test
**Support chunked upload:** ✅ 2MB par chunk
