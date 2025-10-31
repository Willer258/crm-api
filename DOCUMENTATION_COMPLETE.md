# Documentation Complète - Système CRM Multi-Tenant

> **Version** : 1.0
> **Date** : Octobre 2025
> **Framework** : Symfony 7.2
> **Auteur** : Documentation générée pour le projet CRM

---

## Table des matières

### PARTIE 1 - DOCUMENTATION MÉTIER ET TECHNIQUE
1. [Vue d'ensemble du système](#1-vue-densemble-du-système)
2. [Architecture technique](#2-architecture-technique)
3. [Modèle de données](#3-modèle-de-données)
4. [Architecture Multi-tenant](#4-architecture-multi-tenant)
5. [Sécurité et authentification](#5-sécurité-et-authentification)

### PARTIE 2 - MANUEL D'UTILISATION
6. [Guide de démarrage](#6-guide-de-démarrage)
7. [Gestion des contacts](#7-gestion-des-contacts)
8. [Gestion des entreprises](#8-gestion-des-entreprises)
9. [Gestion des affaires (Deals)](#9-gestion-des-affaires-deals)
10. [Gestion des activités](#10-gestion-des-activités)
11. [Pipelines de vente](#11-pipelines-de-vente)
12. [Tags et catégorisation](#12-tags-et-catégorisation)
13. [Import/Export de données](#13-importexport-de-données)

### PARTIE 3 - PROPOSITION D'INTERFACE
14. [Dashboard principal](#14-dashboard-principal)
15. [Interface Contacts](#15-interface-contacts)
16. [Interface Entreprises](#16-interface-entreprises)
17. [Interface Pipeline/Affaires](#17-interface-pipelineaffaires)
18. [Interface Activités](#18-interface-activités)
19. [Interface Configuration](#19-interface-configuration)

### PARTIE 4 - GUIDE DÉVELOPPEUR
20. [Installation et configuration](#20-installation-et-configuration)
21. [Structure du code](#21-structure-du-code)
22. [Commandes disponibles](#22-commandes-disponibles)
23. [Tests et développement](#23-tests-et-développement)
24. [Référence API](#24-référence-api)

### PARTIE 5 - WORKFLOWS MÉTIER
25. [Scénarios d'utilisation](#25-scénarios-dutilisation)
26. [Bonnes pratiques](#26-bonnes-pratiques)
27. [Cas d'usage réels](#27-cas-dusage-réels)

---

# PARTIE 1 - DOCUMENTATION MÉTIER ET TECHNIQUE

## 1. Vue d'ensemble du système

### 1.1 Présentation

Ce CRM (Customer Relationship Management) est une application professionnelle de gestion de la relation client conçue pour les entreprises modernes. Il offre une solution complète pour :

- **Gérer les contacts et entreprises** : Base de données centralisée de vos clients et prospects
- **Suivre les opportunités commerciales** : Pipeline de vente configurable avec suivi des affaires
- **Planifier les activités** : Calendrier d'activités avec notifications
- **Analyser les performances** : Suivi des conversions et statistiques de vente
- **Collaborer en équipe** : Partage d'informations et assignation de tâches

### 1.2 Caractéristiques principales

#### Multi-tenant
Le système supporte plusieurs organisations (tenants) sur une même infrastructure :
- Isolation complète des données par tenant
- Base de données séparée par tenant
- Configuration personnalisée par organisation

#### Propriétés dynamiques
Un système de champs personnalisables permet d'adapter le CRM à votre métier :
- Création de champs personnalisés pour contacts, entreprises, etc.
- Types de données variés (texte, nombre, date, URL, localisation)
- Configuration par type d'objet métier

#### Traçabilité complète
Chaque action est enregistrée avec :
- Date et heure de création/modification/suppression
- Utilisateur responsable de l'action
- Adresse IP d'origine
- UUID unique pour chaque objet

#### API REST complète
Interface programmatique pour :
- Intégrations tierces
- Applications mobiles (React Native, Flutter)
- Automatisations et workflows

### 1.3 Public cible

Le système s'adresse à trois profils principaux :

1. **Utilisateurs finaux** : Commerciaux, responsables de comptes
2. **Administrateurs** : Gestionnaires du système, configurateurs
3. **Développeurs** : Équipes techniques pour intégrations et extensions

---

## 2. Architecture technique

### 2.1 Stack technologique

#### Backend
- **Framework** : Symfony 7.2 (PHP 8.2+)
- **ORM** : Doctrine ORM 3.3
- **Base de données** : MySQL (architecture multi-tenant)
- **Authentification** : JWT (lexik/jwt-authentication-bundle 3.1)
- **UUID** : ramsey/uuid pour identifiants uniques

#### Frontend (prévu)
- **React.js** ou **Vue.js** (classes TypeScript générées automatiquement)
- **Asset Mapper** : Gestion moderne des assets
- **Stimulus** : Controllers JavaScript légers

#### Outils et bibliothèques
- **PHPSpreadsheet** : Import/Export Excel/CSV
- **CORS** : nelmio/cors-bundle pour API cross-origin
- **Device Detector** : Détection des appareils clients
- **Monolog** : Système de logs

### 2.2 Architecture en couches

```
┌─────────────────────────────────────────┐
│         INTERFACE UTILISATEUR           │
│    (React/Vue.js + API REST JSON)       │
└─────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────┐
│           CONTRÔLEURS (API)             │
│     Routes RESTful + Sérialisation      │
└─────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────┐
│          MANAGERS (Logique Métier)      │
│   ContactManager, DealManager, etc.     │
└─────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────┐
│       REPOSITORIES (Accès Données)      │
│        Requêtes Doctrine DQL            │
└─────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────┐
│          ENTITÉS (Modèle)               │
│   Contact, Company, Deal, Activity      │
└─────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────┐
│         BASE DE DONNÉES MySQL           │
│      (Tenant_DBNAME par tenant)         │
└─────────────────────────────────────────┘
```

### 2.3 Patterns architecturaux

#### Pattern Manager
Chaque entité métier possède un Manager dédié :
- **Responsabilité** : Logique métier complexe, validations, orchestration
- **Exemple** : `ContactManager`, `DealManager`, `ActivityManager`
- **Avantages** : Séparation des responsabilités, testabilité, réutilisabilité

#### Pattern Repository
Accès aux données via des repositories Doctrine :
- **Requêtes personnalisées** : DQL optimisées
- **Pagination** : Gestion intégrée
- **Filtres** : Recherche et tri

#### Traits pour comportements partagés
- **UserObjectTrait** : Traçabilité (création, modification, suppression)
- **TagTrait** : Système de tags (à venir)
- **ImageTrait** : Gestion des photos/logos

---

## 3. Modèle de données

### 3.1 Schéma relationnel global

```
┌──────────────┐         ┌──────────────┐
│   CONTACT    │──┬──────│   COMPANY    │
│              │  │      │              │
│ - id         │  │      │ - id         │
│ - source     │  │      │ - properties │
│ - manager    │  │      │ - contacts   │
│ - company    │──┘      │ - deals      │
│ - properties │         │ - tags       │
│ - deals      │         └──────────────┘
│ - activities │                │
│ - tags       │                │
│ - phones     │         ┌──────▼──────┐
│ - mails      │         │     DEAL    │
│ - notes      │────────▶│             │
└──────────────┘         │ - id        │
       │                 │ - object    │
       │                 │ - contact   │
       │                 │ - company   │
       │                 │ - step      │
       │                 │ - status    │
       │                 │ - products  │
       │                 └─────────────┘
       │                        │
       │                        │
       ▼                        ▼
┌──────────────┐         ┌─────────────┐
│   ACTIVITY   │         │ PIPELINE    │
│              │         │   STEP      │
│ - id         │         │             │
│ - name       │         │ - name      │
│ - type       │         │ - ranking   │
│ - startDate  │         │ - probability│
│ - endDate    │         │ - color     │
│ - performed  │         │ - pipeline  │
│ - notify     │         └─────────────┘
│ - contact    │                │
│ - company    │                │
│ - deal       │         ┌──────▼──────┐
└──────────────┘         │  PIPELINE   │
                         │             │
                         │ - name      │
                         │ - roles     │
                         │ - steps     │
                         └─────────────┘
```

### 3.2 Entités principales

#### 3.2.1 Contact
Représente une personne physique (prospect, client, partenaire).

**Attributs principaux** :
- `id` (int) : Identifiant unique
- `source` (string) : Origine du contact (website, référence, salon, etc.)
- `manager` (string) : Email du commercial responsable
- `company` (Company) : Entreprise associée (optionnel)
- `itemType` (ItemType) : Type de contact (pour propriétés dynamiques)
- `photo` (string) : Chemin vers la photo de profil

**Relations** :
- `properties` (Collection<Property>) : Propriétés dynamiques (nom, prénom, fonction, etc.)
- `deals` (Collection<Deal>) : Affaires où le contact est principal
- `dealsAsParticipant` (Collection<Deal>) : Affaires où le contact participe
- `activities` (Collection<Activity>) : Activités liées au contact
- `phones` (Collection<PhoneNumber>) : Numéros de téléphone
- `mails` (Collection<Mail>) : Adresses email
- `notes` (Collection<Note>) : Notes et commentaires
- `tags` (Collection<Tag>) : Tags de catégorisation
- `assets` (Collection<Asset>) : Fichiers attachés

**Traits utilisés** :
- `UserObjectTrait` : Traçabilité complète (createdAt, updatedAt, createBy, etc.)

**Groupes de sérialisation** :
- `contact:list` : Vue liste minimale
- `contact:info` : Vue détaillée complète
- `contact:edit` : Données pour édition

#### 3.2.2 Company
Représente une entreprise cliente ou prospect.

**Attributs principaux** :
- `id` (int) : Identifiant unique
- `itemType` (ItemType) : Type d'entreprise
- `photo` (string) : Logo de l'entreprise

**Relations** :
- `properties` (Collection<Property>) : Propriétés dynamiques (nom, secteur, CA, etc.)
- `contacts` (Collection<Contact>) : Contacts de l'entreprise
- `deals` (Collection<Deal>) : Affaires liées
- `activities` (Collection<Activity>) : Activités planifiées
- `phones` (Collection<PhoneNumber>) : Téléphones
- `mails` (Collection<Mail>) : Emails
- `notes` (Collection<Note>) : Notes
- `tags` (Collection<Tag>) : Tags
- `assets` (Collection<Asset>) : Documents

**Traits** : `UserObjectTrait`

#### 3.2.3 Deal (Affaire)
Représente une opportunité commerciale dans le pipeline de vente.

**Attributs principaux** :
- `id` (int) : Identifiant unique
- `object` (string) : Objet/description de l'affaire
- `manager` (string) : Commercial responsable
- `status` (string) : Statut (null, 'win', 'lost')
- `products` (array) : Liste des produits/services concernés

**Relations** :
- `contact` (Contact) : Contact principal
- `company` (Company) : Entreprise concernée
- `step` (PipelineStep) : Étape actuelle dans le pipeline
- `participants` (Collection<Contact>) : Contacts participants
- `activities` (Collection<Activity>) : Activités liées
- `tags` (Collection<Tag>) : Tags
- `notes` (Collection<Note>) : Notes
- `assets` (Collection<Asset>) : Documents

**Constantes** :
- `STATUS_WIN = 'win'` : Affaire gagnée
- `STATUS_LOST = 'lost'` : Affaire perdue

**Traits** : `UserObjectTrait`

#### 3.2.4 Activity (Activité)
Représente une tâche, rendez-vous, appel ou email à réaliser ou réalisé.

**Attributs principaux** :
- `id` (int) : Identifiant unique
- `name` (string) : Nom de l'activité
- `type` (string) : Type (call, meeting, email, task, deadline)
- `startDate` (DateTime) : Date/heure de début
- `endDate` (DateTime) : Date/heure de fin
- `location` (string) : Lieu (pour meetings)
- `performed` (bool) : Activité réalisée ou non
- `notify` (bool) : Activer les notifications
- `notifyDate` (DateTime) : Date de notification
- `description` (string) : Description détaillée
- `managers` (array JSON) : Liste des utilisateurs assignés

**Relations** :
- `contact` (Contact) : Contact concerné
- `company` (Company) : Entreprise concernée
- `deal` (Deal) : Affaire liée
- `notes` (Collection<Note>) : Notes de suivi

**Traits** : `UserObjectTrait`

#### 3.2.5 Pipeline
Représente un processus de vente personnalisé.

**Attributs principaux** :
- `id` (int) : Identifiant unique
- `name` (string) : Nom du pipeline (ex: "Ventes B2B", "Ventes B2C")
- `description` (string) : Description
- `roles` (array) : Rôles autorisés à utiliser ce pipeline

**Relations** :
- `pipelineSteps` (Collection<PipelineStep>) : Étapes du pipeline

**Traits** : `UserObjectTrait`

#### 3.2.6 PipelineStep (Étape de Pipeline)
Représente une étape dans un processus de vente.

**Attributs principaux** :
- `id` (int) : Identifiant unique
- `name` (string) : Nom de l'étape (ex: "Qualification", "Proposition")
- `description` (string) : Description
- `successProbability` (float) : Probabilité de succès (0-100%)
- `color` (string) : Couleur pour l'affichage (hex color)
- `ranking` (string) : Ordre d'affichage

**Relations** :
- `pipeline` (Pipeline) : Pipeline parent
- `deals` (Collection<Deal>) : Affaires dans cette étape

**Traits** : `UserObjectTrait`

#### 3.2.7 PropertyModel (Modèle de Propriété)
Définit un champ personnalisé pour un type d'objet.

**Attributs principaux** :
- `id` (int) : Identifiant unique
- `label` (string) : Libellé (ex: "Prénom", "Nom entreprise")
- `identifier` (bool) : Est-ce un champ identifiant principal ?
- `type` (string) : Type de données (text, number, datetime, site, localisation)
- `class` (string) : Classe CSS pour le rendu
- `itemType` (ItemType) : Type d'objet concerné

**Relations** :
- `properties` (Collection<Property>) : Instances de valeurs

**Types disponibles** :
- `text` : Texte simple
- `number` : Numérique (entier ou décimal)
- `datetime` : Date et heure
- `site` : URL de site web
- `localisation` : Adresse géographique

**Traits** : `UserObjectTrait`

#### 3.2.8 Property (Propriété/Valeur)
Instance de valeur pour un PropertyModel sur un objet spécifique.

**Attributs principaux** :
- `id` (int) : Identifiant unique
- `value` (string) : Valeur stockée (format texte)
- `propertyModel` (PropertyModel) : Modèle de la propriété

**Relations** :
- `contact` (Contact) : Contact associé (si applicable)
- `company` (Company) : Entreprise associée (si applicable)

**Traits** : `UserObjectTrait`

#### 3.2.9 Tag
Permet de catégoriser et filtrer les objets métier.

**Attributs principaux** :
- `id` (int) : Identifiant unique
- `label` (string) : Libellé affiché
- `code` (string) : Code unique (pour API)
- `description` (string) : Description
- `color` (string) : Couleur (hex)

**Relations** :
- `contacts` (Collection<Contact>) : Contacts taggés
- `companies` (Collection<Company>) : Entreprises taggées
- `deals` (Collection<Deal>) : Affaires taggées

**Traits** : `UserObjectTrait`

#### 3.2.10 ItemType (Type d'Objet)
Définit un type métier pour grouper les PropertyModel.

**Attributs principaux** :
- `id` (int) : Identifiant unique
- `code` (string) : Code unique (ex: "contact", "company")
- `label` (string) : Libellé

**Relations** :
- `propertyModels` (Collection<PropertyModel>) : Modèles de propriétés
- `contacts` (Collection<Contact>) : Contacts de ce type
- `companies` (Collection<Company>) : Entreprises de ce type

**Traits** : `UserObjectTrait`

#### 3.2.11 Note
Commentaire ou observation sur un objet métier.

**Attributs principaux** :
- `id` (int) : Identifiant unique
- `content` (text) : Contenu de la note

**Relations** :
- `contact` (Contact) : Contact concerné (optionnel)
- `company` (Company) : Entreprise concernée (optionnel)
- `deal` (Deal) : Affaire concernée (optionnel)
- `activity` (Activity) : Activité concernée (optionnel)

**Traits** : `UserObjectTrait`

#### 3.2.12 PhoneNumber
Numéro de téléphone associé à un contact ou entreprise.

**Attributs principaux** :
- `id` (int) : Identifiant unique
- `number` (string) : Numéro formaté
- `type` (string) : Type (mobile, fixe, fax)

**Relations** :
- `contact` (Contact) : Contact propriétaire
- `company` (Company) : Entreprise propriétaire

**Traits** : `UserObjectTrait`

#### 3.2.13 Mail
Adresse email associée à un contact ou entreprise.

**Attributs principaux** :
- `id` (int) : Identifiant unique
- `email` (string) : Adresse email
- `type` (string) : Type (professionnel, personnel)

**Relations** :
- `contact` (Contact) : Contact propriétaire
- `company` (Company) : Entreprise propriétaire

**Traits** : `UserObjectTrait`

#### 3.2.14 Asset (Fichier)
Document ou fichier attaché à un objet métier.

**Attributs principaux** :
- `id` (int) : Identifiant unique
- `name` (string) : Nom du fichier
- `description` (string) : Description
- `size` (int) : Taille en octets
- `mimeType` (string) : Type MIME
- `path` (string) : Chemin de stockage

**Relations** :
- `contact` (Contact) : Contact associé (optionnel)
- `company` (Company) : Entreprise associée (optionnel)
- `deal` (Deal) : Affaire associée (optionnel)

**Traits** : `UserObjectTrait`

### 3.3 Système de traçabilité (UserObjectTrait)

Tous les objets métier héritent de ce trait qui fournit :

**Champs de création** :
- `uuid` (UUID) : Identifiant universel unique
- `createdAt` (DateTime) : Date de création
- `createBy` (string) : Utilisateur créateur
- `createdFromIp` (string) : Adresse IP de création

**Champs de modification** :
- `updatedAt` (DateTime) : Date de dernière modification
- `updateBy` (string) : Utilisateur modificateur
- `updatedFromIp` (string) : Adresse IP de modification

**Champs de suppression logique** :
- `removeAt` (DateTime) : Date de suppression
- `removeBy` (string) : Utilisateur supprimeur

**Avantages** :
- Audit trail complet
- Récupération de données supprimées
- Conformité RGPD (traçabilité des actions)
- Analyse des comportements utilisateurs

---

## 4. Architecture Multi-tenant

### 4.1 Principe du Multi-tenancy

Le système utilise une approche **"database-per-tenant"** : chaque organisation dispose de sa propre base de données isolée.

**Avantages** :
- **Isolation maximale** : Sécurité renforcée
- **Performance** : Pas de filtrage systématique par tenant
- **Scalabilité** : Possibilité de distribuer les bases sur différents serveurs
- **Backup/Restore** : Gestion indépendante par tenant

**Structure** :
```
tenant_ci_crm     → Base pour le tenant "ci" (Côte d'Ivoire)
tenant_sn_crm     → Base pour le tenant "sn" (Sénégal)
tenant_demo_crm   → Base pour le tenant de démonstration
```

### 4.2 Composants du système Multi-tenant

#### 4.2.1 Switcher (`src/MultiTenancy/Switcher.php`)

Le composant central de gestion du multi-tenancy.

**Responsabilités** :
- Détecter le tenant actif (cookie, header, CLI)
- Changer la connexion base de données
- Valider les tenants autorisés

**Méthodes principales** :
```php
// Changer de tenant
public function switchTenant(?string $tenant): void

// Obtenir le tenant depuis la requête HTTP
public function getTenant(): string

// Définir le tenant (cookie)
public function setTenant(string $tenant): void

// Changer de base de données
public function setTenantDatabase(string $tenant): void

// Gestion CLI
public function switchCommandLineTenant(?string $tenant): void
public function setCommandLineTenant(string $tenant): void
public function getCommandLineTenant(): ?string
```

**Détection du tenant** :
1. **Mode Web** : Cookie `zone` contenant le code tenant
2. **Mode CLI** : Fichier `tenant.txt` à la racine du projet
3. **Fallback** : Tenant par défaut (DEMO)

#### 4.2.2 ConnectionWrapper (`src/MultiTenancy/ConnectionWrapper.php`)

Wrapper de la connexion Doctrine permettant de changer dynamiquement de base de données.

**Fonctionnement** :
```php
// Change la connexion vers une nouvelle base
$connection->changeDatabase(
    $host,     // Hôte MySQL
    $port,     // Port (3306)
    $user,     // Utilisateur
    $password, // Mot de passe
    $dbname    // Nom de la base (tenant_XX_crm)
);
```

#### 4.2.3 KernelListener (`src/MultiTenancy/KernelListener.php`)

Écouteur d'événements Symfony qui intercepte chaque requête pour switcher le tenant automatiquement.

**Événement écouté** : `kernel.request`

**Processus** :
1. Requête HTTP arrive
2. Le listener détecte le tenant (cookie/header)
3. Appel du Switcher pour changer la base
4. Contrôleur exécuté sur la bonne base de données

#### 4.2.4 Zone (`src/MultiTenancy/Zone.php`)

Gestionnaire de configuration des zones/tenants.

**Responsabilités** :
- Vérifier si un tenant existe
- Récupérer la configuration d'un tenant
- Valider les tenants actifs

### 4.3 Configuration d'un nouveau tenant

#### Étape 1 : Créer la base de données
```sql
CREATE DATABASE tenant_newclient_crm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

#### Étape 2 : Exécuter les migrations
```bash
# Définir le tenant actif
echo "newclient" > tenant.txt

# Exécuter les migrations
php bin/console doctrine:migrations:migrate --no-interaction
```

#### Étape 3 : Charger les données initiales (optionnel)
```bash
php bin/console doctrine:fixtures:load --no-interaction
# ou
php bin/console app:init-fixtures
```

#### Étape 4 : Ajouter le tenant dans la configuration
Modifier `src/MultiTenancy/Switcher.php` :
```php
const ACCEPTED = ['ci', 'sn', 'newclient'];
```

### 4.4 Utilisation en développement

#### Mode Web
Le tenant est défini par un cookie. Pour tester un tenant :
1. Définir le cookie `zone` avec la valeur du tenant
2. Recharger la page

#### Mode CLI
```bash
# Définir le tenant pour les commandes
php bin/console app:tenant:set ci

# Vérifier le tenant actif
php bin/console app:tenant:get

# Exécuter une commande pour chaque tenant
php bin/console app:tenant:foreach doctrine:migrations:status
```

### 4.5 Considérations de sécurité

**Isolation des données** :
- Aucune requête cross-tenant possible
- Validation stricte des tenants autorisés
- Cookie sécurisé (HTTPS en production)

**Bonnes pratiques** :
- Ne jamais coder en dur un tenant dans le code
- Toujours utiliser le Switcher pour les changements
- Logger les changements de tenant (audit)
- Tester l'isolation entre tenants

---

## 5. Sécurité et authentification

### 5.1 Vue d'ensemble

Le système implémente trois mécanismes d'authentification :

1. **JWT (JSON Web Token)** : Authentification principale pour les utilisateurs
2. **API Key** : Authentification pour services externes
3. **Master Authenticator** : Accès privilégié cross-tenant (admin système)

### 5.2 Authentification JWT

#### Configuration
Bundle : `lexik/jwt-authentication-bundle`

**Fichiers de configuration** :
- `config/packages/lexik_jwt_authentication.yaml` : Configuration JWT
- `config/packages/security.yaml` : Firewalls et access control

#### Génération des tokens

**Obtenir un token** :
```bash
curl -X POST http://crm.local/api/login_check \
  -H "Content-Type: application/json" \
  -d '{"username": "user@example.com", "password": "password"}'
```

**Réponse** :
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."
}
```

#### Utilisation du token

**Header HTTP** :
```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...
```

**Exemple** :
```bash
curl -X GET http://crm.local/contact/list \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

#### Durée de vie et renouvellement

**Configuration** (dans `lexik_jwt_authentication.yaml`) :
```yaml
lexik_jwt_authentication:
    token_ttl: 3600  # 1 heure
```

**Rafraîchissement** : Implémenter un refresh token ou redemander l'authentification.

### 5.3 Authentification par API Key

Utilisée pour les intégrations machine-to-machine (webhooks, services externes).

**Fichier** : `src/Security/ApiKeyAuthenticator.php`

**Utilisation** :
```bash
curl -X GET http://crm.local/contact/list \
  -H "X-API-KEY: your-secret-api-key"
```

**Configuration** :
Les clés API sont stockées et gérées dans la base de données (table `api_keys`).

### 5.4 Master Authenticator

Mécanisme d'authentification privilégié pour l'administration système.

**Fichier** : `src/Security/MasterAuthenticator.php`

**Usage** : Opérations de maintenance, accès cross-tenant, tâches d'administration.

**Sécurité** :
- Clé secrète forte stockée en variable d'environnement
- Journalisation de toutes les utilisations
- Restriction par IP (optionnel)

### 5.5 Autorisation et permissions

#### Voter personnalisé

**Fichier** : `src/Security/Voter/UserVoter.php`

Contrôle granulaire des permissions :
- Qui peut voir/modifier/supprimer un objet
- Validation de l'ownership
- Respect de la hiérarchie (manager peut voir les objets de son équipe)

**Exemple d'usage dans un contrôleur** :
```php
$this->denyAccessUnlessGranted('edit', $contact);
```

#### Access Decision Manager

**Fichier** : `src/Security/AccessDecisionManager.php`

Stratégie de décision pour l'autorisation :
- `unanimous` : Tous les voters doivent approuver
- `affirmative` : Au moins un voter approuve (défaut)
- `consensus` : Majorité des voters approuvent

#### Rôles disponibles

**Rôles système** :
- `ROLE_USER` : Utilisateur standard
- `ROLE_COMMERCIAL` : Commercial avec accès aux ventes
- `ROLE_MANAGER` : Manager d'équipe
- `ROLE_ADMIN` : Administrateur système
- `ROLE_SUPER_ADMIN` : Super administrateur

**Hiérarchie** (configurable dans `security.yaml`) :
```yaml
security:
    role_hierarchy:
        ROLE_COMMERCIAL: ROLE_USER
        ROLE_MANAGER: ROLE_COMMERCIAL
        ROLE_ADMIN: ROLE_MANAGER
        ROLE_SUPER_ADMIN: ROLE_ADMIN
```

### 5.6 Protection CORS

Configuration dans `config/packages/nelmio_cors.yaml` :

```yaml
nelmio_cors:
    defaults:
        origin_regex: true
        allow_origin: ['%env(CORS_ALLOW_ORIGIN)%']
        allow_methods: ['GET', 'OPTIONS', 'POST', 'PUT', 'PATCH', 'DELETE']
        allow_headers: ['Content-Type', 'Authorization', 'X-API-KEY']
        expose_headers: ['Link']
        max_age: 3600
```

### 5.7 Bonnes pratiques de sécurité

1. **Mots de passe** :
   - Hashage avec `bcrypt` ou `argon2`
   - Politique de complexité
   - Rotation régulière

2. **Tokens JWT** :
   - Durée de vie courte (1h recommandé)
   - Stockage sécurisé côté client (pas en localStorage)
   - Révocation en cas de compromission

3. **API Keys** :
   - Génération aléatoire forte
   - Rotation régulière
   - Limitation par IP si possible

4. **HTTPS** :
   - Obligatoire en production
   - Certificat SSL valide
   - HSTS activé

5. **Audit** :
   - Logs de tous les accès
   - Monitoring des tentatives échouées
   - Alertes sur comportements suspects

---

# PARTIE 2 - MANUEL D'UTILISATION

## 6. Guide de démarrage

### 6.1 Accès à l'application

#### Connexion

1. Ouvrez votre navigateur web
2. Accédez à l'URL de votre CRM : `https://crm.votre-entreprise.com`
3. Entrez vos identifiants :
   - **Email** : votre adresse email professionnelle
   - **Mot de passe** : fourni par votre administrateur

4. Cliquez sur "Se connecter"

**Première connexion** :
Si c'est votre première connexion, vous serez invité à changer votre mot de passe.

**Mot de passe oublié** :
Cliquez sur "Mot de passe oublié" et suivez les instructions envoyées par email.

### 6.2 Interface principale

Après connexion, vous accédez au tableau de bord principal composé de :

```
┌────────────────────────────────────────────────────────────┐
│  Logo CRM              [Recherche...]         👤 Mon Compte │
├────────────────────────────────────────────────────────────┤
│  📊 Dashboard  │ 👥 Contacts  │ 🏢 Entreprises │ 💰 Affaires │
├────────────────────────────────────────────────────────────┤
│                                                              │
│  ┌──────────────────┐  ┌──────────────────┐                │
│  │  Activités du    │  │  Pipeline de     │                │
│  │  jour (5)        │  │  vente           │                │
│  │                  │  │                  │                │
│  │  • 10h00 - RDV   │  │  [Graphique]     │                │
│  │  • 14h30 - Appel │  │                  │                │
│  └──────────────────┘  └──────────────────┘                │
│                                                              │
│  ┌──────────────────────────────────────────────────┐      │
│  │  Dernières affaires                              │      │
│  │  ──────────────────────────────────────────────  │      │
│  │  🔵 Vente CRM - ACME Corp - Proposition          │      │
│  │  🟢 Service Premium - XYZ Ltd - Négociation      │      │
│  └──────────────────────────────────────────────────┘      │
└────────────────────────────────────────────────────────────┘
```

**Menu principal** :
- **Dashboard** : Vue d'ensemble de votre activité
- **Contacts** : Gestion des personnes
- **Entreprises** : Gestion des sociétés
- **Affaires** : Pipeline de vente
- **Activités** : Calendrier et tâches
- **Configuration** : Paramètres (admin seulement)

### 6.3 Navigation rapide

**Barre de recherche** :
Tapez quelques lettres pour rechercher rapidement :
- Un contact (par nom, email, téléphone)
- Une entreprise (par nom)
- Une affaire (par objet)

**Raccourcis clavier** (optionnels) :
- `Ctrl + K` : Recherche rapide
- `Ctrl + N` : Nouveau contact
- `Ctrl + E` : Nouvelle entreprise
- `Ctrl + D` : Nouvelle affaire

---

## 7. Gestion des contacts

### 7.1 Créer un nouveau contact

#### Méthode 1 : Création manuelle

1. Cliquez sur **"Contacts"** dans le menu principal
2. Cliquez sur le bouton **"+ Nouveau contact"**
3. Remplissez le formulaire :

**Informations essentielles** :
- **Prénom** * (obligatoire)
- **Nom** * (obligatoire)
- **Email professionnel** *
- **Téléphone**
- **Fonction/Poste**

**Informations complémentaires** :
- **Entreprise** : Associer à une entreprise existante
- **Source** : Origine du contact (website, salon, référence, cold call, etc.)
- **Responsable** : Commercial en charge
- **Tags** : Catégories (VIP, Prospect, Client, etc.)

4. Cliquez sur **"Enregistrer"**

**Exemple de formulaire** :
```
┌─────────────────────────────────────────────┐
│  Nouveau Contact                            │
├─────────────────────────────────────────────┤
│  Prénom *        [John                    ] │
│  Nom *           [Doe                     ] │
│  Email *         [john.doe@acme.com       ] │
│  Téléphone       [+33 6 12 34 56 78       ] │
│  Fonction        [Directeur IT            ] │
│                                             │
│  Entreprise      [🔍 ACME Corp         ▼  ] │
│  Source          [Website              ▼  ] │
│  Responsable     [commercial@vous.com  ▼  ] │
│                                             │
│  Tags            [☐ VIP  ☐ Prospect  ☑ Tech]│
│                                             │
│  Photo           [Choisir fichier...]       │
│                                             │
│  [Annuler]              [Enregistrer]       │
└─────────────────────────────────────────────┘
```

#### Méthode 2 : Import depuis fichier CSV/Excel

Voir section [13. Import/Export](#13-importexport-de-données)

### 7.2 Rechercher et consulter un contact

#### Recherche simple

1. Allez dans **"Contacts"**
2. Utilisez la barre de recherche en haut
3. Tapez le nom, email ou numéro de téléphone
4. Les résultats s'affichent en temps réel

#### Recherche avancée

1. Cliquez sur **"Filtres avancés"**
2. Combinez plusieurs critères :
   - Par entreprise
   - Par tags
   - Par responsable
   - Par source
   - Par date de création

3. Cliquez sur **"Appliquer"**

#### Vue liste des contacts

```
┌──────────────────────────────────────────────────────────────┐
│  Contacts (150)                        [+ Nouveau] [Import]  │
├──────────────────────────────────────────────────────────────┤
│  [🔍 Rechercher...]     [Filtres ▼]        Page 1/6          │
├──────────────────────────────────────────────────────────────┤
│  📷  Nom              Email              Entreprise    Tags  │
│  ─────────────────────────────────────────────────────────── │
│  👤  John Doe         john@acme.com      ACME Corp     VIP   │
│  👤  Jane Smith       jane@xyz.com       XYZ Ltd       Lead  │
│  👤  Bob Martin       bob@tech.io        TechCorp      Client│
│  ...                                                          │
│                                                               │
│                    [←] [1] [2] [3] [→]                        │
└──────────────────────────────────────────────────────────────┘
```

### 7.3 Consulter la fiche détaillée d'un contact

Cliquez sur un contact dans la liste pour voir sa fiche complète :

```
┌──────────────────────────────────────────────────────────────┐
│  👤 John Doe                           [Modifier] [Supprimer]│
├──────────────────────────────────────────────────────────────┤
│  📧 john.doe@acme.com  │  📞 +33 6 12 34 56 78                │
│  🏢 ACME Corp          │  👔 Directeur IT                     │
│  🏷️ VIP, Prospect, Tech │  📍 Paris, France                   │
├──────────────────────────────────────────────────────────────┤
│  [Informations] [Affaires] [Activités] [Notes] [Fichiers]   │
├──────────────────────────────────────────────────────────────┤
│                                                               │
│  📊 Affaires en cours (2)                                     │
│  ─────────────────────────────────────────────────────────   │
│  💰 Vente CRM Enterprise - 50K€ - Proposition                │
│  💰 Formation équipe - 5K€ - Qualification                   │
│                                                               │
│  📅 Prochaines activités                                      │
│  ─────────────────────────────────────────────────────────   │
│  🗓️ 15/10/2025 10:00 - Rendez-vous de démonstration          │
│  📞 18/10/2025 14:30 - Appel de suivi                         │
│                                                               │
│  📝 Dernières notes                                           │
│  ─────────────────────────────────────────────────────────   │
│  12/10/2025 - Très intéressé par la solution, décisionnaire  │
│  08/10/2025 - Premier contact lors du salon Tech 2025        │
│                                                               │
└──────────────────────────────────────────────────────────────┘
```

### 7.4 Modifier un contact

1. Ouvrez la fiche du contact
2. Cliquez sur **"Modifier"**
3. Modifiez les champs souhaités
4. Cliquez sur **"Enregistrer"**

**Note** : Toutes les modifications sont tracées (qui, quand, depuis quelle IP)

### 7.5 Ajouter des informations à un contact

#### Ajouter un numéro de téléphone

1. Ouvrez la fiche du contact
2. Dans la section "Téléphones", cliquez sur **"+ Ajouter"**
3. Renseignez :
   - **Numéro** : +33 6 XX XX XX XX
   - **Type** : Mobile, Fixe, Fax
4. **"Enregistrer"**

#### Ajouter une adresse email

1. Section "Emails"
2. **"+ Ajouter"**
3. Renseignez :
   - **Email** : email@domain.com
   - **Type** : Professionnel, Personnel
4. **"Enregistrer"**

#### Ajouter une note

1. Onglet **"Notes"**
2. **"+ Nouvelle note"**
3. Rédigez votre note (observations, compte-rendu d'échange, etc.)
4. **"Enregistrer"**

**Exemple** :
```
┌──────────────────────────────────────────┐
│  Nouvelle Note                           │
├──────────────────────────────────────────┤
│  [Compte-rendu appel du 12/10/2025     ]│
│  [                                      ]│
│  [Client très intéressé par notre offre]│
│  [CRM. Souhaite une démo avant fin du  ]│
│  [mois. Budget : ~50K€                  ]│
│  [                                      ]│
│  [                                      ]│
│  [Annuler]              [Enregistrer]    │
└──────────────────────────────────────────┘
```

#### Joindre un fichier

1. Onglet **"Fichiers"**
2. **"+ Ajouter fichier"**
3. Choisissez le fichier (PDF, Word, Excel, image, etc.)
4. Ajoutez une description (optionnel)
5. **"Envoyer"**

**Types de fichiers utiles** :
- Contrats signés
- Devis
- Présentations envoyées
- Cartes de visite scannées
- Photos d'événements

### 7.6 Associer un contact à une entreprise

#### Option 1 : Lors de la création
Sélectionnez l'entreprise dans le champ "Entreprise" du formulaire.

#### Option 2 : Depuis la fiche contact
1. Ouvrez la fiche du contact
2. Cliquez sur **"Modifier"**
3. Champ "Entreprise" : recherchez et sélectionnez
4. **"Enregistrer"**

#### Option 3 : Action rapide
1. Vue liste des contacts
2. Menu "..." à droite du contact
3. **"Associer à une entreprise"**
4. Recherchez et sélectionnez l'entreprise
5. **"Confirmer"**

### 7.7 Fusionner des contacts en doublon

Quand vous détectez un doublon (même personne saisie 2 fois) :

1. Identifiez les 2 contacts
2. Menu **"Actions"** > **"Fusionner des contacts"**
3. Sélectionnez :
   - **Contact source** : celui à supprimer
   - **Contact cible** : celui à conserver
4. Prévisualisez la fusion
5. **"Confirmer la fusion"**

**Résultat** :
- Le contact cible conserve toutes ses infos
- Les infos du contact source sont transférées (affaires, activités, notes)
- Le contact source est marqué comme supprimé

**Exemple** :
```
┌────────────────────────────────────────────────┐
│  Fusionner les contacts                        │
├────────────────────────────────────────────────┤
│  Contact source (sera supprimé)                │
│  👤 John Doe                                    │
│     john@acme.com                              │
│     2 affaires, 3 notes                        │
│                                                │
│  Contact cible (sera conservé)                 │
│  👤 John DOE                                    │
│     john.doe@acme.com                          │
│     1 affaire, 5 notes                         │
│                                                │
│  Après fusion, le contact cible aura :         │
│  • 3 affaires (1 + 2)                          │
│  • 8 notes (5 + 3)                             │
│  • Tous les emails, téléphones et fichiers     │
│                                                │
│  [Annuler]              [Confirmer]            │
└────────────────────────────────────────────────┘
```

### 7.8 Supprimer un contact

**Attention** : La suppression est logique (le contact n'est pas vraiment effacé, il est marqué comme supprimé).

1. Ouvrez la fiche du contact
2. Menu **"Actions"** > **"Supprimer"**
3. Confirmez la suppression

**Restauration** : Un administrateur peut restaurer un contact supprimé depuis l'interface d'administration.

---

## 8. Gestion des entreprises

### 8.1 Créer une nouvelle entreprise

1. Menu **"Entreprises"**
2. **"+ Nouvelle entreprise"**
3. Remplissez le formulaire :

**Informations principales** :
- **Nom de l'entreprise** * (obligatoire)
- **Secteur d'activité**
- **Site web**
- **Téléphone principal**
- **Email général**

**Informations complémentaires** :
- **Adresse complète**
- **Chiffre d'affaires**
- **Nombre d'employés**
- **Tags** : Client, Prospect, Partenaire, etc.

4. **"Enregistrer"**

**Exemple** :
```
┌─────────────────────────────────────────────┐
│  Nouvelle Entreprise                        │
├─────────────────────────────────────────────┤
│  Nom *           [ACME Corporation        ] │
│  Secteur         [Technologie            ▼] │
│  Site web        [https://acme.com        ] │
│  Téléphone       [+33 1 23 45 67 89       ] │
│  Email           [contact@acme.com        ] │
│                                             │
│  Adresse         [123 Avenue des Champs   ] │
│                  [75008 Paris, France     ] │
│                                             │
│  CA annuel       [5M€                     ] │
│  Effectif        [50-100               ▼  ] │
│                                             │
│  Tags            [☑ Client  ☐ VIP  ☐ Lead] │
│                                             │
│  Logo            [Choisir fichier...]       │
│                                             │
│  [Annuler]              [Enregistrer]       │
└─────────────────────────────────────────────┘
```

### 8.2 Rechercher une entreprise

#### Recherche rapide
Endpoint API : `GET /company/search/{contains}`

Dans l'interface :
1. Menu **"Entreprises"**
2. Barre de recherche : tapez le nom de l'entreprise
3. Résultats en temps réel

#### Vue liste
```
┌──────────────────────────────────────────────────────────────┐
│  Entreprises (75)                      [+ Nouvelle] [Import] │
├──────────────────────────────────────────────────────────────┤
│  [🔍 Rechercher...]     [Filtres ▼]        Page 1/3          │
├──────────────────────────────────────────────────────────────┤
│  🏢  Nom                Secteur        Contacts    CA         │
│  ─────────────────────────────────────────────────────────── │
│  🏢  ACME Corp          Technologie    12          5M€        │
│  🏢  XYZ Ltd            Services       8           2M€        │
│  🏢  TechCorp           IT             15          10M€       │
│  ...                                                          │
└──────────────────────────────────────────────────────────────┘
```

### 8.3 Fiche détaillée d'une entreprise

```
┌──────────────────────────────────────────────────────────────┐
│  🏢 ACME Corporation                   [Modifier] [Supprimer]│
├──────────────────────────────────────────────────────────────┤
│  🌐 https://acme.com   │  📞 +33 1 23 45 67 89                │
│  📧 contact@acme.com   │  🏷️ Client, VIP, Technologie          │
│  📍 75008 Paris        │  👥 12 contacts                       │
├──────────────────────────────────────────────────────────────┤
│  [Informations] [Contacts] [Affaires] [Activités] [Fichiers]│
├──────────────────────────────────────────────────────────────┤
│                                                               │
│  👥 Contacts (12)                                             │
│  ─────────────────────────────────────────────────────────   │
│  👤 John Doe - Directeur IT - john.doe@acme.com              │
│  👤 Jane Smith - CEO - jane.smith@acme.com                   │
│  👤 Bob Martin - CTO - bob.martin@acme.com                   │
│  [+ Ajouter un contact]                                       │
│                                                               │
│  💰 Affaires en cours (3)                                     │
│  ─────────────────────────────────────────────────────────   │
│  💰 Vente CRM Enterprise - 50K€ - Proposition                │
│  💰 Migration Cloud - 150K€ - Négociation                    │
│  💰 Support Premium - 10K€/an - Contrat signé                │
│                                                               │
└──────────────────────────────────────────────────────────────┘
```

### 8.4 Gérer les contacts d'une entreprise

#### Ajouter un contact existant
1. Fiche entreprise > Onglet **"Contacts"**
2. **"+ Ajouter un contact existant"**
3. Recherchez le contact
4. **"Associer"**

#### Créer un nouveau contact pour l'entreprise
1. Fiche entreprise > Onglet **"Contacts"**
2. **"+ Nouveau contact"**
3. L'entreprise est pré-remplie
4. Remplissez le formulaire
5. **"Enregistrer"**

#### Retirer un contact
1. Liste des contacts de l'entreprise
2. Menu "..." > **"Dissocier de l'entreprise"**
3. **"Confirmer"**

**Note** : Le contact n'est pas supprimé, juste dissocié de l'entreprise.

### 8.5 Modifier une entreprise

1. Fiche entreprise
2. **"Modifier"**
3. Modifiez les champs
4. **"Enregistrer"**

### 8.6 Ajouter un logo

1. Fiche entreprise
2. Section "Logo"
3. **"Choisir un fichier"** ou glisser-déposer
4. **"Envoyer"**

**Formats acceptés** : JPG, PNG, SVG
**Taille recommandée** : 200x200 px minimum

### 8.7 Supprimer une entreprise

**Attention** : Suppression logique uniquement.

1. Fiche entreprise
2. Menu **"Actions"** > **"Supprimer"**
3. **Confirmer**

**Important** : Les contacts associés ne sont pas supprimés, juste dissociés.

---

## 9. Gestion des affaires (Deals)

### 9.1 Qu'est-ce qu'une affaire ?

Une **affaire** (deal) représente une opportunité commerciale en cours. Elle progresse à travers les étapes d'un **pipeline de vente** jusqu'à être gagnée ou perdue.

**Éléments d'une affaire** :
- **Objet** : Description de la vente (ex: "Vente CRM Enterprise")
- **Contact principal** : Décisionnaire ou contact clé
- **Entreprise** : Société cliente
- **Responsable** : Commercial en charge
- **Étape** : Position dans le pipeline (Qualification, Proposition, Négociation, etc.)
- **Produits** : Liste des produits/services vendus
- **Participants** : Autres contacts impliqués
- **Statut** : En cours, Gagné, Perdu

### 9.2 Créer une nouvelle affaire

1. Menu **"Affaires"** ou depuis une fiche Contact/Entreprise
2. **"+ Nouvelle affaire"**
3. Remplissez le formulaire :

```
┌─────────────────────────────────────────────┐
│  Nouvelle Affaire                           │
├─────────────────────────────────────────────┤
│  Objet *         [Vente CRM Enterprise    ] │
│  Contact *       [🔍 John Doe (ACME)    ▼ ] │
│  Entreprise *    [🔍 ACME Corporation   ▼ ] │
│  Responsable *   [commercial@vous.com   ▼ ] │
│                                             │
│  Pipeline        [Ventes B2B            ▼ ] │
│  Étape           [1. Qualification      ▼ ] │
│                                             │
│  Produits/Services                          │
│  [CRM_ENTERPRISE                          ] │
│  [SUPPORT_PREMIUM                         ] │
│  [+ Ajouter]                                │
│                                             │
│  Montant estimé  [50 000 €                ] │
│                                             │
│  Participants                               │
│  [☑ Jane Smith - CEO                      ] │
│  [☑ Bob Martin - CTO                      ] │
│                                             │
│  Tags            [☐ Urgent  ☑ Cloud  ☐ VIP]│
│                                             │
│  [Annuler]              [Créer l'affaire]   │
└─────────────────────────────────────────────┘
```

4. **"Créer l'affaire"**

**API correspondante** :
```bash
POST /deal/edit
Content-Type: application/json

{
  "object": "Vente CRM Enterprise",
  "contact": {"id": 1},
  "company": {"id": 1},
  "manager": "commercial@vous.com",
  "step": {"id": 1},
  "products": ["CRM_ENTERPRISE", "SUPPORT_PREMIUM"],
  "participants": [{"id": 2}, {"id": 3}],
  "tags": [{"id": 5}]
}
```

### 9.3 Vue Pipeline (Kanban)

L'affichage principal des affaires se fait en mode **Kanban** par étapes de pipeline :

```
┌──────────────────────────────────────────────────────────────────────┐
│  Pipeline: Ventes B2B                         [+ Nouvelle affaire]   │
├──────────────────────────────────────────────────────────────────────┤
│  Qualification   │  Proposition    │  Négociation  │  Contrat         │
│  (10%)           │  (50%)          │  (75%)        │  (90%)           │
│  ──────────────  │  ──────────────  │  ────────────  │  ──────────────  │
│                  │                  │                │                  │
│  ┌────────────┐  │  ┌────────────┐  │  ┌──────────┐  │  ┌──────────┐  │
│  │ ACME Corp  │  │  │ XYZ Ltd    │  │  │ TechCorp │  │  │ GlobalCo │  │
│  │ CRM - 50K€ │  │  │ Cloud      │  │  │ Support  │  │  │ Enterprise│ │
│  │ John Doe   │  │  │ Migration  │  │  │ Premium  │  │  │ License  │  │
│  └────────────┘  │  │ 150K€      │  │  │ 10K€     │  │  │ 200K€    │  │
│                  │  │ Jane Smith │  │  └──────────┘  │  └──────────┘  │
│  ┌────────────┐  │  └────────────┘  │                │                  │
│  │ StartupX   │  │                  │  ┌──────────┐  │                  │
│  │ SaaS - 5K€ │  │  ┌────────────┐  │  │ ABC Inc  │  │                  │
│  │ Bob Martin │  │  │ BizCorp    │  │  │ Custom   │  │                  │
│  └────────────┘  │  │ API        │  │  │ Dev      │  │                  │
│                  │  │ 30K€       │  │  │ 75K€     │  │                  │
│  Total: 55K€     │  └────────────┘  │  └──────────┘  │                  │
│  2 affaires      │  Total: 180K€    │  Total: 85K€   │  Total: 200K€    │
│                  │  2 affaires      │  2 affaires    │  1 affaire       │
└──────────────────────────────────────────────────────────────────────┘
```

**Interactions** :
- **Glisser-déposer** : Déplacez une affaire d'une étape à l'autre
- **Clic** : Ouvrez la fiche détaillée
- **Filtres** : Par responsable, par tags, par montant

### 9.4 Fiche détaillée d'une affaire

```
┌──────────────────────────────────────────────────────────────┐
│  💰 Vente CRM Enterprise - 50 000€      [Modifier] [Clôturer]│
├──────────────────────────────────────────────────────────────┤
│  🏢 ACME Corporation   │  👤 John Doe (contact principal)     │
│  👔 commercial@vous.com│  📊 Étape: Proposition (50%)         │
│  📅 Créée le 01/10/25  │  🏷️ Cloud, Enterprise                 │
├──────────────────────────────────────────────────────────────┤
│  [Détails] [Activités] [Notes] [Participants] [Fichiers]    │
├──────────────────────────────────────────────────────────────┤
│                                                               │
│  📦 Produits/Services                                         │
│  ─────────────────────────────────────────────────────────   │
│  • CRM_ENTERPRISE - Licence annuelle                         │
│  • SUPPORT_PREMIUM - Support 24/7                            │
│  • TRAINING - Formation équipe 5 jours                       │
│                                                               │
│  👥 Participants                                              │
│  ─────────────────────────────────────────────────────────   │
│  👤 John Doe - Directeur IT (contact principal)              │
│  👤 Jane Smith - CEO (décisionnaire)                         │
│  👤 Bob Martin - CTO (prescripteur technique)                │
│                                                               │
│  📅 Activités planifiées                                      │
│  ─────────────────────────────────────────────────────────   │
│  🗓️ 15/10/2025 10:00 - Démonstration produit                 │
│  📞 20/10/2025 15:00 - Appel de suivi décision               │
│  📧 22/10/2025 - Envoi proposition commerciale               │
│  [+ Nouvelle activité]                                        │
│                                                               │
│  📝 Historique                                                │
│  ─────────────────────────────────────────────────────────   │
│  12/10/2025 - Déplacée vers "Proposition"                    │
│  08/10/2025 - Premier contact lors du salon                  │
│  01/10/2025 - Affaire créée                                  │
│                                                               │
└──────────────────────────────────────────────────────────────┘
```

### 9.5 Faire progresser une affaire dans le pipeline

#### Méthode 1 : Glisser-déposer (Vue Kanban)
1. Saisissez la carte de l'affaire
2. Glissez-la vers l'étape suivante
3. Relâchez

L'affaire change automatiquement d'étape.

#### Méthode 2 : Depuis la fiche détaillée
1. Ouvrez la fiche de l'affaire
2. Section "Étape actuelle"
3. Cliquez sur **"Passer à l'étape suivante"**
4. Ou sélectionnez une étape spécifique dans le menu déroulant
5. **"Enregistrer"**

**API** :
```bash
PATCH /deal/change/step/{id}
Content-Type: application/json

{
  "step_id": 3
}
```

**Traçabilité** :
Chaque changement d'étape est enregistré dans l'historique de l'affaire.

### 9.6 Marquer une affaire comme gagnée

Quand vous concluez la vente :

1. Fiche de l'affaire
2. Bouton **"Marquer comme Gagnée"** (vert)
3. Optionnel : Ajouter une note de clôture
4. **"Confirmer"**

**Résultat** :
- Statut = "Gagné"
- L'affaire sort du pipeline actif
- Statistiques de vente mises à jour
- Notifications envoyées à l'équipe

**API** :
```bash
GET /deal/win/{id}
```

### 9.7 Marquer une affaire comme perdue

Si la vente ne se conclut pas :

1. Fiche de l'affaire
2. Bouton **"Marquer comme Perdue"** (rouge)
3. **Important** : Indiquez la raison de la perte :
   - Prix trop élevé
   - Concurrent retenu
   - Projet abandonné
   - Timing inapproprié
   - Autre (préciser)
4. Optionnel : Note explicative
5. **"Confirmer"**

**API** :
```bash
GET /deal/lose/{id}
```

**Bonnes pratiques** :
- Toujours documenter pourquoi l'affaire est perdue
- Analyser les raisons de perte pour s'améliorer
- Possibilité de réactiver l'affaire plus tard si le contexte change

### 9.8 Réactiver une affaire close

Pour rouvrir une affaire gagnée ou perdue :

1. Recherchez l'affaire dans les archives
2. **"Réactiver l'affaire"**
3. Sélectionnez la nouvelle étape de départ
4. **"Confirmer"**

**API** :
```bash
GET /deal/unlose/unwin/{id}
```

### 9.9 Supprimer une affaire

**Attention** : Suppression logique.

1. Fiche affaire
2. Menu **"Actions"** > **"Supprimer"**
3. **Confirmer**

**API** :
```bash
DELETE /deal/delete/{id}
```

---

## 10. Gestion des activités

### 10.1 Qu'est-ce qu'une activité ?

Une **activité** est une tâche à réaliser ou un événement planifié en lien avec votre CRM :
- **Appel téléphonique**
- **Rendez-vous client**
- **Email à envoyer**
- **Tâche à accomplir**
- **Deadline** (échéance)

Chaque activité peut être liée à :
- Un contact
- Une entreprise
- Une affaire

### 10.2 Types d'activités

- **call** : Appel téléphonique
- **meeting** : Rendez-vous physique ou visioconférence
- **email** : Email à envoyer
- **task** : Tâche générale
- **deadline** : Échéance importante

### 10.3 Créer une nouvelle activité

1. Depuis le calendrier : cliquez sur une date/heure
2. Depuis une fiche Contact/Entreprise/Affaire : **"+ Nouvelle activité"**
3. Remplissez le formulaire :

```
┌─────────────────────────────────────────────┐
│  Nouvelle Activité                          │
├─────────────────────────────────────────────┤
│  Nom *           [Démonstration produit   ] │
│  Type *          [Meeting               ▼ ] │
│                                             │
│  Date début *    [15/10/2025   10:00     ] │
│  Date fin *      [15/10/2025   11:30     ] │
│  Lieu            [Bureaux client          ] │
│                                             │
│  Liée à :                                   │
│  Contact         [🔍 John Doe           ▼ ] │
│  Entreprise      [🔍 ACME Corp          ▼ ] │
│  Affaire         [🔍 Vente CRM 50K€     ▼ ] │
│                                             │
│  Responsables    [☑ commercial@vous.com   ] │
│                  [☑ manager@vous.com      ] │
│                                             │
│  🔔 Notification                            │
│  ☑ Me notifier   [30 minutes avant      ▼ ] │
│                                             │
│  Description                                │
│  [Démonstration complète de la solution   ]│
│  [CRM avec focus sur les fonctionnalités  ]│
│  [de reporting demandées par le client    ]│
│                                             │
│  ☐ Marquer comme effectuée                  │
│                                             │
│  [Annuler]              [Créer]             │
└─────────────────────────────────────────────┘
```

4. **"Créer"**

**API** :
```bash
POST /activity/edit
Content-Type: application/json

{
  "name": "Démonstration produit",
  "type": "meeting",
  "startDate": "2025-10-15T10:00:00Z",
  "endDate": "2025-10-15T11:30:00Z",
  "location": "Bureaux client",
  "performed": false,
  "notify": true,
  "notifyDate": "2025-10-15T09:30:00Z",
  "description": "Démo complète CRM",
  "deal": {"id": 5},
  "contact": {"id": 1},
  "company": {"id": 1},
  "managers": ["commercial@vous.com", "manager@vous.com"]
}
```

### 10.4 Vue Calendrier

```
┌──────────────────────────────────────────────────────────────┐
│  Octobre 2025                    [Jour] [Semaine] [Mois]     │
├──────────────────────────────────────────────────────────────┤
│  Lun  │  Mar  │  Mer  │  Jeu  │  Ven  │  Sam  │  Dim        │
├──────────────────────────────────────────────────────────────┤
│       │       │   1   │   2   │   3   │   4   │   5         │
│       │       │       │       │       │       │              │
│   6   │   7   │   8   │   9   │  10   │  11   │  12         │
│       │       │       │       │       │       │              │
│  13   │  14   │  15   │  16   │  17   │  18   │  19         │
│       │       │ 10:00 │       │ 14:00 │       │              │
│       │       │ Démo  │       │ Appel │       │              │
│       │       │ ACME  │       │ XYZ   │       │              │
│  20   │  21   │  22   │  23   │  24   │  25   │  26         │
│ 15:00 │       │       │       │       │       │              │
│ Suivi │       │       │       │       │       │              │
└──────────────────────────────────────────────────────────────┘
```

**Navigation** :
- Cliquez sur une activité pour voir les détails
- Double-clic sur une plage horaire pour créer une activité
- Glisser-déposer pour déplacer une activité

### 10.5 Vue Liste des activités

```
┌──────────────────────────────────────────────────────────────┐
│  Activités                    [Calendrier] [Liste] [+ Créer] │
├──────────────────────────────────────────────────────────────┤
│  [Filtres: ▼ Toutes  ▼ Tous responsables  ▼ Toutes affaires]│
├──────────────────────────────────────────────────────────────┤
│  📅 Aujourd'hui (15 octobre 2025)                             │
│  ──────────────────────────────────────────────────────────  │
│  ⏰ 10:00-11:30  🗓️  Démonstration ACME Corp                  │
│                      John Doe • Vente CRM 50K€               │
│                      [☐ Effectuée]                           │
│                                                               │
│  ⏰ 14:00-14:30  📞  Appel de suivi XYZ Ltd                   │
│                      Jane Smith • Migration Cloud            │
│                      [☐ Effectuée]                           │
│                                                               │
│  📅 Demain (16 octobre 2025)                                  │
│  ──────────────────────────────────────────────────────────  │
│  ⏰ 09:00-10:00  📞  Qualification prospect                   │
│                      Bob Martin • Nouvelle affaire           │
│                      [☐ Effectuée]                           │
│                                                               │
│  📅 Cette semaine                                             │
│  ──────────────────────────────────────────────────────────  │
│  ⏰ 17/10 15:00  📧  Envoi proposition TechCorp               │
│  ⏰ 18/10 10:30  🗓️  Présentation produit GlobalCo            │
│  ...                                                          │
└──────────────────────────────────────────────────────────────┘
```

### 10.6 Marquer une activité comme effectuée

#### Option 1 : Depuis la liste
Cochez la case **"☐ Effectuée"** à côté de l'activité.

#### Option 2 : Depuis la fiche activité
1. Ouvrez l'activité
2. Bouton **"Marquer comme effectuée"**
3. Optionnel : Ajoutez une note de compte-rendu
4. **"Enregistrer"**

**Résultat** :
- L'activité passe en statut "Effectuée"
- Elle disparaît de la liste des tâches à faire
- Disponible dans l'historique

### 10.7 Notifications

Le système peut vous notifier avant une activité :

**Paramétrage** :
- **30 minutes avant** (par défaut)
- **1 heure avant**
- **1 jour avant**
- **Personnalisé**

**Canaux de notification** (selon configuration) :
- Email
- Push notification (application mobile)
- SMS (optionnel)
- Notification navigateur

**Activer les notifications** :
1. Lors de la création/modification d'une activité
2. Cochez **"☑ Me notifier"**
3. Choisissez le délai
4. **"Enregistrer"**

### 10.8 Modifier une activité

1. Cliquez sur l'activité (calendrier ou liste)
2. **"Modifier"**
3. Changez les champs souhaités
4. **"Enregistrer"**

### 10.9 Supprimer une activité

1. Ouvrez l'activité
2. **"Supprimer"**
3. **Confirmer**

**API** :
```bash
DELETE /activity/delete/{id}
```

---

## 11. Pipelines de vente

### 11.1 Qu'est-ce qu'un pipeline ?

Un **pipeline de vente** est un processus structuré que suivent vos affaires commerciales, de la prospection à la conclusion.

**Exemple de pipeline B2B** :
```
[Qualification] → [Proposition] → [Négociation] → [Contrat] → [Gagné]
     10%              50%              75%            90%       100%
```

Chaque étape a :
- Un **nom** (Qualification, Proposition, etc.)
- Une **probabilité de succès** (10%, 50%, etc.)
- Une **couleur** pour l'affichage
- Un **ordre** (ranking)

### 11.2 Consulter les pipelines

1. Menu **"Configuration"** > **"Pipelines"** (admin)
2. Liste des pipelines configurés

```
┌──────────────────────────────────────────────────────────────┐
│  Pipelines de vente                          [+ Nouveau]     │
├──────────────────────────────────────────────────────────────┤
│  Pipeline                 Étapes   Affaires   Utilisé par    │
│  ──────────────────────────────────────────────────────────  │
│  🔵 Ventes B2B             5         15        Commerciaux    │
│  🟢 Ventes B2C             4          8        Tous           │
│  🟠 Partenariats           6          3        Managers       │
└──────────────────────────────────────────────────────────────┘
```

### 11.3 Créer un nouveau pipeline

**Requis** : Rôle Admin

1. **"+ Nouveau pipeline"**
2. Formulaire :

```
┌─────────────────────────────────────────────┐
│  Nouveau Pipeline                           │
├─────────────────────────────────────────────┤
│  Nom *           [Ventes Internationales  ] │
│  Description     [Pipeline pour clients   ] │
│                  [hors France             ] │
│                                             │
│  Rôles autorisés                            │
│  [☑ ROLE_COMMERCIAL                       ] │
│  [☑ ROLE_MANAGER                          ] │
│  [☐ ROLE_ADMIN                            ] │
│                                             │
│  [Annuler]       [Créer]  [Créer et config]│
└─────────────────────────────────────────────┘
```

3. **"Créer et configurer"** pour passer aux étapes

**API** :
```bash
POST /pipeline/edit
{
  "name": "Ventes Internationales",
  "description": "Pipeline pour clients hors France",
  "roles": ["ROLE_COMMERCIAL", "ROLE_MANAGER"]
}
```

### 11.4 Ajouter des étapes à un pipeline

1. Créez le pipeline ou éditez un existant
2. Section **"Étapes"**
3. **"+ Ajouter une étape"**

```
┌─────────────────────────────────────────────┐
│  Nouvelle Étape                             │
├─────────────────────────────────────────────┤
│  Nom *            [Découverte             ] │
│  Description      [Premier contact et     ] │
│                   [qualification des      ] │
│                   [besoins                ] │
│                                             │
│  Probabilité *    [10] %                    │
│  Couleur          [#FFC107] [🎨]            │
│  Ordre            [1]                       │
│                                             │
│  Pipeline         [Ventes Internationales▼] │
│                                             │
│  [Annuler]              [Créer]             │
└─────────────────────────────────────────────┘
```

4. Répétez pour chaque étape

**Exemple d'étapes complètes** :
```
1. Découverte       - 10% - Jaune
2. Qualification    - 25% - Orange
3. Proposition      - 50% - Bleu
4. Négociation      - 75% - Violet
5. Contrat          - 90% - Vert foncé
6. Signé            - 100% - Vert
```

**API** :
```bash
POST /pipeline-step/edit
{
  "name": "Découverte",
  "description": "Premier contact",
  "successProbability": 10.0,
  "color": "#FFC107",
  "ranking": "1",
  "pipeline": {"id": 2}
}
```

### 11.5 Modifier un pipeline ou une étape

1. Liste des pipelines
2. Cliquez sur le pipeline
3. **"Modifier"** (pipeline) ou sur une étape spécifique
4. Modifiez les champs
5. **"Enregistrer"**

### 11.6 Supprimer une étape

**Attention** : Vous ne pouvez pas supprimer une étape contenant des affaires actives.

1. Transférez d'abord toutes les affaires vers une autre étape
2. Puis **"Supprimer l'étape"**

### 11.7 Supprimer un pipeline

**Attention** : Impossible si des affaires actives utilisent ce pipeline.

1. Fermez ou déplacez toutes les affaires
2. **"Supprimer le pipeline"**

### 11.8 Bonnes pratiques pipelines

**Nombre d'étapes** :
- **B2B complexe** : 5-7 étapes
- **B2B simple** : 4-5 étapes
- **B2C** : 3-4 étapes

**Probabilités** :
- Basées sur vos statistiques réelles
- Révisez tous les trimestres
- Utiles pour prévisions de chiffre d'affaires

**Nommage** :
- Clair et actionnable
- "Découverte" plutôt que "Étape 1"
- Refléter l'action à faire

---

## 12. Tags et catégorisation

### 12.1 Qu'est-ce qu'un tag ?

Un **tag** (étiquette) est un label que vous pouvez attacher à :
- Contacts
- Entreprises
- Affaires

**Exemples de tags** :
- **Statut** : Prospect, Client, Ancien client, Partenaire
- **Priorité** : VIP, Urgent, Standard
- **Secteur** : Technologie, Finance, Santé, Industrie
- **Origine** : Salon, Website, Référence, Cold call
- **Autre** : Newsletter, Beta testeur, Influenceur

### 12.2 Créer un tag

**Requis** : Rôle Admin ou Manager

1. Menu **"Configuration"** > **"Tags"**
2. **"+ Nouveau tag"**
3. Formulaire :

```
┌─────────────────────────────────────────────┐
│  Nouveau Tag                                │
├─────────────────────────────────────────────┤
│  Libellé *       [Client VIP              ] │
│  Code *          [vip                     ] │
│                  (pour API, sans espaces)   │
│                                             │
│  Description     [Client à forte valeur   ] │
│                  [stratégique pour l'     ] │
│                  [entreprise              ] │
│                                             │
│  Couleur         [#FF5722] [🎨]             │
│                  ████████ (rouge)           │
│                                             │
│  [Annuler]              [Créer]             │
└─────────────────────────────────────────────┘
```

4. **"Créer"**

**API** :
```bash
POST /tag/edit
{
  "label": "Client VIP",
  "code": "vip",
  "description": "Client à forte valeur stratégique",
  "color": "#FF5722"
}
```

### 12.3 Utiliser les tags

#### Ajouter un tag à un contact

1. Fiche du contact > **"Modifier"**
2. Section **"Tags"**
3. Sélectionnez un ou plusieurs tags
4. **"Enregistrer"**

#### Ajouter un tag à une entreprise

Même processus que pour les contacts.

#### Ajouter un tag à une affaire

Même processus.

### 12.4 Filtrer par tags

Dans les vues liste (Contacts, Entreprises, Affaires) :

1. **"Filtres"**
2. Section **"Tags"**
3. Sélectionnez un ou plusieurs tags
4. **"Appliquer"**

**Résultat** : Seuls les éléments avec AU MOINS un des tags sélectionnés s'affichent.

**Combinaison ET** : Certains CRM permettent de filtrer par "Tag1 ET Tag2" (tous les tags doivent être présents).

### 12.5 Modifier un tag

1. Liste des tags
2. Cliquez sur le tag
3. **"Modifier"**
4. Changez libellé, couleur ou description
5. **"Enregistrer"**

**Note** : La modification d'un tag affecte tous les objets qui l'utilisent.

### 12.6 Supprimer un tag

1. Liste des tags
2. **"Supprimer"**
3. **Confirmer**

**Attention** : Le tag sera retiré de tous les contacts/entreprises/affaires qui l'utilisent.

### 12.7 Suggestions de tags pour démarrer

**Statuts clients** :
- Prospect
- Lead qualifié
- Client actif
- Ancien client
- Partenaire

**Priorités** :
- VIP
- Urgent
- Standard

**Secteurs** :
- Technologie
- Finance
- Santé
- Industrie
- Services
- Commerce

**Origines** :
- Website
- Salon professionnel
- Référence
- Cold call
- Réseaux sociaux
- Publicité

**Taille** (entreprises) :
- TPE (< 10)
- PME (10-250)
- ETI (250-5000)
- Grande entreprise (> 5000)

**Engagement** :
- Newsletter
- Beta testeur
- Ambassadeur
- Influenceur

---

## 13. Import/Export de données

### 13.1 Importer des contacts

#### Format du fichier

Le système accepte les formats **CSV** et **Excel** (.xlsx).

**Structure minimale du fichier** :
```csv
Prénom,Nom,Email,Téléphone,Entreprise,Fonction,Source
John,Doe,john@acme.com,+33612345678,ACME Corp,Directeur IT,website
Jane,Smith,jane@xyz.com,+33698765432,XYZ Ltd,CEO,salon
Bob,Martin,bob@tech.io,+33654321098,TechCorp,CTO,référence
```

**Colonnes supportées** :
- Colonnes obligatoires : Email (au minimum)
- Colonnes standards : Prénom, Nom, Téléphone, Entreprise, etc.
- Colonnes personnalisées : Selon vos PropertyModel configurés

#### Processus d'import

1. Menu **"Contacts"**
2. Bouton **"Import"**
3. **"Choisir un fichier"** ou glisser-déposer

```
┌─────────────────────────────────────────────┐
│  Import de contacts                         │
├─────────────────────────────────────────────┤
│  📁 Glissez votre fichier ici               │
│      ou                                     │
│  [Choisir un fichier...]                    │
│                                             │
│  Formats acceptés: CSV, Excel (.xlsx)       │
│  Taille max: 10 Mo                          │
│                                             │
│  ℹ️  Assurez-vous que votre fichier         │
│     contient au minimum la colonne Email    │
│                                             │
│  [Télécharger modèle]                       │
│                                             │
│  [Annuler]              [Importer]          │
└─────────────────────────────────────────────┘
```

4. **"Importer"**
5. Mapping des colonnes (si nécessaire)

```
┌─────────────────────────────────────────────┐
│  Mapping des colonnes                       │
├─────────────────────────────────────────────┤
│  Fichier → CRM                              │
│  ────────────────────────────────────────   │
│  Prénom       → [Prénom             ▼]     │
│  Nom          → [Nom                ▼]     │
│  Email        → [Email professionnel▼]     │
│  Tel          → [Téléphone mobile   ▼]     │
│  Société      → [Entreprise         ▼]     │
│  Poste        → [Fonction           ▼]     │
│  Provenance   → [Source             ▼]     │
│                                             │
│  [< Retour]             [Importer]          │
└─────────────────────────────────────────────┘
```

6. Validation et import

```
┌─────────────────────────────────────────────┐
│  Import en cours...                         │
├─────────────────────────────────────────────┤
│  ████████████████████░░░░░░  75%            │
│                                             │
│  Traitement: 150/200 lignes                 │
│                                             │
│  ✓ 140 contacts créés                       │
│  ⚠️  5 entreprises créées automatiquement   │
│  ⚠️  3 lignes ignorées (email invalide)     │
│  ✗ 2 erreurs (doublons)                     │
│                                             │
│  [Voir le rapport détaillé]                 │
└─────────────────────────────────────────────┘
```

**API** :
```bash
POST /contact/import
Content-Type: multipart/form-data

file=@contacts.csv
```

**Gestion des doublons** :
- Détection par email
- Option : Ignorer ou mettre à jour
- Rapport détaillé des doublons

#### Télécharger un modèle

Pour faciliter votre import :
1. **"Télécharger modèle"**
2. Fichier CSV pré-formaté avec colonnes standards
3. Remplissez avec vos données
4. Importez

### 13.2 Importer des entreprises

Processus identique aux contacts.

**Endpoint API** :
```bash
POST /company/import
Content-Type: multipart/form-data

file=@companies.csv
```

**Colonnes typiques** :
```csv
Nom,Secteur,Site web,Email,Téléphone,Adresse,CA,Effectif
ACME Corp,Technologie,https://acme.com,contact@acme.com,+33123456789,Paris,5M€,50
XYZ Ltd,Services,https://xyz.com,info@xyz.com,+33198765432,Lyon,2M€,25
```

### 13.3 Exporter toutes les données

Pour sauvegarder ou migrer vos données :

1. Menu **"Configuration"** > **"Export"** (admin)
2. **"Exporter toutes les données"**
3. Sélection des entités :

```
┌─────────────────────────────────────────────┐
│  Export complet                             │
├─────────────────────────────────────────────┤
│  Sélectionnez les données à exporter        │
│                                             │
│  [☑] Contacts (150)                         │
│  [☑] Entreprises (75)                       │
│  [☑] Affaires (45)                          │
│  [☑] Activités (230)                        │
│  [☐] Pipelines                              │
│  [☐] Tags                                   │
│                                             │
│  Format: [ZIP avec CSV           ▼]        │
│                                             │
│  [Annuler]              [Exporter]          │
└─────────────────────────────────────────────┘
```

4. **"Exporter"**
5. Téléchargement du fichier ZIP

**Contenu du ZIP** :
```
export_crm_20251029.zip
├── contacts.csv
├── companies.csv
├── deals.csv
├── activities.csv
├── README.txt
└── metadata.json
```

**API** :
```bash
GET /export-full

Response:
Content-Type: application/zip
Content-Disposition: attachment; filename="export_crm.zip"
```

### 13.4 Bonnes pratiques import/export

**Avant l'import** :
- Nettoyez vos données (doublons, formats)
- Testez avec un petit échantillon
- Sauvegardez votre base actuelle

**Format des données** :
- **Téléphones** : Format international (+33...)
- **Emails** : Validation automatique
- **Dates** : Format ISO (YYYY-MM-DD)

**Après l'import** :
- Vérifiez les rapports d'erreur
- Contrôlez les entreprises auto-créées
- Complétez les informations manquantes

---

# PARTIE 3 - PROPOSITION D'INTERFACE

## 14. Dashboard principal

### 14.1 Objectifs du Dashboard

Le tableau de bord est la page d'accueil après connexion. Il doit fournir :
- **Vue d'ensemble** de l'activité commerciale
- **Alertes** sur actions urgentes
- **KPIs** (indicateurs clés)
- **Accès rapides** aux fonctions principales

### 14.2 Maquette proposée

```
┌────────────────────────────────────────────────────────────────────────┐
│  🏢 CRM Enterprise      [🔍 Recherche globale...]       👤 John Doe ▼  │
├────────────────────────────────────────────────────────────────────────┤
│  📊 Dashboard  │ 👥 Contacts  │ 🏢 Entreprises  │ 💰 Affaires  │ 📅     │
├────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  Bonjour John,                                  Mercredi 15 Oct 2025   │
│  Vous avez 5 activités aujourd'hui et 12 affaires en négociation       │
│                                                                         │
│  ┌─────────────────────────────────────────────────────────────────┐  │
│  │  INDICATEURS CLÉS                                               │  │
│  ├─────────────────────────────────────────────────────────────────┤  │
│  │  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐          │  │
│  │  │ Contacts     │  │ Affaires     │  │ CA prévisionnel│         │  │
│  │  │  150         │  │ en cours: 45 │  │  this month   │         │  │
│  │  │  +12 ce mois │  │ gagnées: 8   │  │  250K€        │         │  │
│  │  └──────────────┘  └──────────────┘  └──────────────┘          │  │
│  │                                                                 │  │
│  │  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐          │  │
│  │  │ Taux de      │  │ Délai moyen  │  │ Activités    │         │  │
│  │  │ conversion   │  │ closing      │  │ en retard    │         │  │
│  │  │  45% ↗       │  │  32 jours    │  │  3 ⚠️         │         │  │
│  │  └──────────────┘  └──────────────┘  └──────────────┘          │  │
│  └─────────────────────────────────────────────────────────────────┘  │
│                                                                         │
│  ┌──────────────────────────────┐  ┌────────────────────────────────┐ │
│  │  📅 ACTIVITÉS AUJOURD'HUI     │  │  📊 PIPELINE DE VENTE          │ │
│  ├──────────────────────────────┤  ├────────────────────────────────┤ │
│  │                              │  │                                 │ │
│  │  ⏰ 10:00 - 11:30            │  │  ┌─────────────────────────┐  │ │
│  │  🗓️ Démo CRM ACME Corp       │  │  │ 📊 Graphique funnel     │  │ │
│  │  👤 John Doe                 │  │  │                         │  │ │
│  │  📍 Bureaux client           │  │  │    Qualification: 15    │  │ │
│  │  [Démarrer]                  │  │  │    Proposition: 12      │  │ │
│  │                              │  │  │    Négociation: 8       │  │ │
│  │  ⏰ 14:00 - 14:30            │  │  │    Contrat: 5           │  │ │
│  │  📞 Suivi XYZ Ltd            │  │  │    Gagné: 3             │  │ │
│  │  👤 Jane Smith               │  │  └─────────────────────────┘  │ │
│  │  [Préparer]                  │  │                                 │ │
│  │                              │  │  Valeur totale: 450K€           │ │
│  │  ⏰ 16:00                     │  │  [Voir pipeline complet]        │ │
│  │  📧 Envoi devis TechCorp     │  │                                 │ │
│  │  [Rédiger]                   │  │                                 │ │
│  │                              │  │                                 │ │
│  │  [Voir toutes (5)]           │  │                                 │ │
│  └──────────────────────────────┘  └────────────────────────────────┘ │
│                                                                         │
│  ┌────────────────────────────────────────────────────────────────┐   │
│  │  💰 DERNIÈRES AFFAIRES                                         │   │
│  ├────────────────────────────────────────────────────────────────┤   │
│  │  Affaire              Entreprise     Étape          Montant    │   │
│  │  ──────────────────────────────────────────────────────────    │   │
│  │  🔵 Vente CRM         ACME Corp      Proposition    50K€       │   │
│  │  🟢 Migration Cloud   XYZ Ltd        Négociation   150K€       │   │
│  │  🟠 Support Premium   TechCorp       Contrat        10K€       │   │
│  │  🔴 API Integration   GlobalCo       Qualification  30K€       │   │
│  │  🔵 Formation         StartupX       Proposition     5K€       │   │
│  │                                                                │   │
│  │  [Voir toutes les affaires]                                    │   │
│  └────────────────────────────────────────────────────────────────┘   │
│                                                                         │
│  ┌──────────────────────────────┐  ┌────────────────────────────────┐ │
│  │  ⚠️  ACTIONS REQUISES         │  │  📈 PERFORMANCES DU MOIS       │ │
│  ├──────────────────────────────┤  ├────────────────────────────────┤ │
│  │                              │  │                                 │ │
│  │  🔴 3 activités en retard    │  │  Objectif: 300K€               │ │
│  │     [Voir]                   │  │  Réalisé: 180K€ (60%)          │ │
│  │                              │  │                                 │ │
│  │  🟠 5 affaires sans activité │  │  ████████░░░░░░  60%           │ │
│  │     depuis 7j+ [Relancer]    │  │                                 │ │
│  │                              │  │  Affaires gagnées: 8            │ │
│  │  🟡 12 contacts à qualifier  │  │  Affaires perdues: 3            │ │
│  │     [Qualifier]              │  │  Taux de conversion: 73%        │ │
│  │                              │  │                                 │ │
│  └──────────────────────────────┘  └────────────────────────────────┘ │
│                                                                         │
└────────────────────────────────────────────────────────────────────────┘
```

### 14.3 Composants React/Vue.js suggérés

#### KPI Cards (Cartes d'indicateurs)
```jsx
// React
<KPICard
  title="Contacts"
  value={150}
  change="+12 ce mois"
  trend="up"
  icon="users"
/>

// Vue
<kpi-card
  :title="'Contacts'"
  :value="150"
  :change="'+12 ce mois'"
  :trend="'up'"
  :icon="'users'"
/>
```

#### Activités du jour
```jsx
// React
<TodayActivities
  activities={todayActivities}
  onStart={(activity) => handleStartActivity(activity)}
/>

// Vue
<today-activities
  :activities="todayActivities"
  @start="handleStartActivity"
/>
```

#### Pipeline Funnel Chart
```jsx
// React (avec recharts)
<FunnelChart data={pipelineData} />

// Vue (avec chart.js)
<funnel-chart :data="pipelineData" />
```

### 14.4 Interactions utilisateur

**Actions rapides** :
- Clic sur une activité → Fiche activité
- "Démarrer" → Marque comme en cours + ouvre fiche
- Clic sur une affaire → Fiche affaire
- Graphiques interactifs → Filtrage/drill-down

**Personnalisation** :
- Widgets déplaçables (drag & drop)
- Masquer/afficher des sections
- Choix des KPIs affichés

---

## 15. Interface Contacts

### 15.1 Liste des contacts

```
┌────────────────────────────────────────────────────────────────────────┐
│  👥 Contacts (150)                                                     │
│  [+ Nouveau contact]  [Import CSV]  [Export]  [Filtres ▼]             │
├────────────────────────────────────────────────────────────────────────┤
│  🔍 [Rechercher par nom, email, téléphone...]                          │
│                                                                         │
│  Filtres actifs: [VIP ✕] [Technologie ✕]  [Réinitialiser]             │
├────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  Tri: [Nom ▼]  Vue: [☰ Liste] [▦ Grille] [📋 Compact]                 │
│                                                                         │
│  ┌──────────────────────────────────────────────────────────────────┐ │
│  │ Photo  Nom           Email              Entreprise    Tags       │ │
│  ├──────────────────────────────────────────────────────────────────┤ │
│  │ [👤]  Jane Smith    jane@xyz.com        XYZ Ltd       Lead       │ │
│  │       CEO           +33 6 98 76 54 32   2 affaires    Tech       │ │
│  │                     [📞] [✉️] [👁️]  [...]                          │ │
│  ├──────────────────────────────────────────────────────────────────┤ │
│  │ [👤]  Bob Martin    bob@tech.io         TechCorp      Client     │ │
│  │       CTO           +33 6 54 32 10 98   5 affaires    VIP        │ │
│  │                     [📞] [✉️] [👁️]  [...]                          │ │
│  ├──────────────────────────────────────────────────────────────────┤ │
│  │ [👤]  Alice Cooper  alice@global.com    GlobalCo      Prospect   │ │
│  │       VP Sales      +33 6 11 22 33 44   1 affaire                │ │
│  │                     [📞] [✉️] [👁️]  [...]                          │ │
│  ├──────────────────────────────────────────────────────────────────┤ │
│  │ ... (22 autres contacts)                                         │ │
│  └──────────────────────────────────────────────────────────────────┘ │
│                                                                         │
│  Affichage 1-25 sur 150                      [←] 1 2 3 4 5 6 [→]      │
└────────────────────────────────────────────────────────────────────────┘
```

**Actions rapides sur chaque ligne** :
- **📞** : Lancer un appel (intégration téléphonie)
- **✉️** : Envoyer un email
- **👁️** : Voir la fiche détaillée
- **...** : Menu contextuel (Modifier, Supprimer, Fusionner, Associer)

### 15.2 Panneau de filtres avancés

```
┌──────────────────────────────────┐
│  Filtres avancés                 │
├──────────────────────────────────┤
│  Entreprise                      │
│  [Toutes              ▼]        │
│                                  │
│  Tags                            │
│  [☐ VIP                        ] │
│  [☐ Prospect                   ] │
│  [☐ Client                     ] │
│  [☐ Lead                       ] │
│  [☐ Tech                       ] │
│                                  │
│  Source                          │
│  [☐ Website                    ] │
│  [☐ Salon                      ] │
│  [☐ Référence                  ] │
│  [☐ Cold call                  ] │
│                                  │
│  Responsable                     │
│  [Tous                ▼]        │
│                                  │
│  Date de création                │
│  Du [01/01/2025] au [31/10/2025]│
│                                  │
│  Avec affaires en cours          │
│  [☑ Oui  ☐ Non                 ] │
│                                  │
│  [Réinitialiser] [Appliquer]     │
└──────────────────────────────────┘
```

### 15.3 Fiche contact détaillée

```
┌────────────────────────────────────────────────────────────────────────┐
│  ← Retour aux contacts                                                 │
│                                                                         │
│  ┌──────────┐  👤 John DOE                      [Modifier] [Actions ▼]│
│  │          │  Directeur IT                                            │
│  │  Photo   │  🏢 ACME Corporation                                     │
│  │          │  🏷️ VIP • Prospect • Tech                                 │
│  └──────────┘  📅 Créé le 08/10/2025 par commercial@vous.com           │
├────────────────────────────────────────────────────────────────────────┤
│  [Informations] [Affaires (2)] [Activités (5)] [Notes (3)] [Fichiers] │
├────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  📧 EMAILS                                                              │
│  ─────────────────────────────────────────────────────────────────────│
│  • john.doe@acme.com (Professionnel) ✉️  [Principal]                   │
│  • john.doe.perso@gmail.com (Personnel) ✉️                              │
│  [+ Ajouter email]                                                      │
│                                                                         │
│  📞 TÉLÉPHONES                                                          │
│  ─────────────────────────────────────────────────────────────────────│
│  • +33 6 12 34 56 78 (Mobile) 📞  [Principal]                          │
│  • +33 1 23 45 67 89 (Bureau) 📞                                       │
│  [+ Ajouter téléphone]                                                  │
│                                                                         │
│  💼 INFORMATIONS PROFESSIONNELLES                                       │
│  ─────────────────────────────────────────────────────────────────────│
│  Fonction:       Directeur IT                                          │
│  Entreprise:     ACME Corporation [Voir fiche]                         │
│  Département:    Technologies                                          │
│  Ancienneté:     5 ans                                                 │
│                                                                         │
│  🎯 CRM                                                                 │
│  ─────────────────────────────────────────────────────────────────────│
│  Source:         Website                                               │
│  Responsable:    commercial@vous.com                                   │
│  Statut:         Prospect actif                                        │
│                                                                         │
│  📍 LOCALISATION                                                        │
│  ─────────────────────────────────────────────────────────────────────│
│  123 Avenue des Champs-Élysées                                         │
│  75008 Paris, France                                                   │
│  [Voir sur la carte]                                                    │
│                                                                         │
└────────────────────────────────────────────────────────────────────────┘
```

### 15.4 Onglet "Affaires"

```
┌────────────────────────────────────────────────────────────────────────┐
│  💰 AFFAIRES (2)                                [+ Nouvelle affaire]   │
├────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  ┌──────────────────────────────────────────────────────────────────┐ │
│  │ 🔵 Vente CRM Enterprise                              50 000 €    │ │
│  │    Étape: Proposition (50%)                                      │ │
│  │    Créée le 01/10/2025 • Modifiée le 12/10/2025                │ │
│  │    Responsable: commercial@vous.com                              │ │
│  │    Prochaine activité: 15/10 10:00 - Démonstration produit      │ │
│  │    [Voir détails]                                                │ │
│  └──────────────────────────────────────────────────────────────────┘ │
│                                                                         │
│  ┌──────────────────────────────────────────────────────────────────┐ │
│  │ 🟢 Formation équipe                                   5 000 €    │ │
│  │    Étape: Qualification (10%)                                    │ │
│  │    Créée le 05/10/2025                                          │ │
│  │    Responsable: formation@vous.com                               │ │
│  │    Aucune activité planifiée                                     │ │
│  │    [Voir détails]                                                │ │
│  └──────────────────────────────────────────────────────────────────┘ │
│                                                                         │
│  ✓ 1 affaire gagnée (total: 25K€)                                      │
│  ✗ 0 affaire perdue                                                    │
│                                                                         │
└────────────────────────────────────────────────────────────────────────┘
```

### 15.5 Formulaire d'édition contact

```
┌────────────────────────────────────────────────────────────────────────┐
│  Modifier le contact                                   [Enregistrer]   │
├────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  INFORMATIONS PRINCIPALES                                              │
│  ─────────────────────────────────────────────────────────────────────│
│  Prénom *         [John                              ]                 │
│  Nom *            [DOE                               ]                 │
│  Fonction         [Directeur IT                      ]                 │
│                                                                         │
│  COORDONNÉES                                                            │
│  ─────────────────────────────────────────────────────────────────────│
│  ┌───────────────────────────────────────────────────────────────┐    │
│  │ Email          Type          Principal     Actions            │    │
│  │ john@acme.com  Professionnel  ☑            [Modifier] [✕]    │    │
│  │ john@gmail.com Personnel      ☐            [Modifier] [✕]    │    │
│  │ [+ Ajouter email]                                             │    │
│  └───────────────────────────────────────────────────────────────┘    │
│                                                                         │
│  ┌───────────────────────────────────────────────────────────────┐    │
│  │ Téléphone          Type      Principal     Actions            │    │
│  │ +33 6 12 34 56 78  Mobile    ☑            [Modifier] [✕]    │    │
│  │ +33 1 23 45 67 89  Bureau    ☐            [Modifier] [✕]    │    │
│  │ [+ Ajouter téléphone]                                         │    │
│  └───────────────────────────────────────────────────────────────┘    │
│                                                                         │
│  ENTREPRISE & CRM                                                       │
│  ─────────────────────────────────────────────────────────────────────│
│  Entreprise       [🔍 ACME Corporation                      ▼]        │
│  Source           [Website                                  ▼]        │
│  Responsable      [commercial@vous.com                      ▼]        │
│                                                                         │
│  TAGS                                                                   │
│  ─────────────────────────────────────────────────────────────────────│
│  [VIP ✕] [Prospect ✕] [Tech ✕]                                        │
│  [+ Ajouter tag]                                                        │
│                                                                         │
│  PHOTO                                                                  │
│  ─────────────────────────────────────────────────────────────────────│
│  ┌──────────┐                                                          │
│  │  Photo   │  [Choisir fichier...] ou Glisser-déposer                │
│  │ actuelle │                                                          │
│  └──────────┘                                                          │
│                                                                         │
│  [Annuler]                                          [Enregistrer]      │
└────────────────────────────────────────────────────────────────────────┘
```

### 15.6 Composants techniques

**Liste de contacts** (React):
```jsx
<ContactList
  contacts={contacts}
  filters={activeFilters}
  onFilterChange={handleFilterChange}
  onContactClick={handleContactClick}
  onQuickAction={(contact, action) => handleQuickAction(contact, action)}
  pagination={{
    page: currentPage,
    limit: 25,
    total: totalContacts
  }}
/>
```

**Fiche contact** (Vue):
```vue
<contact-detail
  :contact="selectedContact"
  :readonly="false"
  @save="handleSave"
  @delete="handleDelete"
  @add-activity="handleAddActivity"
/>
```

---

## 16. Interface Entreprises

### 16.1 Liste des entreprises

Interface similaire aux contacts, adaptée aux entreprises :

```
┌────────────────────────────────────────────────────────────────────────┐
│  🏢 Entreprises (75)                                                   │
│  [+ Nouvelle entreprise]  [Import CSV]  [Export]  [Filtres ▼]         │
├────────────────────────────────────────────────────────────────────────┤
│  🔍 [Rechercher par nom, secteur...]                                   │
│                                                                         │
│  Tri: [CA décroissant ▼]  Vue: [☰ Liste] [▦ Cartes]                   │
│                                                                         │
│  ┌──────────────────────────────────────────────────────────────────┐ │
│  │ Logo  Nom              Secteur      Contacts  CA       Tags      │ │
│  ├──────────────────────────────────────────────────────────────────┤ │
│  │ [🏢]  ACME Corp        Technologie   12       5M€      Client    │ │
│  │       Paris            B2B SaaS      3 affaires         VIP      │ │
│  │       https://acme.com              [👁️]  [...]                  │ │
│  ├──────────────────────────────────────────────────────────────────┤ │
│  │ [🏢]  XYZ Ltd          Services      8        2M€      Lead      │ │
│  │       Lyon             Conseil       1 affaire          Tech     │ │
│  │       https://xyz.com               [👁️]  [...]                  │ │
│  ├──────────────────────────────────────────────────────────────────┤ │
│  │ [🏢]  TechCorp         IT            15       10M€     Client    │ │
│  │       Lille            Cloud         5 affaires         Partner  │ │
│  │       https://tech.io               [👁️]  [...]                  │ │
│  └──────────────────────────────────────────────────────────────────┘ │
│                                                                         │
│  Affichage 1-25 sur 75                       [←] 1 2 3 [→]            │
└────────────────────────────────────────────────────────────────────────┘
```

### 16.2 Fiche entreprise détaillée

```
┌────────────────────────────────────────────────────────────────────────┐
│  ← Retour                                                              │
│                                                                         │
│  ┌──────────┐  🏢 ACME CORPORATION              [Modifier] [Actions ▼]│
│  │   Logo   │  Technologie - B2B SaaS                                 │
│  │          │  🏷️ Client • VIP • Technologie                           │
│  └──────────┘  📅 Créée le 15/03/2024                                  │
├────────────────────────────────────────────────────────────────────────┤
│  [Informations] [Contacts (12)] [Affaires (3)] [Activités] [Fichiers] │
├────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  🌐 INFORMATIONS GÉNÉRALES                                              │
│  ─────────────────────────────────────────────────────────────────────│
│  Site web:        https://acme.com 🔗                                  │
│  Email général:   contact@acme.com ✉️                                   │
│  Téléphone:       +33 1 23 45 67 89 📞                                 │
│                                                                         │
│  📍 ADRESSE                                                             │
│  ─────────────────────────────────────────────────────────────────────│
│  123 Avenue des Champs-Élysées                                         │
│  75008 Paris, France                                                   │
│  [Voir sur la carte]                                                    │
│                                                                         │
│  💼 INFORMATIONS BUSINESS                                               │
│  ─────────────────────────────────────────────────────────────────────│
│  Secteur:           Technologie                                        │
│  Sous-secteur:      B2B SaaS                                           │
│  CA annuel:         5M€                                                │
│  Effectif:          50-100 employés                                    │
│  Année création:    2015                                               │
│                                                                         │
│  🎯 CRM                                                                 │
│  ─────────────────────────────────────────────────────────────────────│
│  Statut:            Client actif                                       │
│  Contacts:          12                                                 │
│  Affaires en cours: 3 (total: 215K€)                                  │
│  Affaires gagnées:  8 (total: 450K€)                                  │
│                                                                         │
└────────────────────────────────────────────────────────────────────────┘
```

### 16.3 Onglet "Contacts de l'entreprise"

```
┌────────────────────────────────────────────────────────────────────────┐
│  👥 CONTACTS (12)                   [+ Nouveau] [+ Associer existant]  │
├────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  Contact principal:                                                     │
│  ┌──────────────────────────────────────────────────────────────────┐ │
│  │ 👤 John DOE - Directeur IT                                       │ │
│  │    📧 john.doe@acme.com  📞 +33 6 12 34 56 78                     │ │
│  │    2 affaires en cours                                            │ │
│  │    [Définir comme principal] [Voir fiche]                         │ │
│  └──────────────────────────────────────────────────────────────────┘ │
│                                                                         │
│  Autres contacts:                                                       │
│  ┌──────────────────────────────────────────────────────────────────┐ │
│  │ 👤 Jane SMITH - CEO                                              │ │
│  │    📧 jane.smith@acme.com  📞 +33 6 98 76 54 32                   │ │
│  │    Décisionnaire • 1 affaire                                      │ │
│  │    [Définir comme principal] [Voir fiche] [Dissocier]            │ │
│  └──────────────────────────────────────────────────────────────────┘ │
│                                                                         │
│  ┌──────────────────────────────────────────────────────────────────┐ │
│  │ 👤 Bob MARTIN - CTO                                              │ │
│  │    📧 bob.martin@acme.com  📞 +33 6 54 32 10 98                   │ │
│  │    Prescripteur technique • 2 affaires                            │ │
│  │    [Définir comme principal] [Voir fiche] [Dissocier]            │ │
│  └──────────────────────────────────────────────────────────────────┘ │
│                                                                         │
│  ... (9 autres contacts)                                                │
│                                                                         │
└────────────────────────────────────────────────────────────────────────┘
```

---

## 17. Interface Pipeline/Affaires

### 17.1 Vue Kanban du pipeline

C'est la vue principale pour gérer les affaires :

```
┌────────────────────────────────────────────────────────────────────────────────────────────────┐
│  💰 Pipeline: Ventes B2B                        [Filtres ▼] [+ Nouvelle affaire]              │
├────────────────────────────────────────────────────────────────────────────────────────────────┤
│  Responsable: [Tous ▼]  Tags: [Tous ▼]  Montant: [Tous ▼]  Période: [Tous ▼]                │
├────────────────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                                 │
│  ┌────────────────┐  ┌────────────────┐  ┌────────────────┐  ┌────────────────┐  ┌──────────┐│
│  │ Qualification  │  │ Proposition    │  │ Négociation    │  │ Contrat        │  │ Gagné    ││
│  │ 10%            │  │ 50%            │  │ 75%            │  │ 90%            │  │ 100%     ││
│  │ ────────────── │  │ ────────────── │  │ ────────────── │  │ ────────────── │  │ ──────── ││
│  │ 15 affaires    │  │ 12 affaires    │  │ 8 affaires     │  │ 5 affaires     │  │ 3/mois   ││
│  │ 375K€          │  │ 600K€          │  │ 400K€          │  │ 450K€          │  │ 175K€    ││
│  │                │  │                │  │                │  │                │  │          ││
│  │ ┌────────────┐ │  │ ┌────────────┐ │  │ ┌────────────┐ │  │ ┌────────────┐ │  │          ││
│  │ │ACME Corp   │ │  │ │XYZ Ltd     │ │  │ │TechCorp    │ │  │ │GlobalCo    │ │  │          ││
│  │ │CRM         │ │  │ │Cloud       │ │  │ │Support     │ │  │ │Enterprise  │ │  │          ││
│  │ │50K€        │ │  │ │Migration   │ │  │ │Premium     │ │  │ │License     │ │  │          ││
│  │ │John Doe    │ │  │ │150K€       │ │  │ │10K€        │ │  │ │200K€       │ │  │          ││
│  │ │            │ │  │ │Jane Smith  │ │  │ │Bob Martin  │ │  │ │Alice C.    │ │  │          ││
│  │ │ [...]      │ │  │ │ [...]      │ │  │ │ [...]      │ │  │ │ [...]      │ │  │          ││
│  │ └────────────┘ │  │ └────────────┘ │  │ └────────────┘ │  │ └────────────┘ │  │          ││
│  │                │  │                │  │                │  │                │  │          ││
│  │ ┌────────────┐ │  │ ┌────────────┐ │  │ ┌────────────┐ │  │                │  │          ││
│  │ │StartupX    │ │  │ │BizCorp     │ │  │ │ABC Inc     │ │  │                │  │          ││
│  │ │SaaS        │ │  │ │API         │ │  │ │Custom Dev  │ │  │                │  │          ││
│  │ │5K€         │ │  │ │30K€        │ │  │ │75K€        │ │  │                │  │          ││
│  │ │Bob M.      │ │  │ │John Doe    │ │  │ │Jane Smith  │ │  │                │  │          ││
│  │ │ [...]      │ │  │ │ [...]      │ │  │ │ [...]      │ │  │                │  │          ││
│  │ └────────────┘ │  │ └────────────┘ │  │ └────────────┘ │  │                │  │          ││
│  │                │  │                │  │                │  │                │  │          ││
│  │ ...            │  │ ...            │  │ ...            │  │ ...            │  │          ││
│  │                │  │                │  │                │  │                │  │          ││
│  │ [+ Créer]      │  │ [+ Créer]      │  │ [+ Créer]      │  │ [+ Créer]      │  │          ││
│  └────────────────┘  └────────────────┘  └────────────────┘  └────────────────┘  └──────────┘│
│                                                                                                 │
│  📊 Statistiques: Taux de conversion Qual→Prop: 80% • Prop→Négo: 67% • Négo→Contrat: 63%     │
└────────────────────────────────────────────────────────────────────────────────────────────────┘
```

**Interactions drag & drop** :
- Saisissez une carte d'affaire
- Glissez vers une autre colonne
- Relâchez → L'affaire change d'étape automatiquement
- Animation fluide + confirmation visuelle

### 17.2 Carte d'affaire (détails)

Chaque carte contient :

```
┌────────────────┐
│ ACME Corp      │ ← Entreprise
│ CRM Enterprise │ ← Objet de l'affaire
│ 50K€           │ ← Montant
│ John Doe       │ ← Contact principal
│ ⏰ 15/10 10:00 │ ← Prochaine activité
│ 🏷️ Cloud • VIP │ ← Tags
│ [...]          │ ← Menu actions
└────────────────┘
```

**Couleurs** :
- Bordure selon priorité (VIP = rouge, Normal = bleu, etc.)
- Pastille selon ancienneté dans l'étape :
  - 🟢 < 7 jours
  - 🟠 7-14 jours
  - 🔴 > 14 jours (attention, risque de stagner)

### 17.3 Détail d'une affaire (modale ou page)

```
┌────────────────────────────────────────────────────────────────────────┐
│  💰 Vente CRM Enterprise - 50 000 €                     [Fermer ✕]     │
├────────────────────────────────────────────────────────────────────────┤
│  [Détails] [Timeline] [Activités] [Participants] [Fichiers] [Notes]   │
├────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  INFORMATIONS PRINCIPALES                                              │
│  ─────────────────────────────────────────────────────────────────────│
│  Objet:            Vente CRM Enterprise                                │
│  Entreprise:       🏢 ACME Corporation  [Voir fiche]                   │
│  Contact:          👤 John DOE (Directeur IT)  [Voir fiche]            │
│  Responsable:      commercial@vous.com                                 │
│                                                                         │
│  PIPELINE                                                               │
│  ─────────────────────────────────────────────────────────────────────│
│  Pipeline:         Ventes B2B                                          │
│  Étape actuelle:   Proposition (50%)                                   │
│                                                                         │
│  Progression:      [▓▓▓▓▓░░░░░]  50%                                  │
│                                                                         │
│  [◄ Qualification]  [Proposition]  [Négociation ►]                     │
│                                                                         │
│  Depuis:           12/10/2025 (3 jours)                                │
│  ⚠️  Moyenne étape: 7 jours (reste 4j dans la cible)                   │
│                                                                         │
│  MONTANT & PRODUITS                                                     │
│  ─────────────────────────────────────────────────────────────────────│
│  Montant:          50 000 € (prévisionnel)                             │
│  Probabilité:      50% (selon étape)                                   │
│  CA pondéré:       25 000 €                                            │
│                                                                         │
│  Produits/Services:                                                     │
│  • CRM_ENTERPRISE - Licence annuelle - 40K€                            │
│  • SUPPORT_PREMIUM - Support 24/7 - 8K€                                │
│  • TRAINING - Formation 5 jours - 2K€                                  │
│                                                                         │
│  PARTICIPANTS                                                           │
│  ─────────────────────────────────────────────────────────────────────│
│  👤 John DOE - Directeur IT (Contact principal)                        │
│  👤 Jane SMITH - CEO (Décisionnaire)                                   │
│  👤 Bob MARTIN - CTO (Prescripteur technique)                          │
│  [+ Ajouter participant]                                                │
│                                                                         │
│  TAGS                                                                   │
│  ─────────────────────────────────────────────────────────────────────│
│  [Cloud ✕] [Enterprise ✕] [VIP ✕]                                     │
│                                                                         │
│  ACTIONS RAPIDES                                                        │
│  ─────────────────────────────────────────────────────────────────────│
│  [📅 Planifier activité]  [📝 Ajouter note]  [📎 Joindre fichier]      │
│  [✅ Marquer gagnée]  [❌ Marquer perdue]                               │
│                                                                         │
│  [Modifier l'affaire]                                                   │
└────────────────────────────────────────────────────────────────────────┘
```

### 17.4 Timeline de l'affaire

```
┌────────────────────────────────────────────────────────────────────────┐
│  TIMELINE                                                              │
├────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  ● 12/10/2025 14:30 - Changement d'étape                              │
│    De "Qualification" vers "Proposition"                              │
│    Par: commercial@vous.com                                            │
│                                                                         │
│  ● 12/10/2025 11:00 - Activité effectuée                              │
│    Appel de qualification réalisé                                     │
│    Note: "Client intéressé, budget confirmé 50K€"                     │
│                                                                         │
│  ● 10/10/2025 - Note ajoutée                                          │
│    "Décisionnaire identifié: Jane Smith (CEO)"                        │
│    Par: commercial@vous.com                                            │
│                                                                         │
│  ● 08/10/2025 - Participant ajouté                                     │
│    Bob MARTIN (CTO) ajouté comme prescripteur                        │
│                                                                         │
│  ● 01/10/2025 09:15 - Affaire créée                                   │
│    Par: commercial@vous.com                                            │
│    Source: Contact lors du Salon TechParis 2025                       │
│                                                                         │
└────────────────────────────────────────────────────────────────────────┘
```

### 17.5 Composants techniques

**Kanban Board** (React):
```jsx
import { DndContext, DragOverlay } from '@dnd-kit/core';

<KanbanBoard
  pipeline={selectedPipeline}
  steps={pipelineSteps}
  deals={deals}
  onDealMove={(dealId, newStepId) => handleDealMove(dealId, newStepId)}
  onDealClick={(deal) => setSelectedDeal(deal)}
  onCreateDeal={(stepId) => handleCreateDeal(stepId)}
/>
```

**Deal Card** (Vue):
```vue
<deal-card
  :deal="deal"
  :draggable="true"
  @click="handleClick"
  @menu="showContextMenu"
/>
```

---

## 18. Interface Activités

### 18.1 Vue calendrier

```
┌────────────────────────────────────────────────────────────────────────┐
│  📅 Activités                                   [+ Nouvelle activité]  │
│  [Calendrier] [Liste] [Timeline]                                      │
├────────────────────────────────────────────────────────────────────────┤
│  ◄ Octobre 2025 ►                      [Jour] [Semaine] [Mois]        │
├────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  Lundi 13   │ Mardi 14  │ Mercre 15 │ Jeudi 16  │ Vendredi 17         │
│             │           │           │           │                     │
│  08:00      │ 08:00     │ 08:00     │ 08:00     │ 08:00               │
│             │           │           │           │                     │
│  09:00      │ 09:00     │ 09:00     │ 09:00     │ 09:00               │
│             │           │           │ ┌───────┐ │                     │
│  10:00      │ 10:00     │ ┌───────┐ │ │Qual.  │ │ 10:00               │
│             │           │ │Démo   │ │ │Prospect│ │                     │
│  11:00      │ 11:00     │ │ACME   │ │ └───────┘ │ 11:00               │
│             │           │ │Corp   │ │           │                     │
│  12:00      │ 12:00     │ └───────┘ │ 12:00     │ 12:00               │
│             │           │           │           │                     │
│  13:00      │ 13:00     │ 13:00     │ 13:00     │ 13:00               │
│             │           │           │           │                     │
│  14:00      │ 14:00     │ 14:00     │ 14:00     │ ┌───────┐           │
│             │           │ ┌───────┐ │           │ │Présen.│           │
│  15:00      │ 15:00     │ │Suivi  │ │ 15:00     │ │GlobalCo│          │
│             │           │ │XYZ    │ │           │ └───────┘           │
│  16:00      │ 16:00     │ └───────┘ │ 16:00     │ 16:00               │
│             │           │           │           │                     │
│  17:00      │ 17:00     │ 17:00     │ 17:00     │ 17:00               │
│             │           │           │           │                     │
│  18:00      │ 18:00     │ 18:00     │ 18:00     │ 18:00               │
└────────────────────────────────────────────────────────────────────────┘
```

**Interactions** :
- **Clic** sur activité : Voir détails
- **Double-clic** sur plage vide : Créer activité
- **Glisser-déposer** : Déplacer activité
- **Molette** : Scroll vertical (heures)

### 18.2 Vue liste groupée

```
┌────────────────────────────────────────────────────────────────────────┐
│  📋 Liste des activités                         [Filtres ▼]            │
├────────────────────────────────────────────────────────────────────────┤
│  Type: [Tous ▼]  Responsable: [Moi ▼]  Affaire: [Toutes ▼]           │
│  Statut: [☐ À faire  ☑ Effectuées]                                    │
├────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  🔴 EN RETARD (3)                                                      │
│  ─────────────────────────────────────────────────────────────────────│
│  📞 11/10/2025 14:00 - Appel de relance - StartupX                    │
│      Bob Martin • Affaire SaaS 5K€                                    │
│      [Marquer effectuée] [Reprogrammer]                               │
│                                                                         │
│  ✉️  12/10/2025 - Envoi devis - BizCorp                               │
│      John Doe • Affaire API 30K€                                      │
│      [Marquer effectuée] [Reprogrammer]                               │
│                                                                         │
│  📅 AUJOURD'HUI - Mercredi 15 octobre 2025 (5)                         │
│  ─────────────────────────────────────────────────────────────────────│
│  🗓️ 10:00-11:30 - Démonstration produit - ACME Corp                   │
│      John Doe • Affaire CRM 50K€                                      │
│      📍 Bureaux client • 🔔 Notification à 09:30                       │
│      [Marquer effectuée] [Modifier]                                    │
│                                                                         │
│  📞 14:00-14:30 - Appel de suivi - XYZ Ltd                            │
│      Jane Smith • Affaire Migration Cloud 150K€                       │
│      🔔 Notification à 13:45                                           │
│      [Marquer effectuée] [Modifier]                                    │
│                                                                         │
│  ... (3 autres activités)                                              │
│                                                                         │
│  📅 DEMAIN - Jeudi 16 octobre (4)                                      │
│  ─────────────────────────────────────────────────────────────────────│
│  📞 09:00-10:00 - Qualification prospect                               │
│  🗓️ 15:00-16:00 - Rendez-vous commercial                              │
│  ...                                                                    │
│                                                                         │
│  📅 CETTE SEMAINE (12)                                                 │
│  [Afficher]                                                             │
│                                                                         │
│  📅 PLUS TARD (25)                                                     │
│  [Afficher]                                                             │
│                                                                         │
│  ✅ EFFECTUÉES CE MOIS (45)                                            │
│  [Afficher l'historique]                                               │
│                                                                         │
└────────────────────────────────────────────────────────────────────────┘
```

### 18.3 Détail d'une activité

```
┌────────────────────────────────────────────────────────────────────────┐
│  🗓️ Démonstration produit                                 [Fermer ✕]   │
├────────────────────────────────────────────────────────────────────────┤
│  [Détails] [Compte-rendu] [Fichiers liés]                             │
├────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  TYPE & DATES                                                           │
│  ─────────────────────────────────────────────────────────────────────│
│  Type:             🗓️ Meeting (Rendez-vous)                             │
│  Date début:       Mercredi 15 octobre 2025, 10:00                    │
│  Date fin:         Mercredi 15 octobre 2025, 11:30                    │
│  Durée:            1h30                                                │
│  Lieu:             Bureaux client - ACME Corp                          │
│                                                                         │
│  LIÉE À                                                                 │
│  ─────────────────────────────────────────────────────────────────────│
│  Contact:          👤 John DOE (Directeur IT)  [Voir]                  │
│  Entreprise:       🏢 ACME Corporation  [Voir]                         │
│  Affaire:          💰 Vente CRM Enterprise - 50K€  [Voir]              │
│                                                                         │
│  RESPONSABLES                                                           │
│  ─────────────────────────────────────────────────────────────────────│
│  • commercial@vous.com (organisateur)                                  │
│  • manager@vous.com                                                    │
│  [+ Ajouter responsable]                                                │
│                                                                         │
│  DESCRIPTION                                                            │
│  ─────────────────────────────────────────────────────────────────────│
│  Démonstration complète de la solution CRM avec focus sur :           │
│  • Module de gestion des contacts et entreprises                      │
│  • Pipeline de vente personnalisable                                  │
│  • Reporting et tableaux de bord                                      │
│  • Intégrations API                                                    │
│                                                                         │
│  Participants côté client :                                            │
│  • John DOE - Directeur IT                                            │
│  • Jane SMITH - CEO (décisionnaire)                                   │
│  • Bob MARTIN - CTO                                                    │
│                                                                         │
│  NOTIFICATION                                                           │
│  ─────────────────────────────────────────────────────────────────────│
│  ☑ Me notifier 30 minutes avant (09:30)                               │
│  ☑ Notifier les autres responsables                                   │
│                                                                         │
│  STATUT                                                                 │
│  ─────────────────────────────────────────────────────────────────────│
│  ☐ Activité effectuée                                                  │
│                                                                         │
│  ACTIONS                                                                │
│  ─────────────────────────────────────────────────────────────────────│
│  [Modifier]  [Reprogrammer]  [Marquer effectuée]  [Supprimer]         │
│                                                                         │
└────────────────────────────────────────────────────────────────────────┘
```

### 18.4 Compte-rendu après activité

Une fois l'activité effectuée, ajouter un compte-rendu :

```
┌────────────────────────────────────────────────────────────────────────┐
│  ✅ COMPTE-RENDU                                                        │
├────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  Résultat:       [Positif                            ▼]               │
│                  (Positif / Neutre / Négatif / À relancer)            │
│                                                                         │
│  Notes:                                                                 │
│  ┌──────────────────────────────────────────────────────────────────┐ │
│  │ Démonstration très réussie !                                     │ │
│  │                                                                  │ │
│  │ Points positifs :                                                │ │
│  │ - Équipe très intéressée par le module reporting                │ │
│  │ - CEO convaincue de la valeur ajoutée                           │ │
│  │ - CTO valide la compatibilité technique                         │ │
│  │                                                                  │ │
│  │ Prochaines étapes :                                              │ │
│  │ - Envoi proposition commerciale détaillée (avant 18/10)         │ │
│  │ - Programmation POC sur leurs données (semaine du 21/10)        │ │
│  │ - Décision finale attendue fin octobre                          │ │
│  └──────────────────────────────────────────────────────────────────┘ │
│                                                                         │
│  Prochaine action:                                                      │
│  [📧 Envoi proposition] programmé pour le [17/10/2025]                 │
│  Responsable: [commercial@vous.com              ▼]                    │
│                                                                         │
│  Faire progresser l'affaire:                                           │
│  ☑ Passer à l'étape "Négociation"                                     │
│  Probabilité de succès: [75] %                                         │
│                                                                         │
│  [Enregistrer le compte-rendu]                                          │
│                                                                         │
└────────────────────────────────────────────────────────────────────────┘
```

---

## 19. Interface Configuration

### 19.1 Tableau de bord configuration (Admin)

```
┌────────────────────────────────────────────────────────────────────────┐
│  ⚙️ Configuration                                                       │
├────────────────────────────────────────────────────────────────────────┤
│  [Pipelines] [Étapes] [Tags] [Types] [Propriétés] [Utilisateurs]      │
├────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  ┌──────────────────────────────┐  ┌────────────────────────────────┐ │
│  │  📊 PIPELINES                │  │  🏷️ TAGS                        │ │
│  ├──────────────────────────────┤  ├────────────────────────────────┤ │
│  │                              │  │                                 │ │
│  │  • Ventes B2B (5 étapes)     │  │  • VIP (12 utilisations)       │ │
│  │  • Ventes B2C (4 étapes)     │  │  • Prospect (45)               │ │
│  │  • Partenariats (6 étapes)   │  │  • Client (78)                 │ │
│  │                              │  │  • Lead (34)                   │ │
│  │  [+ Nouveau pipeline]         │  │  • Technologie (23)            │ │
│  │  [Gérer]                      │  │                                 │ │
│  │                              │  │  [+ Nouveau tag]                │ │
│  │                              │  │  [Gérer]                        │ │
│  └──────────────────────────────┘  └────────────────────────────────┘ │
│                                                                         │
│  ┌──────────────────────────────┐  ┌────────────────────────────────┐ │
│  │  📝 TYPES D'OBJETS            │  │  🔧 PROPRIÉTÉS PERSONNALISÉES  │ │
│  ├──────────────────────────────┤  ├────────────────────────────────┤ │
│  │                              │  │                                 │ │
│  │  • Contact (12 propriétés)   │  │  Contact:                      │ │
│  │  • Entreprise (8 propriétés) │  │  • Prénom (text)               │ │
│  │                              │  │  • Nom (text)                  │ │
│  │  [Gérer les types]            │  │  • Fonction (text)             │ │
│  │                              │  │  ... (+9)                      │ │
│  │                              │  │                                 │ │
│  │                              │  │  [+ Nouvelle propriété]         │ │
│  │                              │  │  [Gérer]                        │ │
│  └──────────────────────────────┘  └────────────────────────────────┘ │
│                                                                         │
│  ┌────────────────────────────────────────────────────────────────┐   │
│  │  👥 UTILISATEURS & PERMISSIONS                                  │   │
│  ├────────────────────────────────────────────────────────────────┤   │
│  │                                                                │   │
│  │  Utilisateur           Rôle              Dernière connexion    │   │
│  │  ──────────────────────────────────────────────────────────    │   │
│  │  commercial@vous.com   COMMERCIAL        15/10/2025 09:30      │   │
│  │  manager@vous.com      MANAGER           15/10/2025 08:45      │   │
│  │  admin@vous.com        ADMIN             14/10/2025 17:20      │   │
│  │                                                                │   │
│  │  [+ Nouvel utilisateur]  [Gérer les rôles]                     │   │
│  └────────────────────────────────────────────────────────────────┘   │
│                                                                         │
│  ┌────────────────────────────────────────────────────────────────┐   │
│  │  🔄 IMPORT / EXPORT                                            │   │
│  ├────────────────────────────────────────────────────────────────┤   │
│  │                                                                │   │
│  │  [Import contacts]  [Import entreprises]                       │   │
│  │  [Export complet]   [Télécharger modèles]                      │   │
│  │                                                                │   │
│  │  Dernier export: 10/10/2025 (export_crm.zip - 2.5 Mo)         │   │
│  └────────────────────────────────────────────────────────────────┘   │
│                                                                         │
└────────────────────────────────────────────────────────────────────────┘
```

### 19.2 Gestion des pipelines

```
┌────────────────────────────────────────────────────────────────────────┐
│  📊 Gestion des pipelines                          [+ Nouveau pipeline]│
├────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  ┌──────────────────────────────────────────────────────────────────┐ │
│  │ 🔵 Ventes B2B                                    [Modifier] [...]│ │
│  ├──────────────────────────────────────────────────────────────────┤ │
│  │ Pipeline pour les ventes B2B complexes                          │ │
│  │ Utilisé par: ROLE_COMMERCIAL, ROLE_MANAGER                      │ │
│  │ 45 affaires actives • Total: 1.2M€                              │ │
│  │                                                                  │ │
│  │ Étapes (5):                                                      │ │
│  │ 1. Qualification (10%) - 15 affaires - 375K€                   │ │
│  │ 2. Proposition (50%) - 12 affaires - 600K€                     │ │
│  │ 3. Négociation (75%) - 8 affaires - 400K€                      │ │
│  │ 4. Contrat (90%) - 5 affaires - 450K€                          │ │
│  │ 5. Gagné (100%) - Archivé                                       │ │
│  │                                                                  │ │
│  │ [Gérer les étapes]  [Voir statistiques]                         │ │
│  └──────────────────────────────────────────────────────────────────┘ │
│                                                                         │
│  ┌──────────────────────────────────────────────────────────────────┐ │
│  │ 🟢 Ventes B2C                                    [Modifier] [...]│ │
│  ├──────────────────────────────────────────────────────────────────┤ │
│  │ Pipeline simplifié pour ventes B2C rapides                      │ │
│  │ Utilisé par: ROLE_COMMERCIAL                                    │ │
│  │ 23 affaires actives • Total: 180K€                              │ │
│  │                                                                  │ │
│  │ Étapes (4):                                                      │ │
│  │ 1. Contact (25%) - 2. Démo (50%) - 3. Closing (75%) - 4. Gagné │ │
│  │                                                                  │ │
│  │ [Gérer les étapes]  [Voir statistiques]                         │ │
│  └──────────────────────────────────────────────────────────────────┘ │
│                                                                         │
│  ...                                                                    │
│                                                                         │
└────────────────────────────────────────────────────────────────────────┘
```

---

# PARTIE 4 - GUIDE DÉVELOPPEUR

## 20. Installation et configuration

### 20.1 Prérequis

**Logiciels requis** :
- **PHP** ≥ 8.2
- **Composer** (gestionnaire de dépendances PHP)
- **MySQL** ≥ 8.0
- **Node.js** ≥ 18.x (pour assets frontend)
- **Git**

**Extensions PHP nécessaires** :
- ext-ctype
- ext-iconv
- ext-pdo_mysql
- ext-json
- ext-mbstring

### 20.2 Installation pas à pas

#### Étape 1 : Cloner le repository

```bash
git clone https://github.com/votre-org/crm.git
cd crm
```

#### Étape 2 : Installer les dépendances

```bash
# Dépendances PHP
composer install

# Dépendances JavaScript (si applicable)
npm install
```

#### Étape 3 : Configuration de l'environnement

```bash
# Copier le fichier d'environnement
cp .env .env.local

# Éditer .env.local
nano .env.local
```

**Paramètres à configurer** :

```ini
# .env.local

# DATABASE
DATABASE_URL="mysql://user:password@127.0.0.1:3306/crm?serverVersion=8.0&charset=utf8mb4"

# JWT (Générer les clés avec: php bin/console lexik:jwt:generate-keypair)
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=your_secret_passphrase

# APP
APP_ENV=dev
APP_SECRET=your_app_secret_here

# MAILER (optionnel)
MAILER_DSN=smtp://localhost

# CORS
CORS_ALLOW_ORIGIN=^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$
```

#### Étape 4 : Générer les clés JWT

```bash
php bin/console lexik:jwt:generate-keypair
```

#### Étape 5 : Créer la base de données

```bash
# Créer la base
php bin/console doctrine:database:create

# Exécuter les migrations
php bin/console doctrine:migrations:migrate

# Charger les données de test (optionnel)
php bin/console doctrine:fixtures:load
# ou
php bin/console app:init-fixtures
```

#### Étape 6 : Installer les assets

```bash
php bin/console assets:install
php bin/console importmap:install
```

#### Étape 7 : Lancer le serveur de développement

```bash
symfony server:start
# ou
php -S localhost:8000 -t public/
```

Accédez à : `http://localhost:8000`

### 20.3 Configuration multi-tenant

#### Créer un nouveau tenant

```bash
# 1. Créer la base de données
mysql -u root -p
CREATE DATABASE tenant_newclient_crm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit;

# 2. Définir le tenant actif
echo "newclient" > tenant.txt

# 3. Exécuter les migrations pour ce tenant
php bin/console doctrine:migrations:migrate --no-interaction

# 4. Charger les fixtures (optionnel)
php bin/console app:init-fixtures

# 5. Ajouter le tenant dans le code
# Éditer src/MultiTenancy/Switcher.php
# Ajouter 'newclient' dans const ACCEPTED
```

### 20.4 Tests

```bash
# Lancer tous les tests
php bin/phpunit

# Test spécifique
php bin/phpunit tests/Controller/ContactControllerTest.php

# Avec couverture de code
php bin/phpunit --coverage-html coverage
# Ouvrir coverage/index.html dans un navigateur
```

---

## 21. Structure du code

### 21.1 Arborescence du projet

```
crm/
├── bin/                     # Exécutables (console)
│   └── console
├── config/                  # Configuration Symfony
│   ├── packages/            # Configuration des bundles
│   ├── routes/              # Routes
│   └── services.yaml        # Services DI
├── jsClasses/               # Classes TypeScript générées
│   ├── react/               # Pour React
│   └── vueJS/               # Pour Vue.js
├── migrations/              # Migrations Doctrine
├── public/                  # Point d'entrée web
│   ├── index.php
│   └── .htaccess
├── src/                     # Code source
│   ├── Controller/          # Contrôleurs API
│   ├── Entity/              # Entités Doctrine
│   ├── Managers/            # Logique métier
│   ├── MultiTenancy/        # Système multi-tenant
│   ├── Repository/          # Repositories Doctrine
│   ├── Security/            # Authentification/Autorisation
│   ├── Traits/              # Traits réutilisables
│   └── Kernel.php
├── templates/               # Templates Twig (si applicable)
├── tests/                   # Tests PHPUnit
├── var/                     # Fichiers générés (cache, logs)
├── vendor/                  # Dépendances Composer
├── .env                     # Configuration environnement (template)
├── composer.json            # Dépendances PHP
└── symfony.lock
```

### 21.2 Pattern Manager expliqué

Les **Managers** centralisent la logique métier complexe.

**Exemple** : `ContactManager`

```php
namespace App\Managers;

class ContactManager extends Manager
{
    public function __construct(
        private ManagerRegistry $registry,
        private PropertyManager $propertyManager,
        private PhoneNumberManager $phoneManager,
        private MailManager $mailManager,
        private TagManager $tagManager,
        private EntityManagerInterface $em
    ) {}

    /**
     * Crée ou met à jour un contact
     */
    public function edit(array $data): ?Contact
    {
        // Logique de création/modification
        // Gestion des relations (properties, phones, mails, tags)
        // Validations métier
        // Persist & flush

        return $contact;
    }

    /**
     * Fusionne deux contacts
     */
    public function merge(Contact $source, Contact $target): void
    {
        // Transfère toutes les relations
        // Supprime logiquement le contact source
    }
}
```

**Utilisation dans un contrôleur** :

```php
#[Route('/contact/edit', name: 'app_contact_edit')]
public function editContact(Request $request): Response
{
    $data = json_decode($request->getContent(), true);
    $contact = $this->contactManager->edit($data);

    return $this->json([
        'status' => 'success',
        'contact' => $contact
    ], 200, [], ['groups' => 'contact:edit']);
}
```

### 21.3 Repositories personnalisés

**Exemple** : `ContactRepository`

```php
namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

class ContactRepository extends ServiceEntityRepository
{
    /**
     * Liste paginée avec filtres
     */
    public function listContacts(array $filters): array
    {
        $qb = $this->createQueryBuilder('c');

        // Pagination
        $page = $filters['pagination']['page'] ?? 1;
        $limit = $filters['pagination']['limit'] ?? 25;
        $offset = ($page - 1) * $limit;

        // Filtres
        if (isset($filters['search'])) {
            $qb->leftJoin('c.properties', 'p')
               ->andWhere('p.value LIKE :search')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }

        // Tri
        $qb->orderBy('c.id', 'DESC');

        return $qb->setFirstResult($offset)
                  ->setMaxResults($limit)
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Recherche rapide
     */
    public function searchContacts(string $term): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.properties', 'p')
            ->leftJoin('c.mails', 'm')
            ->leftJoin('c.phones', 'ph')
            ->where('p.value LIKE :term')
            ->orWhere('m.email LIKE :term')
            ->orWhere('ph.number LIKE :term')
            ->setParameter('term', '%' . $term . '%')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }
}
```

### 21.4 Sérialisation avec groupes

Les groupes de sérialisation contrôlent quelles données sont exposées dans l'API.

**Dans l'entité** :

```php
#[ORM\Column]
#[Groups(['contact:list', 'contact:info', 'contact:edit'])]
private ?int $id = null;

#[ORM\Column(length: 255)]
#[Groups(['contact:list', 'contact:info'])]
private ?string $source = null;

#[ORM\OneToMany(targetEntity: Deal::class, mappedBy: 'contact')]
#[Groups(['contact:info'])] // Seulement en vue détaillée
private Collection $deals;
```

**Dans le contrôleur** :

```php
// Vue liste (minimal)
return $this->json($contacts, 200, [], ['groups' => 'contact:list']);

// Vue détaillée (complet)
return $this->json($contact, 200, [], ['groups' => ['contact:info', 'userManagement']]);
```

---

## 22. Commandes disponibles

### 22.1 Commandes Doctrine

```bash
# Base de données
php bin/console doctrine:database:create
php bin/console doctrine:database:drop --force

# Migrations
php bin/console doctrine:migrations:diff      # Générer migration
php bin/console doctrine:migrations:migrate   # Exécuter migrations
php bin/console doctrine:migrations:status    # Statut migrations

# Fixtures
php bin/console doctrine:fixtures:load        # Charger fixtures
```

### 22.2 Commandes personnalisées

#### app:init-fixtures

Initialise les données de base (types, propriétés, pipelines).

```bash
php bin/console app:init-fixtures
```

#### app:generate-js-class

Génère les classes TypeScript pour React/Vue depuis les entités PHP.

```bash
php bin/console app:generate-js-class
```

**Résultat** :
- `jsClasses/react/Contact.ts`
- `jsClasses/vueJS/Contact.ts`
- etc.

#### app:tenant:set

Définit le tenant actif pour les commandes CLI.

```bash
php bin/console app:tenant:set ci
```

#### app:tenant:get

Affiche le tenant actif.

```bash
php bin/console app:tenant:get
```

#### app:tenant:foreach

Exécute une commande pour chaque tenant.

```bash
php bin/console app:tenant:foreach doctrine:migrations:status
```

#### app:debug

Commande de débogage personnalisée.

```bash
php bin/console app:debug
```

#### app:generate-access-map

Génère la carte d'accès des routes avec permissions.

```bash
php bin/console app:generate-access-map
```

### 22.3 Commandes Symfony standards

```bash
# Cache
php bin/console cache:clear
php bin/console cache:warmup

# Routing
php bin/console debug:router                    # Liste toutes les routes
php bin/console debug:router app_contact_list   # Détails d'une route

# Services
php bin/console debug:container                 # Liste services
php bin/console debug:autowiring                # Services auto-wirables

# Assets
php bin/console assets:install
php bin/console importmap:install
```

---

## 23. Tests et développement

### 23.1 Tests unitaires

**Emplacement** : `tests/`

**Structure** :

```
tests/
├── Controller/
│   ├── ContactControllerTest.php
│   ├── CompanyControllerTest.php
│   └── DealControllerTest.php
├── Entity/
│   └── ContactTest.php
└── Manager/
    └── ContactManagerTest.php
```

**Exemple de test** :

```php
namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ContactControllerTest extends WebTestCase
{
    public function testListContacts(): void
    {
        $client = static::createClient();

        $client->request('POST', '/contact/list', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'pagination' => ['page' => 1, 'limit' => 25]
        ]));

        $this->assertResponseIsSuccessful();
        $this->assertJson($client->getResponse()->getContent());

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('success', $data['status']);
        $this->assertArrayHasKey('contacts', $data);
    }
}
```

### 23.2 Base de données de test

Le système utilise automatiquement une base suffixée `_test` en environnement de test.

**Configuration** : `config/packages/test/doctrine.yaml`

### 23.3 Fixtures de test

**Créer une fixture** :

```php
namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ContactFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        for ($i = 1; $i <= 50; $i++) {
            $contact = new Contact();
            $contact->setSource('website');
            // ...
            $manager->persist($contact);
        }

        $manager->flush();
    }
}
```

**Charger** :

```bash
php bin/console doctrine:fixtures:load --env=test
```

### 23.4 Environnement de développement

**Profiler Symfony** :

En mode `dev`, activez le profiler pour déboguer :
- Requêtes SQL
- Performance
- Événements
- Logs

Accès : `http://localhost:8000/_profiler`

**Debug Toolbar** :

Barre en bas de page en mode dev avec :
- Temps d'exécution
- Mémoire utilisée
- Requêtes DB
- Etc.

---

## 24. Référence API

### 24.1 Documentation complète

Voir le fichier [API_DOCUMENTATION.md](API_DOCUMENTATION.md) pour la référence API complète.

### 24.2 Authentification

**Obtenir un token JWT** :

```bash
POST /api/login_check
Content-Type: application/json

{
  "username": "user@example.com",
  "password": "password"
}

Response:
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."
}
```

**Utiliser le token** :

```bash
GET /contact/list
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...
```

### 24.3 Endpoints principaux

| Endpoint | Méthode | Description |
|----------|---------|-------------|
| `/contact/list` | POST | Liste contacts |
| `/contact/info/{id}` | GET | Détail contact |
| `/contact/edit` | POST | Créer/modifier contact |
| `/company/list` | POST | Liste entreprises |
| `/company/info/{id}` | GET | Détail entreprise |
| `/deal/edit` | POST | Créer/modifier affaire |
| `/deal/change/step/{id}` | PATCH | Changer étape |
| `/activity/edit` | POST | Créer/modifier activité |
| `/pipeline/list` | GET | Liste pipelines |

### 24.4 Format des réponses

**Succès** :

```json
{
  "status": "success",
  "data": {...}
}
```

**Erreur** :

```json
{
  "status": "error",
  "message": "Contact non trouvé",
  "code": 404
}
```

---

# PARTIE 5 - WORKFLOWS MÉTIER

## 25. Scénarios d'utilisation

### 25.1 Scénario 1 : Processus complet de vente B2B

**Contexte** : Un commercial rencontre un prospect lors d'un salon professionnel.

#### Étapes :

**1. Créer le contact**
- Depuis l'application mobile ou web
- Informations : Nom, prénom, email, téléphone
- Source : "Salon TechParis 2025"
- Tags : "Prospect", "Salon"

**2. Créer l'entreprise (si inexistante)**
- Nom, secteur, site web
- Associer le contact à l'entreprise

**3. Créer l'affaire**
- Objet : "Vente CRM Enterprise"
- Contact : Le prospect rencontré
- Étape initiale : "Qualification"
- Montant estimé : 50K€

**4. Planifier première activité**
- Type : Appel téléphonique
- Date : J+2 après le salon
- Objectif : Qualifier le besoin

**5. Après l'appel de qualification**
- Marquer l'activité comme effectuée
- Ajouter note de compte-rendu
- Faire progresser vers "Proposition"
- Planifier démonstration produit

**6. Démonstration**
- Meeting planifié chez le client
- Participants : Contact + décisionnaires
- Compte-rendu : Positif
- Passer en "Négociation"

**7. Envoi proposition commerciale**
- Activité : Email avec devis
- Joindre document PDF à l'affaire
- Planifier relance J+3

**8. Négociation et closing**
- Ajustement de l'offre
- Validation finale
- Passer en étape "Contrat"

**9. Signature**
- Joindre contrat signé
- Marquer l'affaire comme "Gagnée"
- Le contact devient "Client"

**10. Suivi post-vente**
- Planifier formation utilisateurs
- Activité de suivi à J+30

**Durée totale typique** : 30-45 jours

### 25.2 Scénario 2 : Import massif de contacts

**Contexte** : Migration depuis un ancien CRM ou liste de contacts.

#### Étapes :

**1. Préparation du fichier CSV**

```csv
Prénom,Nom,Email,Téléphone,Entreprise,Fonction,Source
John,Doe,john@acme.com,+33612345678,ACME Corp,Directeur IT,migration
Jane,Smith,jane@xyz.com,+33698765432,XYZ Ltd,CEO,migration
...
```

**2. Import via l'interface**
- Menu "Contacts" > "Import"
- Sélectionner le fichier CSV
- Mapper les colonnes (automatique si noms correspondent)

**3. Validation**
- Vérifier le rapport d'import
- 140 contacts créés
- 5 entreprises créées automatiquement
- 3 doublons détectés

**4. Post-traitement**
- Fusionner les doublons manuellement
- Compléter informations manquantes
- Affecter tags appropriés ("Migration", secteurs, etc.)
- Répartir entre commerciaux

**5. Qualification progressive**
- Campagne de recontact
- Créer activités de qualification
- Enrichir les fiches contact

### 25.3 Scénario 3 : Gestion d'équipe commerciale

**Contexte** : Manager supervise une équipe de 5 commerciaux.

#### Workflows quotidiens :

**Matin (Manager)** :
1. Consulter le dashboard
2. Vérifier KPIs de l'équipe
3. Identifier activités en retard
4. Affaires sans activité depuis 7j+

**Réunion hebdomadaire** :
1. Revue du pipeline par commercial
2. Affaires bloquées : analyse et déblocage
3. Objectifs semaine suivante
4. Export statistiques pour direction

**Suivi d'affaire** :
1. Manager est notifié : affaire importante en négociation
2. Consulte l'historique et timeline
3. Ajoute une note stratégique
4. Se marque comme participant
5. Programme un point d'équipe

**Rapports mensuels** :
1. Export complet des données
2. Analyse taux de conversion par étape
3. Identification goulots d'étranglement
4. Ajustement probabilités des étapes

---

## 26. Bonnes pratiques

### 26.1 Saisie des données

**Contacts** :
- ✅ Toujours remplir au minimum : Nom, Prénom, Email
- ✅ Indiquer la source (traçabilité)
- ✅ Assigner un responsable dès la création
- ❌ Ne pas créer de doublons : rechercher avant de créer
- ✅ Utiliser tags pour catégoriser

**Entreprises** :
- ✅ Format standardisé du nom (ACME Corp, pas "acme" ou "Acme corporation")
- ✅ URL complète du site web (https://...)
- ✅ Secteur d'activité cohérent
- ✅ Associer tous les contacts de l'entreprise

**Affaires** :
- ✅ Objet clair et descriptif ("Vente CRM 50 licences" plutôt que "Vente")
- ✅ Montant réaliste basé sur devis
- ✅ Toujours une activité planifiée (ne jamais laisser sans action)
- ✅ Mettre à jour régulièrement la probabilité
- ❌ Ne pas laisser stagner dans une étape > 14 jours

**Activités** :
- ✅ Nom explicite ("Démo CRM ACME" plutôt que "RDV")
- ✅ Toujours lier à une affaire si pertinent
- ✅ Compte-rendu systématique après réalisation
- ✅ Définir prochaine action immédiatement

### 26.2 Hygiène des données

**Quotidien** :
- Compléter compte-rendu d'activités le jour même
- Mettre à jour étapes des affaires en temps réel
- Marquer activités effectuées

**Hebdomadaire** :
- Relancer affaires sans activité
- Vérifier contacts sans entreprise assignée
- Nettoyer tags obsolètes

**Mensuel** :
- Fusionner doublons détectés
- Archiver affaires perdues anciennes
- Réviser pipelines et probabilités

### 26.3 Sécurité

**Mots de passe** :
- Minimum 12 caractères
- Mélange majuscules, minuscules, chiffres, symboles
- Changer tous les 90 jours
- Ne jamais partager

**Données sensibles** :
- Ne pas inclure informations bancaires dans notes
- Chiffrer documents confidentiels avant upload
- Limiter partage d'accès

**RGPD** :
- Droit à l'oubli : supprimer données sur demande
- Export données personnelles sur demande
- Consentement tracé dans notes

---

## 27. Cas d'usage réels

### 27.1 Startup SaaS B2B

**Profil** : 5 commerciaux, ventes entre 5K€ et 100K€, cycle 30-60 jours.

**Configuration** :
- Pipeline unique "Ventes SaaS" : Découverte → Démo → Essai → Négociation → Signature
- Tags : Trial, Freemium, Enterprise, Churn Risk
- Propriétés personnalisées : MRR, ARR, Nombre utilisateurs

**Workflow type** :
1. Lead entre via website → Contact créé automatiquement (webhook)
2. Commercial qualifie sous 24h
3. Démo planifiée J+3
4. Trial 14 jours activé → Suivi automated
5. Conversion ou perdu

**KPIs suivis** :
- Taux conversion Lead → Trial : 25%
- Taux conversion Trial → Client : 35%
- MRR moyen
- Churn rate

### 27.2 Agence de services

**Profil** : Projets one-shot, montants variables, cycle court.

**Configuration** :
- Pipeline "Projets" : Contact → Cadrage → Chiffrage → Négociation → GO
- Tags par type projet : WebDev, Mobile, Conseil, Formation
- Propriétés : Durée projet, Technologies, Budget

**Workflow** :
1. Demande de devis reçue
2. Échange téléphonique cadrage
3. Chiffrage et envoi proposition
4. Négociation si besoin
5. Bon de commande → Gagné
6. Projet → Fichiers liés à l'affaire

### 27.3 Commerce B2B traditionnel

**Profil** : Force de vente terrain, catalogues produits, revendeurs.

**Configuration** :
- Pipelines multiples par gamme produit
- Tags : Revendeur, Utilisateur final, Prescription, VIP
- Géolocalisation importante

**Workflow** :
1. Tournée commerciale : visites clients
2. Prise de commande sur tablette
3. Synchro CRM temps réel
4. Suivi livraison
5. Relances automatiques J+30, J+60

---

## Annexes

### A. Glossaire

- **Affaire (Deal)** : Opportunité commerciale en cours
- **Pipeline** : Processus de vente structuré en étapes
- **Étape** : Phase du processus de vente (ex: Qualification, Proposition)
- **Activité** : Tâche ou événement planifié (appel, RDV, email)
- **Tag** : Étiquette de catégorisation
- **Propriété** : Champ personnalisé
- **PropertyModel** : Modèle/définition d'un champ personnalisé
- **Tenant** : Organisation/client dans le système multi-tenant
- **Manager** : Classe contenant la logique métier
- **Repository** : Classe d'accès aux données
- **Sérialisation** : Conversion objet PHP → JSON
- **Groupe de sérialisation** : Ensemble de champs exposés dans l'API
- **JWT** : JSON Web Token, mécanisme d'authentification
- **UUID** : Identifiant universel unique

### B. FAQ

**Q : Peut-on récupérer un contact supprimé ?**
R : Oui, la suppression est logique. Un administrateur peut restaurer depuis l'interface admin.

**Q : Combien de pipelines peut-on créer ?**
R : Illimité. Recommandation : 1 à 3 pour ne pas complexifier.

**Q : Les emails sont-ils envoyés depuis le CRM ?**
R : Non, le CRM track les activités "email" mais l'envoi se fait depuis votre client email habituel. Intégration possible via API.

**Q : Peut-on accéder au CRM depuis mobile ?**
R : Oui, l'API permet de développer une app mobile. Classes TypeScript React Native fournies.

**Q : Les données sont-elles sauvegardées ?**
R : À configurer côté infrastructure. Recommandation : backup quotidien de la base MySQL.

**Q : Peut-on personnaliser les emails de notification ?**
R : Oui, templates dans `templates/emails/` modifiables.

**Q : Limitation du nombre de contacts ?**
R : Non, dépend de votre infrastructure serveur.

**Q : Intégration avec d'autres outils ?**
R : Oui via API REST. Webhooks configurables.

### C. Support et ressources

**Documentation officielle** :
- [Symfony](https://symfony.com/doc)
- [Doctrine](https://www.doctrine-project.org)
- [JWT Bundle](https://github.com/lexik/LexikJWTAuthenticationBundle)

**Communauté** :
- Forum utilisateurs : [forum.crm-exemple.com]
- Issues GitHub : [github.com/org/crm/issues]
- Email support : support@crm-exemple.com

**Mises à jour** :
- Vérifier releases : `git fetch && git tag`
- Changelog : `CHANGELOG.md`
- Migration guides : `docs/migrations/`

---

**Fin de la documentation complète**

*Document généré le 29 octobre 2025*
*Version 1.0*