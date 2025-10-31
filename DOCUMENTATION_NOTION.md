# 📚 Documentation CRM Multi-Tenant

> 💡 **À propos**
> Version 1.0 • Symfony 7.2 • Octobre 2025

---

# 📑 Table des matières

## 🏢 PARTIE 1 - Documentation Métier et Technique
- [Vue d'ensemble du système](#vue-densemble-du-système)
- [Architecture technique](#architecture-technique)
- [Modèle de données](#modèle-de-données)
- [Architecture Multi-tenant](#architecture-multi-tenant)
- [Sécurité et authentification](#sécurité-et-authentification)

## 📖 PARTIE 2 - Manuel d'Utilisation
- [Guide de démarrage](#guide-de-démarrage)
- [Gestion des contacts](#gestion-des-contacts)
- [Gestion des entreprises](#gestion-des-entreprises)
- [Gestion des affaires](#gestion-des-affaires-deals)
- [Gestion des activités](#gestion-des-activités)
- [Pipelines de vente](#pipelines-de-vente)
- [Tags et catégorisation](#tags-et-catégorisation)
- [Import/Export de données](#importexport-de-données)

## 🎨 PARTIE 3 - Proposition d'Interface
- [Dashboard principal](#dashboard-principal)
- [Interface Contacts](#interface-contacts)
- [Interface Entreprises](#interface-entreprises)
- [Interface Pipeline/Affaires](#interface-pipelineaffaires)
- [Interface Activités](#interface-activités)
- [Interface Configuration](#interface-configuration)

## 👨‍💻 PARTIE 4 - Guide Développeur
- [Installation et configuration](#installation-et-configuration)
- [Structure du code](#structure-du-code)
- [Commandes disponibles](#commandes-disponibles)
- [Tests et développement](#tests-et-développement)
- [Référence API](#référence-api)

## 🔄 PARTIE 5 - Workflows Métier
- [Scénarios d'utilisation](#scénarios-dutilisation)
- [Bonnes pratiques](#bonnes-pratiques)
- [Cas d'usage réels](#cas-dusage-réels)

---

# 🏢 PARTIE 1 - Documentation Métier et Technique

## Vue d'ensemble du système

### 🎯 Présentation

Ce CRM (Customer Relationship Management) est une application professionnelle de gestion de la relation client conçue pour les entreprises modernes.

**Fonctionnalités principales :**

✅ **Gérer les contacts et entreprises** - Base de données centralisée de vos clients et prospects

✅ **Suivre les opportunités commerciales** - Pipeline de vente configurable avec suivi des affaires

✅ **Planifier les activités** - Calendrier d'activités avec notifications

✅ **Analyser les performances** - Suivi des conversions et statistiques de vente

✅ **Collaborer en équipe** - Partage d'informations et assignation de tâches

---

### 🌟 Caractéristiques principales

> 🏘️ **Multi-tenant**
> Le système supporte plusieurs organisations (tenants) sur une même infrastructure :
> - Isolation complète des données par tenant
> - Base de données séparée par tenant
> - Configuration personnalisée par organisation

> ⚙️ **Propriétés dynamiques**
> Un système de champs personnalisables permet d'adapter le CRM à votre métier :
> - Création de champs personnalisés pour contacts, entreprises, etc.
> - Types de données variés (texte, nombre, date, URL, localisation)
> - Configuration par type d'objet métier

> 📝 **Traçabilité complète**
> Chaque action est enregistrée avec :
> - Date et heure de création/modification/suppression
> - Utilisateur responsable de l'action
> - Adresse IP d'origine
> - UUID unique pour chaque objet

> 🔌 **API REST complète**
> Interface programmatique pour :
> - Intégrations tierces
> - Applications mobiles (React Native, Flutter)
> - Automatisations et workflows

---

### 👥 Public cible

**1. Utilisateurs finaux** 👤
- Commerciaux
- Responsables de comptes
- Équipes de vente

**2. Administrateurs** 👨‍💼
- Gestionnaires du système
- Configurateurs
- Responsables CRM

**3. Développeurs** 👨‍💻
- Équipes techniques
- Intégrations
- Extensions personnalisées

---

## Architecture technique

### 🛠️ Stack technique

**Backend**
- **Framework** : Symfony 7.2
- **PHP** : 8.2+
- **Base de données** : MySQL 8.0
- **ORM** : Doctrine 3.3
- **Authentication** : JWT (LexikJWTAuthenticationBundle)

**Frontend**
- **JavaScript** : TypeScript
- **Frameworks supportés** : React, Vue.js
- **Build** : Asset Mapper, Stimulus

**Bibliothèques principales**
- `ramsey/uuid-doctrine` : Gestion des UUID
- `phpoffice/phpspreadsheet` : Import/Export Excel/CSV
- `nelmio/cors-bundle` : Configuration CORS pour API

---

### 🏗️ Architecture en couches

```
┌─────────────────────────────────────────┐
│         Présentation (API REST)         │
│    Controllers + Serialization Groups   │
└─────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────┐
│           Logique Métier                │
│    Managers + Business Logic            │
└─────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────┐
│        Accès aux Données                │
│    Repositories + Doctrine ORM          │
└─────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────┐
│         Base de Données MySQL           │
│    Multi-tenant (DB par tenant)         │
└─────────────────────────────────────────┘
```

---

### 📦 Pattern Manager

> 💡 **Principe**
> La logique métier est encapsulée dans des classes Manager pour séparer les responsabilités :

**Responsabilités des Managers :**
- Validation des données métier
- Règles de gestion complexes
- Orchestration des opérations
- Gestion des transactions
- Logique de calcul

**Exemple : ContactManager**
```php
class ContactManager extends Manager
{
    public function create(array $data): Contact
    public function update(Contact $contact, array $data): Contact
    public function delete(Contact $contact): void
    public function search(array $criteria): array
    public function export(array $filters): string
}
```

---

## Modèle de données

### 📊 Entités principales

> 📇 **Contact**
> Représente un contact individuel (personne)
>
> **Champs principaux :**
> - `firstName`, `lastName` : Nom et prénom
> - `company` : Entreprise associée (relation ManyToOne)
> - `phones` : Numéros de téléphone (collection)
> - `mails` : Adresses email (collection)
> - `properties` : Propriétés personnalisées
> - `deals` : Affaires associées
> - `activities` : Activités liées
> - `notes` : Notes et commentaires
> - `tags` : Étiquettes de catégorisation

> 🏢 **Company**
> Représente une entreprise/organisation
>
> **Champs principaux :**
> - `name` : Nom de l'entreprise
> - `website` : Site web
> - `contacts` : Liste des contacts (relation OneToMany)
> - `deals` : Affaires de l'entreprise
> - `properties` : Propriétés personnalisées
> - `tags` : Étiquettes

> 💰 **Deal**
> Représente une opportunité commerciale
>
> **Champs principaux :**
> - `title` : Titre de l'affaire
> - `amount` : Montant (en centimes)
> - `status` : Statut (win, lost, null=en cours)
> - `probability` : Probabilité de succès (0-100)
> - `expectedCloseDate` : Date de clôture prévue
> - `contact` : Contact principal
> - `company` : Entreprise associée
> - `step` : Étape du pipeline
> - `participants` : Utilisateurs participants
> - `activities` : Activités liées

> 📅 **Activity**
> Représente une activité/tâche
>
> **Champs principaux :**
> - `title` : Titre de l'activité
> - `type` : Type (call, meeting, email, task, deadline)
> - `status` : Statut (todo, done, cancelled)
> - `date` : Date de l'activité
> - `duration` : Durée en minutes
> - `description` : Description détaillée
> - `contact` : Contact lié
> - `company` : Entreprise liée
> - `deal` : Affaire liée
> - `assignedTo` : Utilisateur assigné

> 🚀 **Pipeline / PipelineStep**
> Définissent le processus de vente
>
> **Pipeline :**
> - `name` : Nom du pipeline
> - `steps` : Étapes du pipeline
>
> **PipelineStep :**
> - `name` : Nom de l'étape
> - `position` : Ordre (0, 1, 2...)
> - `probability` : Probabilité par défaut
> - `pipeline` : Pipeline parent

> 🏷️ **Tag**
> Système de catégorisation flexible
>
> **Champs principaux :**
> - `name` : Nom du tag
> - `color` : Couleur (hex)
> - Peut être lié à : contacts, companies, deals

> ⚙️ **Property / PropertyModel**
> Système de champs personnalisés
>
> **PropertyModel :**
> - `name` : Nom du champ
> - `type` : Type (text, number, date, url, location)
> - `objectType` : Type d'objet (contact, company, deal)
>
> **Property :**
> - `propertyModel` : Modèle de propriété
> - `value` : Valeur du champ
> - Relation polymorphe vers l'objet parent

---

### 🔗 Relations principales

```
Contact
   ├── ManyToOne → Company
   ├── OneToMany → Phone
   ├── OneToMany → Mail
   ├── OneToMany → Property
   ├── OneToMany → Deal
   ├── OneToMany → Activity
   ├── OneToMany → Note
   └── ManyToMany → Tag

Company
   ├── OneToMany → Contact
   ├── OneToMany → Deal
   ├── OneToMany → Activity
   ├── OneToMany → Property
   └── ManyToMany → Tag

Deal
   ├── ManyToOne → Contact
   ├── ManyToOne → Company
   ├── ManyToOne → PipelineStep
   ├── ManyToMany → User (participants)
   ├── OneToMany → Activity
   ├── OneToMany → Note
   └── ManyToMany → Tag

Pipeline
   └── OneToMany → PipelineStep

PipelineStep
   ├── ManyToOne → Pipeline
   └── OneToMany → Deal
```

---

## Architecture Multi-tenant

### 🏘️ Principe

Le système utilise une approche **"database-per-tenant"** : chaque organisation dispose de sa propre base de données isolée.

**Structure :**
```
tenant_ci_crm     → Base pour le tenant "ci" (Côte d'Ivoire)
tenant_sn_crm     → Base pour le tenant "sn" (Sénégal)
tenant_bf_crm     → Base pour le tenant "bf" (Burkina Faso)
```

---

### 🔄 Composants Multi-tenant

> 🔀 **Switcher**
> `src/MultiTenancy/Switcher.php`
>
> Composant central qui gère le changement de tenant :
> - Détection du tenant (cookie, header, CLI)
> - Changement de connexion DB
> - Isolation des données

> 🔌 **ConnectionWrapper**
> `src/MultiTenancy/ConnectionWrapper.php`
>
> Enveloppe Doctrine pour gérer les connexions multi-tenant :
> - Wrapping de la connexion Doctrine
> - Changement dynamique de base de données
> - Gestion du pool de connexions

> 📡 **KernelListener**
> `src/MultiTenancy/KernelListener.php`
>
> Écoute les événements kernel pour :
> - Détecter le tenant au début de chaque requête
> - Switcher automatiquement
> - Gérer les erreurs de tenant

> 🌍 **Zone**
> `src/MultiTenancy/Zone.php`
>
> Gère les zones géographiques et configurations :
> - Configuration par zone
> - Paramètres régionaux
> - Locales et devises

---

### 🎯 Détection du tenant

**1. Via Cookie/Header HTTP**
```
Cookie: tenant=ci
Header: X-Tenant: ci
```

**2. Via CLI (fichier tenant.txt)**
```bash
echo "ci" > tenant.txt
php bin/console app:tenant:set ci
```

**3. Tenant par défaut**
```
DEMO (si aucun tenant détecté)
```

---

## Sécurité et authentification

### 🔐 Méthodes d'authentification

> 🎫 **JWT Authentication**
> Méthode principale pour l'API
>
> **Endpoints :**
> - `POST /login` → Retourne un token JWT
> - Token valide 1 heure
> - Refresh token pour renouvellement
>
> **Header requis :**
> ```
> Authorization: Bearer {token}
> ```

> 🔑 **API Key Authentication**
> Pour les intégrations service-to-service
>
> **Header requis :**
> ```
> X-API-Key: {api_key}
> ```

> 👑 **Master Authenticator**
> Authentification spéciale pour opérations cross-tenant
>
> **Usage :**
> - Administration système
> - Opérations de maintenance
> - Migration de données

---

### 🛡️ Autorisation et Voters

**UserVoter** (`src/Security/Voter/UserVoter.php`)

Gère les permissions personnalisées :
- Contrôle d'accès par rôle
- Vérification de propriété des objets
- Règles métier complexes

**Rôles disponibles :**
- `ROLE_USER` : Utilisateur standard
- `ROLE_ADMIN` : Administrateur
- `ROLE_SUPER_ADMIN` : Super administrateur

---

### 🔒 Sécurité des données

> ⚠️ **Soft Delete (Suppression logique)**
> Les données ne sont jamais physiquement supprimées :
> - Champ `deleted` (DateTime nullable)
> - `deletedBy` : Utilisateur ayant supprimé
> - `deletedByIp` : IP de suppression
> - Possibilité de restauration

> 📋 **Audit Trail**
> Traçabilité complète via `UserObjectTrait` :
> - `created` : Date de création
> - `createdBy` : Créateur
> - `createdByIp` : IP de création
> - `updated` : Date de modification
> - `updatedBy` : Modificateur
> - `updatedByIp` : IP de modification

---

# 📖 PARTIE 2 - Manuel d'Utilisation

## Guide de démarrage

### 🚀 Première connexion

**1. Accéder à l'application**
```
URL : https://votre-crm.com
```

**2. Se connecter**
- Saisissez votre email
- Saisissez votre mot de passe
- Cliquez sur "Se connecter"

**3. Découvrir le dashboard**

Après connexion, vous arrivez sur le tableau de bord qui affiche :
- 📊 Statistiques clés (contacts, deals, revenus)
- 📅 Activités du jour
- 🎯 Pipeline des ventes
- 📈 Graphiques de performance

---

### ⚙️ Configuration initiale

> 💡 **Première étape : Configurer votre profil**
> 1. Cliquez sur votre avatar (coin supérieur droit)
> 2. Sélectionnez "Mon profil"
> 3. Complétez vos informations
> 4. Ajoutez une photo de profil
> 5. Configurez vos notifications

> 🏷️ **Deuxième étape : Créer des tags**
> 1. Allez dans "Configuration" → "Tags"
> 2. Cliquez sur "Nouveau tag"
> 3. Nommez le tag (ex: "VIP", "Prospect chaud")
> 4. Choisissez une couleur
> 5. Cliquez sur "Enregistrer"

> 🚀 **Troisième étape : Configurer votre pipeline**
> 1. Allez dans "Configuration" → "Pipelines"
> 2. Créez les étapes de votre processus de vente :
>    - Prospection
>    - Qualification
>    - Proposition
>    - Négociation
>    - Clôture
> 3. Définissez les probabilités pour chaque étape

---

## Gestion des contacts

### 👤 Créer un contact

**Méthode 1 : Création manuelle**

1. Cliquez sur "Contacts" dans le menu
2. Cliquez sur "+ Nouveau contact"
3. Remplissez le formulaire :
   - **Obligatoire** : Prénom et Nom
   - **Recommandé** : Email, Téléphone, Entreprise
   - **Optionnel** : Adresse, Réseaux sociaux, Notes

4. Ajoutez des tags pour catégoriser
5. Cliquez sur "Enregistrer"

**Méthode 2 : Import CSV/Excel**

1. Allez dans "Contacts" → "Importer"
2. Téléchargez le fichier CSV/Excel
3. Mappez les colonnes
4. Validez l'import

---

### 🔍 Rechercher un contact

**Recherche rapide**
- Tapez dans la barre de recherche (en haut)
- Résultats en temps réel

**Recherche avancée**
- Cliquez sur "Filtres avancés"
- Filtrez par :
  - Tags
  - Entreprise
  - Date de création
  - Propriétés personnalisées

**Tri**
- Cliquez sur les en-têtes de colonnes
- Tri croissant/décroissant

---

### ✏️ Modifier un contact

1. Ouvrez la fiche du contact
2. Cliquez sur "Modifier"
3. Modifiez les champs nécessaires
4. Cliquez sur "Enregistrer"

> 💡 **Astuce**
> Double-cliquez sur un champ pour éditer rapidement depuis la liste

---

### 📋 Fiche contact complète

**Sections disponibles :**

**📇 Informations générales**
- Nom, prénom, entreprise
- Coordonnées (emails, téléphones)
- Adresse, site web
- Tags

**💰 Affaires liées**
- Liste des deals en cours
- Historique des affaires
- Montant total des opportunités

**📅 Activités**
- Prochaines activités planifiées
- Historique des interactions
- Bouton "Nouvelle activité"

**📝 Notes**
- Notes chronologiques
- Ajout rapide de commentaires
- Historique complet

**📎 Documents**
- Fichiers attachés
- Contrats, devis
- Upload par glisser-déposer

**📊 Propriétés personnalisées**
- Champs configurés par votre organisation
- Données métier spécifiques

---

### 🗑️ Supprimer un contact

> ⚠️ **Attention**
> La suppression est logique : les données sont conservées mais masquées

1. Ouvrez la fiche du contact
2. Cliquez sur "⋮" (menu)
3. Sélectionnez "Supprimer"
4. Confirmez la suppression

**Restauration :**
- Les administrateurs peuvent restaurer les contacts supprimés
- Allez dans "Corbeille" pour voir les éléments supprimés

---

## Gestion des entreprises

### 🏢 Créer une entreprise

1. Cliquez sur "Entreprises" dans le menu
2. Cliquez sur "+ Nouvelle entreprise"
3. Remplissez le formulaire :
   - **Obligatoire** : Nom de l'entreprise
   - **Recommandé** : Site web, Secteur d'activité
   - **Optionnel** : Taille, Chiffre d'affaires

4. Ajoutez des tags
5. Cliquez sur "Enregistrer"

---

### 👥 Gérer les contacts d'une entreprise

**Ajouter un contact à une entreprise**

**Méthode 1 : Depuis la fiche entreprise**
1. Ouvrez la fiche entreprise
2. Section "Contacts"
3. Cliquez sur "+ Ajouter un contact"
4. Créez ou liez un contact existant

**Méthode 2 : Depuis la fiche contact**
1. Ouvrez la fiche contact
2. Champ "Entreprise"
3. Sélectionnez l'entreprise dans la liste

---

### 📊 Vue d'ensemble entreprise

**Informations affichées :**

- 📇 **Contacts associés** : Liste de tous les contacts
- 💰 **Affaires en cours** : Deals liés à l'entreprise
- 📈 **Statistiques** : CA généré, taux de conversion
- 📅 **Activités** : Historique des interactions
- 📝 **Notes** : Informations importantes

---

## Gestion des affaires (Deals)

### 💼 Créer une affaire

1. Cliquez sur "Affaires" ou "Pipeline"
2. Cliquez sur "+ Nouvelle affaire"
3. Remplissez le formulaire :

**Informations essentielles :**
- **Titre** : Nom de l'affaire (ex: "Projet CRM Acme Corp")
- **Montant** : Valeur en €
- **Contact** : Contact principal
- **Entreprise** : Entreprise concernée
- **Étape** : Étape du pipeline
- **Date de clôture prévue** : Estimation

**Informations complémentaires :**
- **Probabilité** : 0-100% (pré-remplie selon l'étape)
- **Participants** : Membres de l'équipe impliqués
- **Tags** : Catégorisation
- **Description** : Détails de l'opportunité

4. Cliquez sur "Enregistrer"

---

### 🎯 Vue Pipeline (Kanban)

**Navigation dans le pipeline :**

```
┌──────────────┐  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐
│ Prospection  │  │Qualification │  │ Proposition  │  │ Négociation  │
│              │  │              │  │              │  │              │
│ Deal A       │  │ Deal B       │  │ Deal C       │  │ Deal D       │
│ 5 000 €      │  │ 15 000 €     │  │ 50 000 €     │  │ 100 000 €    │
│              │  │              │  │              │  │              │
│ Deal E       │  │              │  │              │  │              │
│ 8 000 €      │  │              │  │              │  │              │
└──────────────┘  └──────────────┘  └──────────────┘  └──────────────┘
Total: 13K€       Total: 15K€       Total: 50K€       Total: 100K€
```

**Actions disponibles :**
- **Glisser-déposer** : Déplacez les cartes entre les étapes
- **Clic sur carte** : Ouvre la fiche détaillée
- **Filtres** : Par utilisateur, tag, montant, date

---

### 📈 Faire avancer une affaire

**Méthode 1 : Drag & Drop**
- Glissez la carte vers l'étape suivante
- La probabilité se met à jour automatiquement

**Méthode 2 : Depuis la fiche**
1. Ouvrez la fiche du deal
2. Changez le champ "Étape"
3. Enregistrez

---

### ✅ Clôturer une affaire

**Affaire gagnée :**
1. Ouvrez la fiche du deal
2. Cliquez sur "Marquer comme gagnée" 🎉
3. Le deal passe en statut "Won"
4. Les statistiques sont mises à jour

**Affaire perdue :**
1. Ouvrez la fiche du deal
2. Cliquez sur "Marquer comme perdue" ❌
3. Indiquez optionnellement la raison
4. Le deal passe en statut "Lost"

> 💡 **Analyse**
> Les affaires perdues alimentent les statistiques pour améliorer vos processus

---

## Gestion des activités

### 📅 Types d'activités

**📞 Appel téléphonique** (`call`)
- Appel sortant/entrant
- Durée de l'appel
- Notes de conversation

**🤝 Réunion** (`meeting`)
- Rendez-vous physique ou visio
- Participants
- Ordre du jour et compte-rendu

**📧 Email** (`email`)
- Email envoyé/reçu
- Objet et contenu
- Pièces jointes

**✅ Tâche** (`task`)
- Action à réaliser
- Liste de contrôle
- Assignation

**⏰ Échéance** (`deadline`)
- Date limite importante
- Rappels automatiques

---

### ➕ Créer une activité

**Méthode 1 : Depuis le calendrier**
1. Allez dans "Activités"
2. Cliquez sur une date ou un créneau
3. Le formulaire s'ouvre pré-rempli avec la date/heure

**Méthode 2 : Depuis une fiche (contact/deal)**
1. Ouvrez la fiche contact ou deal
2. Section "Activités"
3. Cliquez sur "+ Nouvelle activité"

**Méthode 3 : Création rapide**
1. Cliquez sur "+ Activité" (barre supérieure)
2. Remplissez le formulaire rapide

**Formulaire :**
- **Type** : Call, Meeting, Email, Task, Deadline
- **Titre** : Description courte
- **Date et heure** : Quand ?
- **Durée** : Combien de temps ?
- **Assigné à** : Quel utilisateur ?
- **Lié à** : Contact, Entreprise, Deal
- **Description** : Détails

---

### 📆 Vue Calendrier

**Vues disponibles :**
- **Jour** : Planning détaillé heure par heure
- **Semaine** : Vue hebdomadaire
- **Mois** : Vue mensuelle
- **Agenda** : Liste chronologique

**Code couleur :**
- 🟦 Bleu : Activité à venir
- 🟩 Vert : Activité terminée
- 🟥 Rouge : Activité en retard
- 🟧 Orange : Activité d'aujourd'hui

---

### ✅ Marquer une activité comme terminée

**Méthode rapide :**
- Cochez la case ✓ à côté de l'activité
- L'activité passe en statut "Done"

**Méthode complète :**
1. Ouvrez l'activité
2. Ajoutez un compte-rendu dans la description
3. Cliquez sur "Marquer comme terminée"

> 💡 **Productivité**
> Les activités terminées comptent dans vos statistiques d'activité

---

## Pipelines de vente

### 🚀 Créer un pipeline

1. Allez dans "Configuration" → "Pipelines"
2. Cliquez sur "+ Nouveau pipeline"
3. Nommez le pipeline (ex: "Vente B2B", "Vente B2C")
4. Créez les étapes :

**Exemple de pipeline B2B :**

| Étape | Probabilité | Description |
|-------|-------------|-------------|
| 1. Prospection | 10% | Identification du besoin |
| 2. Qualification | 25% | Budget confirmé |
| 3. Proposition | 50% | Devis envoyé |
| 4. Négociation | 75% | Discussion finale |
| 5. Clôture | 90% | Contrat en signature |

5. Cliquez sur "Enregistrer"

---

### ⚙️ Configurer les étapes

**Pour chaque étape :**

- **Nom** : Nom de l'étape
- **Position** : Ordre (0, 1, 2...)
- **Probabilité par défaut** : % de chance de conclure
- **Actions automatiques** (optionnel) :
  - Envoyer un email
  - Créer une tâche
  - Notifier l'équipe

---

### 🔄 Utiliser plusieurs pipelines

**Cas d'usage :**
- Pipeline "Vente Directe"
- Pipeline "Partenaires"
- Pipeline "Upsell Clients"

Chaque deal est lié à un seul pipeline.

---

## Tags et catégorisation

### 🏷️ Créer des tags

1. "Configuration" → "Tags"
2. "+ Nouveau tag"
3. Définissez :
   - **Nom** : Ex: "VIP", "Prospect chaud", "Client inactif"
   - **Couleur** : Choisissez une couleur hex (#FF5733)
4. Enregistrez

---

### 🎨 Bonnes pratiques de taggage

**Catégories recommandées :**

**Par statut client :**
- 🟢 Prospect chaud
- 🟡 Prospect tiède
- 🔵 Client actif
- ⚪ Client inactif

**Par secteur :**
- 💼 Finance
- 🏥 Santé
- 🏭 Industrie
- 🛒 Commerce

**Par priorité :**
- ⭐ VIP
- 🔥 Urgent
- 📌 À suivre

---

### 🔗 Appliquer des tags

**Méthode 1 : Depuis la fiche**
1. Ouvrez la fiche (contact/entreprise/deal)
2. Section "Tags"
3. Cliquez sur "+ Ajouter un tag"
4. Sélectionnez ou créez

**Méthode 2 : Modification en masse**
1. Sélectionnez plusieurs éléments (cases à cocher)
2. Menu "Actions groupées"
3. "Ajouter des tags"
4. Choisissez les tags

---

## Import/Export de données

### 📥 Importer des contacts

**Formats supportés :**
- CSV (recommandé)
- Excel (.xlsx, .xls)

**Étapes :**

1. Préparez votre fichier :
```csv
firstName,lastName,email,phone,company
Jean,Dupont,jean@example.com,+33612345678,Acme Corp
Marie,Martin,marie@example.com,+33687654321,Beta Inc
```

2. Allez dans "Contacts" → "Importer"

3. Téléchargez votre fichier

4. Mappez les colonnes :
   - Colonne CSV → Champ CRM
   - firstName → Prénom
   - lastName → Nom
   - email → Email principal
   - phone → Téléphone
   - company → Entreprise

5. Choisissez les options :
   - ☑️ Créer les entreprises manquantes
   - ☑️ Mettre à jour les contacts existants (si email correspond)
   - ☐ Ignorer les doublons

6. Validez l'import

7. Vérifiez le résultat :
   - ✅ 45 contacts créés
   - ⚠️ 5 erreurs (lignes invalides)

---

### 📤 Exporter des données

**Export de contacts :**

1. Allez dans "Contacts"
2. Appliquez des filtres (optionnel)
3. Cliquez sur "Exporter"
4. Choisissez le format :
   - CSV
   - Excel
5. Sélectionnez les champs à exporter
6. Téléchargez le fichier

**Export de deals :**

Même processus depuis "Affaires"

> 💡 **Astuce**
> Utilisez l'export pour des analyses dans Excel ou des imports vers d'autres outils

---

# 🎨 PARTIE 3 - Proposition d'Interface

## Dashboard principal

### 📊 Vue d'ensemble

```
┌───────────────────────────────────────────────────────────────────────┐
│  [Logo CRM]  Dashboard          🔍 Recherche...     👤 Jean Dupont ▼  │
├───────────────────────────────────────────────────────────────────────┤
│                                                                        │
│  📊 Dashboard  📇 Contacts  🏢 Entreprises  💼 Affaires  📅 Activités │
│                                                                        │
├───────────────────────────────────────────────────────────────────────┤
│                                                                        │
│  Bienvenue, Jean 👋                                  Mer 29 Oct 2025  │
│                                                                        │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐      │
│  │  📇 Contacts    │  │  💰 Deals       │  │  📈 CA Mensuel  │      │
│  │                 │  │                 │  │                 │      │
│  │      1,247      │  │       53        │  │    247,500 €    │      │
│  │   +12 ce mois   │  │   15 en cours   │  │      +15%       │      │
│  └─────────────────┘  └─────────────────┘  └─────────────────┘      │
│                                                                        │
│  ┌────────────────────────────────────┐  ┌────────────────────────┐  │
│  │  📅 Activités du jour              │  │  🎯 Pipeline           │  │
│  │                                    │  │                        │  │
│  │  ⏰ 10:00 - Appel Jean Martin      │  │  Prospection   13K€    │  │
│  │  🤝 14:00 - RDV Acme Corp          │  │  Qualification 28K€    │  │
│  │  📧 16:00 - Email de relance       │  │  Proposition   75K€    │  │
│  │                                    │  │  Négociation   130K€   │  │
│  │  [+ Nouvelle activité]             │  │  ────────────────────  │  │
│  │                                    │  │  Total Pipeline 246K€  │  │
│  └────────────────────────────────────┘  └────────────────────────┘  │
│                                                                        │
│  ┌──────────────────────────────────────────────────────────────────┐ │
│  │  📊 Graphique de performance (6 derniers mois)                   │ │
│  │                                                                   │ │
│  │   CA    │     ••••                                               │ │
│  │  250K   │    •    •••                                            │ │
│  │  200K   │   •           •                                        │ │
│  │  150K   │  •              ••                                     │ │
│  │  100K   │ •                  •                                   │ │
│  │   50K   │•                    •                                  │ │
│  │    0    ├─────────────────────────────────────────────          │ │
│  │         Mai  Juin  Juil  Août  Sept  Oct                        │ │
│  └──────────────────────────────────────────────────────────────────┘ │
│                                                                        │
└───────────────────────────────────────────────────────────────────────┘
```

### 🎯 Éléments clés

**En-tête :**
- Logo et nom de l'application
- Barre de recherche globale
- Menu utilisateur

**KPI Cards (Métriques) :**
- Nombre de contacts
- Nombre de deals actifs
- Chiffre d'affaires mensuel
- Évolutions en pourcentage

**Widgets :**
- Activités du jour (liste)
- Pipeline résumé (montants par étape)
- Graphique de performance

---

## Interface Contacts

### 📇 Liste des contacts

```
┌───────────────────────────────────────────────────────────────────────┐
│  [Logo] Contacts               🔍 Rechercher...    👤 Jean Dupont ▼   │
├───────────────────────────────────────────────────────────────────────┤
│                                                                        │
│  📇 Contacts                                     [+ Nouveau contact]  │
│                                                                        │
│  🔽 Filtres    🏷️ Tous les tags ▼   🏢 Toutes entreprises ▼          │
│                                                                        │
│  ┌──────────────────────────────────────────────────────────────────┐ │
│  │ ☐  Nom               Entreprise      Email           Tags    ⋮   │ │
│  ├──────────────────────────────────────────────────────────────────┤ │
│  │ ☐  Jean Dupont       Acme Corp      j.dupont@    🟢 VIP      ⋮   │ │
│  │ ☐  Marie Martin      Beta Inc       m.martin@    🟡 Prospect ⋮   │ │
│  │ ☐  Pierre Durand     Acme Corp      p.durand@    🔵 Client   ⋮   │ │
│  │ ☐  Sophie Bernard    Gamma SA       s.bernard@   🟢 VIP      ⋮   │ │
│  │ ☐  Luc Petit         Delta Ltd      l.petit@                 ⋮   │ │
│  │ ☐  Emma Dubois       Acme Corp      e.dubois@    🟡 Prospect ⋮   │ │
│  │                                                                   │ │
│  │                      ... (1,247 contacts au total)                │ │
│  └──────────────────────────────────────────────────────────────────┘ │
│                                                                        │
│  ◀ Précédent    1 2 3 4 5 ... 25    Suivant ▶              50/page ▼ │
│                                                                        │
└───────────────────────────────────────────────────────────────────────┘
```

### 📄 Fiche contact détaillée

```
┌───────────────────────────────────────────────────────────────────────┐
│  [Logo]  ◀ Retour aux contacts                    👤 Jean Dupont ▼   │
├───────────────────────────────────────────────────────────────────────┤
│                                                                        │
│  👤 Jean Dupont                           [Modifier]  [Supprimer]  ⋮  │
│  📧 j.dupont@acme.com  📞 +33 6 12 34 56 78                           │
│  🏢 Acme Corp          🏷️ VIP  Prospect chaud                         │
│                                                                        │
│  ┌─ 📇 Informations ──────────────────────────────────────────────┐  │
│  │                                                                  │  │
│  │  Prénom : Jean                    Nom : Dupont                  │  │
│  │  Entreprise : Acme Corp           Poste : Directeur Commercial  │  │
│  │  Email : j.dupont@acme.com        Tél : +33 6 12 34 56 78       │  │
│  │  Adresse : 123 rue de Paris, 75001 Paris                        │  │
│  │  LinkedIn : linkedin.com/in/jeandupont                           │  │
│  │                                                                  │  │
│  │  Créé le : 15 Jan 2025 par Marie M.                             │  │
│  │  Modifié le : 28 Oct 2025 par Sophie B.                         │  │
│  └──────────────────────────────────────────────────────────────────┘  │
│                                                                        │
│  ┌─ 💰 Affaires (3) ────────────────────────────────────────────────┐ │
│  │                                                                   │ │
│  │  • Projet CRM - 50,000 € - Proposition (50%)                     │ │
│  │  • Refonte Site Web - 25,000 € - Négociation (75%)               │ │
│  │  • Formation Équipe - 8,000 € - Prospection (10%)                │ │
│  │                                                                   │ │
│  │  [+ Nouvelle affaire]                                            │ │
│  └──────────────────────────────────────────────────────────────────┘ │
│                                                                        │
│  ┌─ 📅 Activités récentes ──────────────────────────────────────────┐ │
│  │                                                                   │ │
│  │  📞 28 Oct - Appel téléphonique (15 min)                         │ │
│  │     "Discussion sur le projet CRM, devis à envoyer"              │ │
│  │                                                                   │ │
│  │  🤝 22 Oct - Réunion (1h)                                        │ │
│  │     "Présentation de la solution, feedback positif"              │ │
│  │                                                                   │ │
│  │  📧 15 Oct - Email envoyé                                        │ │
│  │     "Relance suite à la démo"                                    │ │
│  │                                                                   │ │
│  │  [+ Nouvelle activité]          [Voir tout l'historique]         │ │
│  └──────────────────────────────────────────────────────────────────┘ │
│                                                                        │
│  ┌─ 📝 Notes (5) ────────────────────────────────────────────────────┐ │
│  │                                                                   │ │
│  │  💬 "Intéressé par module de reporting avancé"                   │ │
│  │     - Marie M. • 25 Oct 2025                                     │ │
│  │                                                                   │ │
│  │  💬 "Budget validé pour Q1 2026"                                 │ │
│  │     - Sophie B. • 20 Oct 2025                                    │ │
│  │                                                                   │ │
│  │  [Écrire une note...]                                            │ │
│  └──────────────────────────────────────────────────────────────────┘ │
│                                                                        │
└───────────────────────────────────────────────────────────────────────┘
```

---

## Interface Entreprises

### 🏢 Liste des entreprises

```
┌───────────────────────────────────────────────────────────────────────┐
│  [Logo] Entreprises            🔍 Rechercher...    👤 Jean Dupont ▼   │
├───────────────────────────────────────────────────────────────────────┤
│                                                                        │
│  🏢 Entreprises                                [+ Nouvelle entreprise] │
│                                                                        │
│  🔽 Filtres    🏷️ Tags ▼   🏭 Secteur ▼   👥 Taille ▼                │
│                                                                        │
│  ┌──────────────────────────────────────────────────────────────────┐ │
│  │ ☐  Entreprise      Secteur        Contacts    CA Potentiel   ⋮   │ │
│  ├──────────────────────────────────────────────────────────────────┤ │
│  │ ☐  Acme Corp       Technologie    12          247,500 €      ⋮   │ │
│  │ ☐  Beta Inc        Finance        5           125,000 €      ⋮   │ │
│  │ ☐  Gamma SA        Industrie      8           89,000 €       ⋮   │ │
│  │ ☐  Delta Ltd       Commerce       3           45,000 €       ⋮   │ │
│  │ ☐  Epsilon GmbH    Santé          15          320,000 €      ⋮   │ │
│  │                                                                   │ │
│  │                      ... (456 entreprises au total)               │ │
│  └──────────────────────────────────────────────────────────────────┘ │
│                                                                        │
│  ◀ Précédent    1 2 3 4 ... 12    Suivant ▶                 50/page ▼ │
│                                                                        │
└───────────────────────────────────────────────────────────────────────┘
```

### 🏢 Fiche entreprise détaillée

```
┌───────────────────────────────────────────────────────────────────────┐
│  [Logo]  ◀ Retour aux entreprises                 👤 Jean Dupont ▼   │
├───────────────────────────────────────────────────────────────────────┤
│                                                                        │
│  🏢 Acme Corp                                 [Modifier]  [Supprimer] │
│  🌐 www.acmecorp.com  📧 contact@acmecorp.com                         │
│  🏷️ Client VIP  Technologie                                          │
│                                                                        │
│  ┌─ 📊 Vue d'ensemble ──────────────────────────────────────────────┐ │
│  │                                                                   │ │
│  │  👥 12 contacts    💰 5 affaires    📈 247,500 € CA potentiel    │ │
│  │  📅 34 activités   🏆 Taux de conversion : 65%                   │ │
│  └──────────────────────────────────────────────────────────────────┘ │
│                                                                        │
│  ┌─ 🏢 Informations ─────────────────────────────────────────────────┐ │
│  │                                                                   │ │
│  │  Nom : Acme Corp                                                 │ │
│  │  Secteur : Technologie            Taille : 250-500 employés     │ │
│  │  Site web : www.acmecorp.com                                     │ │
│  │  Adresse : 456 avenue des Champs, 75008 Paris                   │ │
│  │  CA annuel : ~50M €                                              │ │
│  └──────────────────────────────────────────────────────────────────┘ │
│                                                                        │
│  ┌─ 👥 Contacts (12) ────────────────────────────────────────────────┐ │
│  │                                                                   │ │
│  │  👤 Jean Dupont - Directeur Commercial - j.dupont@acme.com       │ │
│  │  👤 Pierre Durand - CTO - p.durand@acme.com                      │ │
│  │  👤 Emma Dubois - Responsable Achats - e.dubois@acme.com         │ │
│  │                                                                   │ │
│  │  [Voir tous les contacts]          [+ Ajouter un contact]        │ │
│  └──────────────────────────────────────────────────────────────────┘ │
│                                                                        │
│  ┌─ 💰 Affaires en cours (5) ───────────────────────────────────────┐ │
│  │                                                                   │ │
│  │  • Projet CRM - 50,000 € - Proposition (50%)                     │ │
│  │  • Refonte Site Web - 25,000 € - Négociation (75%)               │ │
│  │  • Infrastructure Cloud - 120,000 € - Qualification (25%)        │ │
│  │                                                                   │ │
│  │  [Voir toutes les affaires]        [+ Nouvelle affaire]          │ │
│  └──────────────────────────────────────────────────────────────────┘ │
│                                                                        │
└───────────────────────────────────────────────────────────────────────┘
```

---

## Interface Pipeline/Affaires

### 💼 Vue Pipeline (Kanban)

```
┌───────────────────────────────────────────────────────────────────────┐
│  [Logo] Pipeline                                   👤 Jean Dupont ▼   │
├───────────────────────────────────────────────────────────────────────┤
│                                                                        │
│  💼 Pipeline de vente                           [+ Nouvelle affaire]  │
│                                                                        │
│  🔽 Pipeline: Vente B2B ▼   👤 Tous ▼   🏷️ Tags ▼   📅 Ce mois ▼     │
│                                                                        │
│ ┌────────────┬────────────┬────────────┬────────────┬────────────┐   │
│ │Prospection │Qualification│Proposition │Négociation │  Clôture   │   │
│ │   10%      │    25%      │    50%     │    75%     │    90%     │   │
│ │ ────────── │ ────────── │ ────────── │ ────────── │ ────────── │   │
│ │ 13,000 €   │  28,000 €   │  75,000 €  │ 130,000 €  │  25,000 €  │   │
│ ├────────────┼────────────┼────────────┼────────────┼────────────┤   │
│ │┌──────────┐│┌──────────┐│┌──────────┐│┌──────────┐│┌──────────┐│   │
│ ││ Projet A ││││ Projet C ││││ Projet F ││││ Projet H ││││ Projet K ││   │
│ ││ 5,000 €  ││││ 15,000 € ││││ 50,000 € ││││100,000 € ││││ 25,000 € ││   │
│ ││ Acme     ││││ Beta Inc ││││ Gamma    ││││ Delta    ││││ Epsilon  ││   │
│ ││ J.Dupont ││││ M.Martin ││││ P.Durand ││││ S.Bernard││││ L.Petit  ││   │
│ │└──────────┘││└──────────┘││└──────────┘││└──────────┘││└──────────┘│   │
│ │┌──────────┐││            ││            ││┌──────────┐││            │   │
│ ││ Projet B ││││            ││            ││││ Projet I ││││            │   │
│ ││ 8,000 €  ││││            ││            ││││ 30,000 € ││││            │   │
│ ││ Beta     ││││            ││            ││││ Zeta     ││││            │   │
│ │└──────────┘││            ││            ││└──────────┘││            │   │
│ │            ││            ││            ││            ││            │   │
│ └────────────┴────────────┴────────────┴────────────┴────────────┘   │
│                                                                        │
│  📊 Total Pipeline : 271,000 €    🎯 CA prévisionnel : 180,000 €      │
│                                                                        │
└───────────────────────────────────────────────────────────────────────┘
```

### 💼 Fiche affaire détaillée

```
┌───────────────────────────────────────────────────────────────────────┐
│  [Logo]  ◀ Retour au pipeline                     👤 Jean Dupont ▼   │
├───────────────────────────────────────────────────────────────────────┤
│                                                                        │
│  💼 Projet CRM                      [Modifier]  [✅ Gagné]  [❌ Perdu] │
│  🏢 Acme Corp  •  👤 Jean Dupont                                      │
│                                                                        │
│  ┌─ 💰 Informations financières ───────────────────────────────────┐  │
│  │                                                                  │  │
│  │  Montant : 50,000 €                   Probabilité : 50%         │  │
│  │  Date de clôture prévue : 15 Dec 2025                           │  │
│  │  CA prévisionnel : 25,000 €                                     │  │
│  └──────────────────────────────────────────────────────────────────┘  │
│                                                                        │
│  ┌─ 🚀 Progression dans le pipeline ────────────────────────────────┐ │
│  │                                                                   │ │
│  │  ✅ Prospection  →  ✅ Qualification  →  🔵 Proposition          │ │
│  │     10%              25%                 50% (actuel)            │ │
│  │                                                                   │ │
│  │                   →  ⚪ Négociation  →  ⚪ Clôture               │ │
│  │                      75%              90%                        │ │
│  └──────────────────────────────────────────────────────────────────┘ │
│                                                                        │
│  ┌─ 👥 Équipe ───────────────────────────────────────────────────────┐ │
│  │                                                                   │ │
│  │  👤 Jean Dupont (Propriétaire)                                   │ │
│  │  👤 Marie Martin (Participant)                                   │ │
│  │  👤 Sophie Bernard (Participant)                                 │ │
│  │                                                                   │ │
│  │  [+ Ajouter un participant]                                      │ │
│  └──────────────────────────────────────────────────────────────────┘ │
│                                                                        │
│  ┌─ 📅 Activités (8) ────────────────────────────────────────────────┐ │
│  │                                                                   │ │
│  │  📞 28 Oct - Appel de suivi (Jean D.)                            │ │
│  │  🤝 22 Oct - Présentation solution (Marie M.)                    │ │
│  │  📧 15 Oct - Email de relance (Sophie B.)                        │ │
│  │                                                                   │ │
│  │  [+ Nouvelle activité]          [Voir tout]                      │ │
│  └──────────────────────────────────────────────────────────────────┘ │
│                                                                        │
│  ┌─ 📝 Description ──────────────────────────────────────────────────┐ │
│  │                                                                   │ │
│  │  Projet d'implémentation d'un CRM complet pour Acme Corp.        │ │
│  │  Besoin de gérer 50 utilisateurs avec modules :                  │ │
│  │  - Gestion contacts/entreprises                                  │ │
│  │  - Pipeline de vente                                             │ │
│  │  - Reporting avancé                                              │ │
│  │  - Intégration API existante                                     │ │
│  │                                                                   │ │
│  │  Décision attendue pour fin décembre.                            │ │
│  └──────────────────────────────────────────────────────────────────┘ │
│                                                                        │
└───────────────────────────────────────────────────────────────────────┘
```

---

## Interface Activités

### 📅 Vue Calendrier

```
┌───────────────────────────────────────────────────────────────────────┐
│  [Logo] Activités                                  👤 Jean Dupont ▼   │
├───────────────────────────────────────────────────────────────────────┤
│                                                                        │
│  📅 Activités                                    [+ Nouvelle activité] │
│                                                                        │
│  [Jour] [Semaine] [Mois] [Agenda]    👤 Mes activités ▼   ⏰ Aujourd'hui│
│                                                                        │
│  ◀ Oct 2025 ▶                                                          │
│                                                                        │
│  ┌──────┬──────┬──────┬──────┬──────┬──────┬──────┐                  │
│  │  Lun │  Mar │  Mer │  Jeu │  Ven │  Sam │  Dim │                  │
│  ├──────┼──────┼──────┼──────┼──────┼──────┼──────┤                  │
│  │   1  │   2  │   3  │   4  │   5  │   6  │   7  │                  │
│  │      │  🤝  │      │  📞  │      │      │      │                  │
│  ├──────┼──────┼──────┼──────┼──────┼──────┼──────┤                  │
│  │   8  │   9  │  10  │  11  │  12  │  13  │  14  │                  │
│  │  📧  │      │  🤝  │      │  📞  │      │      │                  │
│  ├──────┼──────┼──────┼──────┼──────┼──────┼──────┤                  │
│  │  15  │  16  │  17  │  18  │  19  │  20  │  21  │                  │
│  │  ✅  │  📞  │      │  🤝  │      │      │      │                  │
│  ├──────┼──────┼──────┼──────┼──────┼──────┼──────┤                  │
│  │  22  │  23  │  24  │  25  │  26  │  27  │  28  │                  │
│  │  🤝  │      │  ✅  │  📧  │      │      │  📞  │                  │
│  ├──────┼──────┼──────┼──────┼──────┼──────┼──────┤                  │
│  │  29  │  30  │  31  │      │      │      │      │                  │
│  │ 🔵🔵 │      │      │      │      │      │      │                  │
│  │ 🤝📞 │      │      │      │      │      │      │                  │
│  └──────┴──────┴──────┴──────┴──────┴──────┴──────┘                  │
│                                                                        │
│  ┌─ 📅 Mercredi 29 Octobre 2025 ────────────────────────────────────┐ │
│  │                                                                   │ │
│  │  09:00 ────────────────────────────────────────────              │ │
│  │  10:00  📞 Appel Jean Martin (15 min)                            │ │
│  │         🏢 Acme Corp • 💼 Projet CRM                             │ │
│  │  11:00 ────────────────────────────────────────────              │ │
│  │  12:00 ────────────────────────────────────────────              │ │
│  │  13:00 ────────────────────────────────────────────              │ │
│  │  14:00  🤝 Réunion Acme Corp (1h)                                │ │
│  │         👤 Jean Dupont • 💼 Projet CRM                           │ │
│  │  15:00 ────────────────────────────────────────────              │ │
│  │  16:00  📧 Email de relance (30 min)                             │ │
│  │         🏢 Beta Inc • 💼 Projet Site Web                         │ │
│  │  17:00 ────────────────────────────────────────────              │ │
│  │                                                                   │ │
│  └──────────────────────────────────────────────────────────────────┘ │
│                                                                        │
└───────────────────────────────────────────────────────────────────────┘
```

### ✅ Vue Liste des tâches

```
┌───────────────────────────────────────────────────────────────────────┐
│  📋 Mes tâches                        🔽 Toutes ▼   📅 Cette semaine ▼│
│                                                                        │
│  ┌─ ⏰ En retard (2) ───────────────────────────────────────────────┐ │
│  │                                                                   │ │
│  │  ☐ 🔴 Envoyer devis Acme Corp                  📅 25 Oct (⏰-3j) │ │
│  │     💼 Projet CRM • 👤 Jean Dupont                               │ │
│  │                                                                   │ │
│  │  ☐ 🔴 Relancer Beta Inc                        📅 26 Oct (⏰-2j) │ │
│  │     💼 Projet Site Web • 👤 Marie Martin                         │ │
│  └──────────────────────────────────────────────────────────────────┘ │
│                                                                        │
│  ┌─ 📅 Aujourd'hui (5) ──────────────────────────────────────────────┐ │
│  │                                                                   │ │
│  │  ☐ 📞 Appel Jean Martin - 10:00                                  │ │
│  │     🏢 Acme Corp • 💼 Projet CRM                                 │ │
│  │                                                                   │ │
│  │  ☐ 🤝 Réunion Acme Corp - 14:00                                  │ │
│  │     👤 Jean Dupont • 💼 Projet CRM                               │ │
│  │                                                                   │ │
│  │  ☐ 📧 Email de relance - 16:00                                   │ │
│  │     🏢 Beta Inc • 💼 Projet Site Web                             │ │
│  │                                                                   │ │
│  │  ☐ ✅ Préparer présentation Gamma SA                             │ │
│  │                                                                   │ │
│  │  ☐ ✅ Mettre à jour CRM                                          │ │
│  └──────────────────────────────────────────────────────────────────┘ │
│                                                                        │
│  ┌─ 📆 Cette semaine (8) ────────────────────────────────────────────┐ │
│  │                                                                   │ │
│  │  ☐ 🤝 RDV Delta Ltd - Jeu 30 Oct 11:00                           │ │
│  │  ☐ 📞 Appel prospection - Ven 31 Oct 15:00                       │ │
│  │  ☐ ✅ Finaliser rapport mensuel - Ven 31 Oct                     │ │
│  │  ...                                                              │ │
│  └──────────────────────────────────────────────────────────────────┘ │
│                                                                        │
│  ┌─ ✅ Terminées récemment (3) ──────────────────────────────────────┐ │
│  │                                                                   │ │
│  │  ✓ Envoyer proposition Epsilon - 28 Oct                          │ │
│  │  ✓ Appel de suivi Zeta Corp - 27 Oct                             │ │
│  │  ✓ Email confirmation RDV - 26 Oct                               │ │
│  └──────────────────────────────────────────────────────────────────┘ │
│                                                                        │
└───────────────────────────────────────────────────────────────────────┘
```

---

## Interface Configuration

### ⚙️ Page de configuration

```
┌───────────────────────────────────────────────────────────────────────┐
│  [Logo] Configuration                              👤 Jean Dupont ▼   │
├───────────────────────────────────────────────────────────────────────┤
│                                                                        │
│  ⚙️ Configuration                                                      │
│                                                                        │
│  ┌────────────────────┐  ┌─────────────────────────────────────────┐ │
│  │                    │  │                                          │ │
│  │  📋 Menu           │  │  🚀 Pipelines de vente                   │ │
│  │                    │  │                                          │ │
│  │  🚀 Pipelines      │  │  ┌────────────────────────────────────┐ │ │
│  │  🏷️ Tags          │  │  │ Pipeline "Vente B2B"               │ │ │
│  │  ⚙️ Propriétés     │  │  │                                    │ │ │
│  │  👥 Utilisateurs   │  │  │  1. Prospection     (10%)          │ │ │
│  │  🔐 Sécurité       │  │  │  2. Qualification   (25%)          │ │ │
│  │  📊 Rapports       │  │  │  3. Proposition     (50%)          │ │ │
│  │  🔔 Notifications  │  │  │  4. Négociation     (75%)          │ │ │
│  │  🌍 Tenant         │  │  │  5. Clôture         (90%)          │ │ │
│  │                    │  │  │                                    │ │ │
│  │                    │  │  │  [Modifier]  [Supprimer]           │ │ │
│  │                    │  │  └────────────────────────────────────┘ │ │
│  │                    │  │                                          │ │
│  │                    │  │  ┌────────────────────────────────────┐ │ │
│  │                    │  │  │ Pipeline "Vente B2C"               │ │ │
│  │                    │  │  │                                    │ │ │
│  │                    │  │  │  1. Contact         (20%)          │ │ │
│  │                    │  │  │  2. Démonstration   (50%)          │ │ │
│  │                    │  │  │  3. Commande        (80%)          │ │ │
│  │                    │  │  │                                    │ │ │
│  │                    │  │  │  [Modifier]  [Supprimer]           │ │ │
│  │                    │  │  └────────────────────────────────────┘ │ │
│  │                    │  │                                          │ │
│  │                    │  │  [+ Nouveau pipeline]                    │ │
│  │                    │  │                                          │ │
│  └────────────────────┘  └─────────────────────────────────────────┘ │
│                                                                        │
└───────────────────────────────────────────────────────────────────────┘
```

### 🏷️ Gestion des tags

```
┌───────────────────────────────────────────────────────────────────────┐
│  ⚙️ Configuration > Tags                                               │
│                                                                        │
│  🏷️ Tags                                            [+ Nouveau tag]   │
│                                                                        │
│  🔍 Rechercher un tag...                                               │
│                                                                        │
│  ┌──────────────────────────────────────────────────────────────────┐ │
│  │  Tag              Couleur    Utilisations                    ⋮   │ │
│  ├──────────────────────────────────────────────────────────────────┤ │
│  │  🟢 VIP          #10B981     45 contacts, 12 deals          ⋮   │ │
│  │  🟡 Prospect     #F59E0B     123 contacts, 34 deals         ⋮   │ │
│  │  🔵 Client       #3B82F6     567 contacts, 89 deals         ⋮   │ │
│  │  🔴 Urgent       #EF4444     8 deals, 15 activités          ⋮   │ │
│  │  🟣 Partenaire   #8B5CF6     23 entreprises                 ⋮   │ │
│  │  ⚪ Inactif      #9CA3AF     89 contacts                    ⋮   │ │
│  │                                                                   │ │
│  └──────────────────────────────────────────────────────────────────┘ │
│                                                                        │
│  ┌─ Créer un nouveau tag ────────────────────────────────────────────┐ │
│  │                                                                   │ │
│  │  Nom du tag : [___________________]                              │ │
│  │                                                                   │ │
│  │  Couleur : 🎨 [Sélecteur de couleur] #FF5733                     │ │
│  │                                                                   │ │
│  │  Aperçu : 🟠 Nouveau tag                                         │ │
│  │                                                                   │ │
│  │                           [Annuler]  [Créer le tag]              │ │
│  └──────────────────────────────────────────────────────────────────┘ │
│                                                                        │
└───────────────────────────────────────────────────────────────────────┘
```

---

# 👨‍💻 PARTIE 4 - Guide Développeur

## Installation et configuration

### 🛠️ Pré-requis

**Environnement :**
- PHP 8.2 ou supérieur
- MySQL 8.0
- Composer 2.x
- Node.js 18+ (pour assets)

**Extensions PHP requises :**
```
php-mysql
php-xml
php-intl
php-mbstring
php-curl
php-zip
```

---

### 📦 Installation

**1. Cloner le projet**
```bash
git clone https://github.com/votre-org/crm.git
cd crm
```

**2. Installer les dépendances**
```bash
composer install
```

**3. Configurer l'environnement**
```bash
cp .env .env.local
```

Éditez `.env.local` :
```env
DATABASE_URL="mysql://user:password@127.0.0.1:3306/crm"
TENANT_DEFAULT=DEMO
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=votre_passphrase
```

**4. Générer les clés JWT**
```bash
php bin/console lexik:jwt:generate-keypair
```

**5. Créer la base de données**
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

**6. Charger les fixtures (optionnel)**
```bash
php bin/console doctrine:fixtures:load
# Ou
php bin/console app:init-fixtures
```

**7. Installer les assets**
```bash
php bin/console importmap:install
php bin/console assets:install
```

**8. Démarrer le serveur**
```bash
symfony serve -d
# Ou
php -S localhost:8000 -t public
```

L'application est accessible sur `http://localhost:8000`

---

### 🌍 Configuration Multi-tenant

**Créer un nouveau tenant :**

1. Définir le tenant via CLI :
```bash
php bin/console app:tenant:set ci
```

2. Créer la base tenant :
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

3. Charger les données initiales :
```bash
php bin/console app:init-fixtures
```

**Exécuter une commande pour tous les tenants :**
```bash
php bin/console app:tenant:foreach doctrine:migrations:migrate
```

---

## Structure du code

### 📁 Arborescence

```
crm/
├── config/              # Configuration Symfony
│   ├── packages/        # Config bundles
│   ├── routes/          # Routes
│   └── services.yaml    # Services
├── public/              # Point d'entrée web
│   └── index.php
├── src/
│   ├── Command/         # Commandes console
│   ├── Controller/      # Contrôleurs API
│   ├── Entity/          # Entités Doctrine
│   ├── Managers/        # Logique métier
│   ├── MultiTenancy/    # Système multi-tenant
│   ├── Repository/      # Repositories Doctrine
│   ├── Security/        # Authentification & autorisation
│   └── Kernel.php
├── tests/               # Tests PHPUnit
├── var/                 # Cache, logs
├── vendor/              # Dépendances
├── .env                 # Config environnement
└── composer.json
```

---

### 🏗️ Architecture des composants

**Entités** (`src/Entity/`)
- Modèles de données avec annotations Doctrine
- Traits partagés (UserObjectTrait, TagTrait, etc.)

**Contrôleurs** (`src/Controller/`)
- API REST
- Routes avec attributs PHP 8
- Retour JSON avec groupes de sérialisation

**Managers** (`src/Managers/`)
- Logique métier
- Validation des règles
- Orchestration des opérations

**Repositories** (`src/Repository/`)
- Requêtes personnalisées
- Accès aux données

**Multi-Tenancy** (`src/MultiTenancy/`)
- Switcher : changement de tenant
- ConnectionWrapper : gestion connexions DB
- KernelListener : détection tenant par requête

---

### 🔧 Patterns utilisés

**Repository Pattern**
```php
class ContactRepository extends ServiceEntityRepository
{
    public function findByCompany(Company $company): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.company = :company')
            ->setParameter('company', $company)
            ->getQuery()
            ->getResult();
    }
}
```

**Manager Pattern**
```php
class ContactManager extends Manager
{
    public function create(array $data): Contact
    {
        // Validation
        // Logique métier
        // Persist & flush
        return $contact;
    }
}
```

**Trait Pattern**
```php
trait UserObjectTrait
{
    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $created = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $createdBy = null;

    // Getters/setters...
}
```

---

## Commandes disponibles

### 📋 Commandes Symfony

**Tests**
```bash
# Tous les tests
php bin/phpunit

# Test spécifique
php bin/phpunit tests/Controller/ContactControllerTest.php

# Avec couverture
php bin/phpunit --coverage-html coverage
```

**Base de données**
```bash
# Créer migration
php bin/console doctrine:migrations:diff

# Exécuter migrations
php bin/console doctrine:migrations:migrate

# Charger fixtures
php bin/console doctrine:fixtures:load

# Init fixtures (custom)
php bin/console app:init-fixtures
```

**Cache**
```bash
# Vider cache
php bin/console cache:clear

# Cache de production
php bin/console cache:clear --env=prod
```

**Assets**
```bash
# Installer assets
php bin/console assets:install

# Installer importmap
php bin/console importmap:install
```

---

### 🛠️ Commandes personnalisées

**Debug application**
```bash
php bin/console app:debug
```

**Générer classes JS**
```bash
php bin/console app:generate-js-class
```

**Générer carte d'accès**
```bash
php bin/console app:generate-access-map
```

**Gestion tenant**
```bash
# Définir tenant
php bin/console app:tenant:set ci

# Obtenir tenant actuel
php bin/console app:tenant:get

# Exécuter pour tous les tenants
php bin/console app:tenant:foreach <commande>
```

---

### 📦 Composer

```bash
# Installer dépendances
composer install

# Mettre à jour
composer update

# Optimiser autoload
composer dump-autoload

# Installer en prod
composer install --no-dev --optimize-autoloader
```

---

## Tests et développement

### ✅ Tests

**Lancer les tests**
```bash
php bin/phpunit
```

**Structure des tests**
```
tests/
├── Controller/
│   ├── ContactControllerTest.php
│   ├── CompanyControllerTest.php
│   └── DealControllerTest.php
├── Manager/
│   └── ContactManagerTest.php
└── bootstrap.php
```

**Exemple de test**
```php
class ContactControllerTest extends WebTestCase
{
    public function testListContacts(): void
    {
        $client = static::createClient();
        $client->request('GET', '/contact');

        $this->assertResponseIsSuccessful();
        $this->assertJson($client->getResponse()->getContent());
    }
}
```

---

### 🐛 Debugging

**Afficher les logs**
```bash
tail -f var/log/dev.log
```

**Debug barre Symfony**
- Activée en mode dev
- Profiler disponible

**Xdebug**
Configuration dans `.env.local` :
```env
XDEBUG_MODE=debug
```

---

## Référence API

### 🔐 Authentication

**Login**
```http
POST /login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password123"
}

Response:
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbG..."
}
```

**Utiliser le token**
```http
GET /contact
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbG...
```

---

### 📇 Contacts

**Lister les contacts**
```http
GET /contact
Authorization: Bearer {token}

Response:
{
  "status": "success",
  "data": [
    {
      "id": "uuid",
      "firstName": "Jean",
      "lastName": "Dupont",
      "email": "jean@example.com"
    }
  ]
}
```

**Créer un contact**
```http
POST /contact
Authorization: Bearer {token}
Content-Type: application/json

{
  "firstName": "Jean",
  "lastName": "Dupont",
  "email": "jean@example.com",
  "phone": "+33612345678"
}
```

**Obtenir un contact**
```http
GET /contact/{id}
Authorization: Bearer {token}
```

**Modifier un contact**
```http
PUT /contact/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "firstName": "Jean",
  "lastName": "Durand"
}
```

**Supprimer un contact**
```http
DELETE /contact/{id}
Authorization: Bearer {token}
```

---

### 🏢 Entreprises

**Endpoints similaires aux contacts :**
- `GET /company` - Liste
- `POST /company` - Création
- `GET /company/{id}` - Détail
- `PUT /company/{id}` - Modification
- `DELETE /company/{id}` - Suppression

---

### 💼 Deals

**Lister les deals**
```http
GET /deal
Authorization: Bearer {token}

Query params:
- status: win|lost|null
- step: {stepId}
- contact: {contactId}
```

**Créer un deal**
```http
POST /deal
Authorization: Bearer {token}
Content-Type: application/json

{
  "title": "Projet CRM",
  "amount": 5000000,  // en centimes
  "contact": "contact-uuid",
  "company": "company-uuid",
  "step": "step-uuid",
  "expectedCloseDate": "2025-12-31"
}
```

**Changer l'étape**
```http
PUT /deal/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "step": "new-step-uuid"
}
```

**Marquer comme gagné/perdu**
```http
PUT /deal/{id}/status
Authorization: Bearer {token}
Content-Type: application/json

{
  "status": "win"  // ou "lost"
}
```

---

### 📅 Activités

**Lister les activités**
```http
GET /activity
Authorization: Bearer {token}

Query params:
- type: call|meeting|email|task|deadline
- status: todo|done|cancelled
- assignedTo: {userId}
- date: YYYY-MM-DD
```

**Créer une activité**
```http
POST /activity
Authorization: Bearer {token}
Content-Type: application/json

{
  "title": "Appel client",
  "type": "call",
  "date": "2025-10-29T10:00:00",
  "duration": 30,
  "contact": "contact-uuid",
  "deal": "deal-uuid"
}
```

**Marquer comme terminée**
```http
PUT /activity/{id}/complete
Authorization: Bearer {token}
```

---

### 🚀 Pipelines

**Lister les pipelines**
```http
GET /pipeline
Authorization: Bearer {token}
```

**Créer un pipeline**
```http
POST /pipeline
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Vente B2B",
  "steps": [
    {"name": "Prospection", "probability": 10, "position": 0},
    {"name": "Qualification", "probability": 25, "position": 1}
  ]
}
```

---

### 🏷️ Tags

**Lister les tags**
```http
GET /tag
Authorization: Bearer {token}
```

**Créer un tag**
```http
POST /tag
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "VIP",
  "color": "#10B981"
}
```

---

### 📥 Import/Export

**Importer contacts**
```http
POST /contact/import
Authorization: Bearer {token}
Content-Type: multipart/form-data

file: contacts.csv
```

**Exporter contacts**
```http
GET /contact/export?format=csv
Authorization: Bearer {token}
```

---

# 🔄 PARTIE 5 - Workflows Métier

## Scénarios d'utilisation

### 📊 Scénario 1 : Acquisition d'un nouveau prospect

**Contexte :**
Un commercial rencontre un prospect lors d'un salon professionnel.

**Workflow :**

1. **Créer le contact**
   - Nom, prénom, email, téléphone
   - Ajouter tag "Prospect chaud"
   - Noter l'entreprise

2. **Créer l'entreprise (si nouvelle)**
   - Nom, secteur, site web
   - Lier le contact

3. **Créer une activité de suivi**
   - Type : Appel téléphonique
   - Date : J+2
   - Objectif : Qualifier le besoin

4. **Créer un deal**
   - Étape : Prospection
   - Montant estimé
   - Date de clôture prévue

5. **Planifier prochaines étapes**
   - Réunion de découverte
   - Envoi de documentation
   - Démonstration produit

---

### 💼 Scénario 2 : Faire progresser une affaire

**Contexte :**
Une affaire passe de "Qualification" à "Proposition".

**Workflow :**

1. **Mettre à jour le deal**
   - Glisser-déposer vers "Proposition"
   - Probabilité passe à 50%

2. **Créer activités associées**
   - Préparer proposition commerciale
   - Planifier présentation
   - Envoyer devis

3. **Ajouter notes**
   - Besoins identifiés
   - Budget confirmé
   - Décisionnaires

4. **Notifier l'équipe**
   - Ajouter participants au deal
   - Partager le contexte

5. **Suivre les activités**
   - Marquer comme terminées
   - Ajuster le montant si besoin

---

### 🎯 Scénario 3 : Clôture d'une affaire gagnée

**Contexte :**
Le client signe le contrat.

**Workflow :**

1. **Marquer le deal comme gagné** 🎉
   - Statut : Win
   - Date de clôture effective
   - Montant final

2. **Créer activités post-vente**
   - Onboarding client
   - Formation équipe
   - Suivi satisfaction

3. **Mettre à jour les tags**
   - Retirer "Prospect"
   - Ajouter "Client actif"

4. **Créer opportunités d'upsell**
   - Nouveau deal "Upsell"
   - Pipeline "Clients existants"

5. **Analyser la performance**
   - Durée du cycle de vente
   - Taux de conversion
   - CA généré

---

### 📅 Scénario 4 : Gestion quotidienne des activités

**Contexte :**
Début de journée d'un commercial.

**Workflow :**

1. **Consulter le dashboard**
   - Activités du jour
   - Deals à relancer
   - Métriques de performance

2. **Traiter les activités en retard**
   - Reprogrammer ou clôturer
   - Ajouter notes explicatives

3. **Exécuter les activités du jour**
   - Appels planifiés
   - Réunions
   - Emails de suivi

4. **Marquer les activités terminées**
   - Cocher comme "Done"
   - Ajouter compte-rendu

5. **Planifier le lendemain**
   - Créer nouvelles activités
   - Préparer les réunions

---

## Bonnes pratiques

### ✅ Saisie des données

**DO ✓**
- Remplir tous les champs obligatoires
- Utiliser des tags cohérents
- Ajouter des notes contextuelles
- Lier contacts/entreprises/deals
- Mettre à jour régulièrement

**DON'T ✗**
- Créer des doublons
- Laisser des champs vides
- Oublier de lier les entités
- Négliger les tags
- Abandonner des deals sans statut

---

### 🎯 Gestion du pipeline

**DO ✓**
- Définir des étapes claires
- Mettre à jour régulièrement les probabilités
- Déplacer les deals rapidement
- Clôturer (gagné/perdu) les deals terminés
- Analyser les raisons des pertes

**DON'T ✗**
- Laisser des deals stagner
- Surestimer les probabilités
- Garder des deals "zombies"
- Oublier de clôturer
- Ignorer les deals perdus

---

### 📅 Planification des activités

**DO ✓**
- Planifier à l'avance
- Définir des durées réalistes
- Assigner clairement les responsables
- Lier aux contacts/deals
- Ajouter descriptions détaillées

**DON'T ✗**
- Sur-réserver son agenda
- Créer des activités floues
- Oublier de les marquer comme terminées
- Ne pas ajouter de compte-rendu
- Ignorer les activités en retard

---

### 🏷️ Utilisation des tags

**DO ✓**
- Créer des tags par catégorie (statut, secteur, priorité)
- Utiliser des couleurs cohérentes
- Limiter le nombre de tags par objet (3-5 max)
- Nettoyer régulièrement les tags obsolètes

**DON'T ✗**
- Créer trop de tags similaires
- Utiliser des tags ambigus
- Multiplier les tags sur un même objet
- Garder des tags inutilisés

---

### 🔍 Recherche et filtres

**DO ✓**
- Utiliser les filtres avancés
- Sauvegarder les recherches fréquentes
- Combiner plusieurs critères
- Exporter pour analyses

**DON'T ✗**
- Parcourir manuellement de longues listes
- Négliger les fonctions de tri
- Ignorer les filtres disponibles

---

## Cas d'usage réels

### 🏢 Cas 1 : PME de services B2B

**Contexte :**
Entreprise de 10 commerciaux, vente de prestations de conseil.

**Configuration :**
- **Pipeline** : Découverte → Analyse → Proposition → Négociation → Signature
- **Tags** : Par secteur (Finance, Industrie, Retail) + par priorité (A, B, C)
- **Activités** : Principalement réunions et appels

**Usage quotidien :**
- Matin : Revue du dashboard et activités du jour
- Journée : Enregistrement des interactions après chaque rendez-vous
- Soir : Planification du lendemain et mise à jour des deals

**Résultats :**
- ✅ Taux de conversion : +25%
- ✅ Cycle de vente : -15 jours
- ✅ Visibilité pipeline : 100%

---

### 🛒 Cas 2 : E-commerce B2C

**Contexte :**
Équipe de 5 personnes, vente en ligne + SAV.

**Configuration :**
- **Pipeline** : Lead → Qualification → Commande → Livraison
- **Tags** : Par source (Web, Téléphone, Email) + par produit
- **Activités** : Emails automatisés + appels SAV

**Usage quotidien :**
- Import automatique des leads depuis le site web
- Qualification rapide (hot/cold)
- Suivi des commandes importantes
- Relances SAV

**Résultats :**
- ✅ Temps de réponse : -40%
- ✅ Satisfaction client : +30%
- ✅ Upsell : +20%

---

### 🏭 Cas 3 : Industrie - Grands comptes

**Contexte :**
3 commerciaux grands comptes, cycles de vente longs (6-12 mois).

**Configuration :**
- **Pipeline** : Prospection → RDV Direction → Audit → POC → Négociation → Contrat
- **Tags** : Par région + par décisionnaire + par budget
- **Activités** : Réunions stratégiques + présentations + démos

**Usage quotidien :**
- Suivi détaillé de chaque étape
- Historique complet des interactions
- Collaboration entre commerciaux et techniques
- Reporting mensuel direction

**Résultats :**
- ✅ Visibilité pipeline : 6 mois d'avance
- ✅ Prévisions : ±5% précision
- ✅ Coordination équipe : optimale

---

### 🎓 Cas 4 : Organisme de formation

**Contexte :**
15 conseillers formation, catalogue de 50 formations.

**Configuration :**
- **Pipeline** : Contact → Analyse besoin → Devis → Inscription → Formation
- **Tags** : Par type formation + par entreprise + par urgence
- **Activités** : Appels + emails + sessions de formation

**Usage quotidien :**
- Gestion des demandes entrantes
- Personnalisation des parcours
- Suivi post-formation
- Relances pour nouvelles sessions

**Résultats :**
- ✅ Taux de remplissage : +35%
- ✅ Satisfaction : 4.5/5
- ✅ Renouvellements : +40%

---

# 📚 Annexes

## Glossaire

**CRM** : Customer Relationship Management - Gestion de la Relation Client

**Deal** : Opportunité commerciale, affaire en cours

**Pipeline** : Processus de vente structuré en étapes

**Lead** : Prospect potentiel non qualifié

**Prospect** : Contact qualifié avec besoin identifié

**Tenant** : Organisation/entreprise dans le système multi-tenant

**Soft Delete** : Suppression logique (données masquées mais conservées)

**UUID** : Identifiant unique universel

**JWT** : JSON Web Token - méthode d'authentification

**API** : Application Programming Interface

**ORM** : Object-Relational Mapping (Doctrine)

---

## FAQ

**Q: Comment restaurer un contact supprimé ?**
R: Seuls les administrateurs peuvent restaurer depuis la corbeille.

**Q: Peut-on avoir plusieurs pipelines ?**
R: Oui, vous pouvez créer autant de pipelines que nécessaire.

**Q: Les données sont-elles sécurisées ?**
R: Oui, isolation complète par tenant + authentification JWT + audit trail complet.

**Q: Comment importer mes données existantes ?**
R: Via import CSV/Excel depuis l'interface ou API.

**Q: Peut-on personnaliser les champs ?**
R: Oui, via le système de propriétés personnalisées.

**Q: Y a-t-il une limite de contacts ?**
R: Non, le système est conçu pour scaler.

---

## Ressources de support

**Documentation technique :**
- API_DOCUMENTATION.md
- CLAUDE.md

**Support :**
- Email : support@votre-crm.com
- Ticket : https://support.votre-crm.com

**Communauté :**
- Forum : https://forum.votre-crm.com
- Chat : Slack workspace

**Formation :**
- Vidéos tutoriels : https://learn.votre-crm.com
- Webinaires mensuels

---

## Historique des versions

**Version 1.0 - Octobre 2025**
- Version initiale de la documentation
- Couverture complète du système
- Documentation métier + technique + UI + développeur + workflows

---

# 🎉 Fin de la documentation

Cette documentation complète couvre tous les aspects du système CRM Multi-Tenant. Pour toute question ou suggestion d'amélioration, n'hésitez pas à contacter l'équipe de support.

**Bonne utilisation ! 🚀**
