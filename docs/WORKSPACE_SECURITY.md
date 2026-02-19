# Workspace Security - Isolation des données

## Vue d'ensemble

Ce document décrit l'implémentation de la sécurité au niveau workspace pour garantir l'isolation complète des données entre les différents workspaces.

## Architecture

### Principe

- **Une seule base de données** PostgreSQL partagée
- **Isolation logique** via une colonne `workspace_id` sur chaque entité
- **Filtrage automatique** de toutes les requêtes par workspace

### Schéma

```
┌─────────────────────────────────────────────┐
│         Base de données UNIQUE              │
├─────────────────────────────────────────────┤
│ Workspace                                   │
│   - id (PK)                                 │
│   - name, slug, ...                         │
├─────────────────────────────────────────────┤
│ Contact                                     │
│   - id (PK)                                 │
│   - workspace_id (FK → Workspace) NOT NULL  │
│   - ...                                     │
├─────────────────────────────────────────────┤
│ Company                                     │
│   - id (PK)                                 │
│   - workspace_id (FK → Workspace) NOT NULL  │
│   - ...                                     │
├─────────────────────────────────────────────┤
│ Deal, Activity, Property, etc.              │
│   - Toutes avec workspace_id (FK)           │
└─────────────────────────────────────────────┘
```

## Composants clés

### 1. WorkspaceResolver

**Fichier**: `src/Service/WorkspaceResolver.php`

**Rôle**: Résout le workspace actuel à partir de :
1. Header HTTP `X-Workspace-Id`
2. Workspace courant de l'utilisateur (`User::currentWorkspace`)
3. Premier workspace de l'utilisateur

**Méthodes principales**:
```php
getCurrentWorkspace(): ?Workspace      // Retourne le workspace actuel
getCurrentWorkspaceId(): ?int          // Retourne l'ID du workspace
hasWorkspace(): bool                   // Vérifie si un workspace est défini
```

### 2. WorkspaceListener

**Fichier**: `src/EventListener/WorkspaceListener.php`

**Rôle**: Écoute les requêtes HTTP et :
- Valide le workspace via le header `X-Workspace-Id`
- Vérifie que l'utilisateur a accès au workspace
- Injecte le workspace dans les attributs de la requête
- Bloque l'accès si le workspace est invalide ou inactif

**Sécurité**:
- ✅ Vérifie que le workspace existe et est actif
- ✅ Vérifie que l'utilisateur est membre du workspace
- ❌ Retourne 403 Forbidden si l'utilisateur n'a pas accès
- ❌ Retourne 400 Bad Request si le workspace est invalide

### 3. WorkspaceAwareRepository

**Fichier**: `src/Repository/WorkspaceAwareRepository.php`

**Rôle**: Classe de base pour tous les repositories qui :
- **Filtre automatiquement** toutes les requêtes par `workspace_id`
- Override les méthodes `find()`, `findBy()`, `findOneBy()`, `findAll()`
- Injecte le filtre workspace dans `createQueryBuilder()`

**Méthodes clés**:
```php
// ✅ Automatiquement filtrées par workspace
createQueryBuilder($alias): QueryBuilder
find($id): ?object
findBy(array $criteria): array
findOneBy(array $criteria): ?object
findAll(): array

// ⚠️ Sans filtrage workspace (admin uniquement)
createQueryBuilderWithoutWorkspaceFilter($alias): QueryBuilder
```

### 4. Repositories mis à jour

Tous les repositories suivants étendent maintenant `WorkspaceAwareRepository` :

- ✅ `ContactRepository`
- ✅ `CompanyRepository`
- ✅ `DealRepository`
- ✅ `ActivityRepository`
- ✅ `PipelineRepository`
- ✅ `PipelineStepRepository`
- ✅ `PropertyRepository`
- ✅ `PropertyModelRepository`
- ✅ `NoteRepository`
- ✅ `TagRepository`

## Fonctionnement

### Exemple de requête sécurisée

**Avant** (❌ VULNÉRABLE):
```php
// N'importe quel utilisateur peut récupérer n'importe quel contact
$contact = $contactRepository->find(123);
```

**Après** (✅ SÉCURISÉ):
```php
// Seulement les contacts du workspace de l'utilisateur
$contact = $contactRepository->find(123);

// Si le contact 123 appartient à un autre workspace → null
// Si le contact 123 appartient au workspace actuel → Contact
```

### Exemple de QueryBuilder

**Avant** (❌ VULNÉRABLE):
```php
$qb = $this->createQueryBuilder('c')
    ->where('c.removeAt IS NULL');
// Retourne TOUS les contacts de TOUS les workspaces
```

**Après** (✅ SÉCURISÉ):
```php
$qb = $this->createQueryBuilder('c')
    ->where('c.removeAt IS NULL');
// Filtre automatique ajouté:
// ->andWhere('c.workspace = :workspace')
// ->setParameter('workspace', $workspaceId)
```

## Configuration API

### Headers requis

Pour toutes les requêtes API (sauf routes publiques) :

```http
Authorization: Bearer <JWT_TOKEN>
X-Workspace-Id: 1
```

### Routes publiques (sans workspace requis)

- `/auth/login`
- `/auth/register`
- `/auth/verify-email-otp`
- `/auth/resend-otp`
- `/auth/refresh`
- `/auth/forgot-password`
- `/auth/reset-password`

### Comportement

1. **Sans header `X-Workspace-Id`**:
   - Utilise le workspace courant de l'utilisateur
   - Si aucun workspace → Erreur 400

2. **Avec header `X-Workspace-Id`**:
   - Vérifie que le workspace existe et est actif
   - Vérifie que l'utilisateur en est membre
   - Si invalide → Erreur 400 ou 403

## Sécurité garantie

### ✅ Ce qui est protégé

1. **Accès par ID**: `find($id)` vérifie le workspace
2. **Listes**: `findBy()`, `findAll()` filtrent automatiquement
3. **Requêtes custom**: `createQueryBuilder()` filtre automatiquement
4. **Relations**: Les entités liées sont du même workspace (grâce à CASCADE)

### ⚠️ Points d'attention

1. **Fonctions admin**: Utilisez `createQueryBuilderWithoutWorkspaceFilter()` si vous devez accéder à tous les workspaces (migrations, admin, etc.)

2. **Entités sans workspace**: Certaines entités globales (User, WorkspaceMember, etc.) n'ont pas de workspace_id

3. **Tests**: Assurez-vous de définir un workspace dans vos tests

## Tests de sécurité

### Test 1: Accès cross-workspace

```php
// Workspace 1
$user1 = // User du workspace 1
$contact1 = // Contact du workspace 1 (ID=5)

// Workspace 2
$user2 = // User du workspace 2
$contact2 = $contactRepository->find(5); // Doit retourner null
```

### Test 2: Liste filtrée

```php
// Workspace actuel: 1
$contacts = $contactRepository->findAll();
// Ne retourne QUE les contacts du workspace 1
```

### Test 3: QueryBuilder personnalisé

```php
$qb = $contactRepository->createQueryBuilder('c')
    ->where('c.id > 100');

$contacts = $qb->getQuery()->getResult();
// WHERE c.workspace = 1 AND c.id > 100
```

## Migration depuis multi-tenant

### Changements architecturaux

**Avant (multi-tenant)**:
- Base de données séparée par tenant
- Switcher pour changer de connexion
- ConnectionWrapper, KernelListener, Zone

**Après (workspace)**:
- Une seule base de données
- Filtrage logique par workspace_id
- WorkspaceResolver, WorkspaceListener, WorkspaceAwareRepository

### Avantages

✅ Simplicité: Plus besoin de gérer plusieurs connexions
✅ Performance: Moins d'overhead de connexion
✅ Sauvegardes: Une seule base à sauvegarder
✅ Migrations: Une seule migration pour tous
✅ Coût: Moins de ressources serveur

### Inconvénients

⚠️ Isolation moindre: Erreur de code = fuite de données possible
⚠️ Single point of failure: Si la DB tombe, tout tombe
⚠️ Compliance: Certaines réglementations exigent séparation physique

## Best Practices

### 1. Toujours utiliser les repositories

❌ **Mauvais**:
```php
$em = $this->getDoctrine()->getManager();
$contact = $em->find(Contact::class, $id);
```

✅ **Bon**:
```php
$contact = $contactRepository->find($id);
```

### 2. Ne jamais bypasser le filtrage workspace

❌ **Dangereux** (sauf cas admin):
```php
$qb = $this->createQueryBuilderWithoutWorkspaceFilter('c');
```

✅ **Sécurisé**:
```php
$qb = $this->createQueryBuilder('c');
```

### 3. Vérifier le workspace dans les managers

```php
class ContactManager
{
    public function create(array $data): Contact
    {
        $workspace = $this->workspaceResolver->getCurrentWorkspace();

        if (!$workspace) {
            throw new \Exception('No workspace context');
        }

        $contact = new Contact();
        $contact->setWorkspace($workspace); // ← Important !
        // ...
    }
}
```

### 4. Tests

```php
class ContactControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Créer un workspace de test
        $this->workspace = $this->createWorkspace();

        // Définir le workspace dans les headers
        $this->client->setServerParameter(
            'HTTP_X_WORKSPACE_ID',
            $this->workspace->getId()
        );
    }
}
```

## Dépannage

### Erreur: "No workspace found"

**Cause**: L'utilisateur n'a pas de workspace ou le header est manquant

**Solution**:
```php
// 1. Vérifier que l'utilisateur a au moins un workspace
$user->getWorkspaces(); // Doit retourner au moins 1 workspace

// 2. Ajouter le header X-Workspace-Id
curl -H "X-Workspace-Id: 1" ...
```

### Erreur: "Access denied to this workspace"

**Cause**: L'utilisateur n'est pas membre du workspace demandé

**Solution**:
```php
// Ajouter l'utilisateur au workspace
$member = new WorkspaceMember();
$member->setUser($user);
$member->setWorkspace($workspace);
$member->setRole(WorkspaceMemberRole::MEMBER);
$em->persist($member);
$em->flush();
```

### Les requêtes retournent des résultats vides

**Cause**: Le workspace n'est pas correctement défini

**Solution**:
```php
// Vérifier dans le controller
$workspace = $this->workspaceResolver->getCurrentWorkspace();
if (!$workspace) {
    // Le workspace n'est pas défini
}
```

## Conclusion

L'implémentation de `WorkspaceAwareRepository` garantit une **isolation automatique et transparente** des données par workspace, tout en maintenant la simplicité du code et les performances.

**Sécurité**: ✅ Toutes les requêtes sont automatiquement filtrées
**Performance**: ✅ Filtrage au niveau base de données (optimisé)
**Maintenance**: ✅ Un seul point de modification (WorkspaceAwareRepository)
**Extensibilité**: ✅ Facile d'ajouter de nouvelles entités
