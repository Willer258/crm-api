# 🎨 ROADMAP FRONTEND - CRM SaaS MVP

**Stack:** Next.js 14 + TypeScript + Tailwind CSS + Shadcn/ui

**Durée Estimée:** 9-10 semaines

**API Backend:** Symfony 7.2 CRM-API

---

## 📊 VUE D'ENSEMBLE

### Objectif MVP
Créer une application CRM moderne, responsive et performante avec les fonctionnalités essentielles :
- Gestion complète des contacts, entreprises et deals
- Pipeline visuel (Kanban)
- Activités et calendrier
- Dashboard avec statistiques
- Responsive mobile-first

### Stack Technique
```
Frontend:
- Next.js 14 (App Router)
- TypeScript
- Tailwind CSS
- Shadcn/ui (composants)
- React Query (data fetching)
- Zustand (state management)
- Axios (API calls)
- React Hook Form + Zod (formulaires)
- Lucide Icons

Outils:
- ESLint + Prettier
- Vitest (tests)
- Playwright (E2E)
- Vercel (déploiement)
```

---

## 🔴 PHASE 1: FONDATIONS (Semaine 1)

### Semaine 1 - Setup Projet

#### Jour 1-2: Initialisation
- [ ] Créer projet Next.js 14 avec TypeScript
  ```bash
  npx create-next-app@latest crm-frontend --typescript --tailwind --app
  cd crm-frontend
  ```
- [ ] Structure dossiers
  ```
  /app
    /auth
    /dashboard
    /contacts
    /companies
    /deals
    /activities
    /settings
  /components
    /ui (shadcn)
    /features
    /layouts
  /lib
    /api
    /hooks
    /utils
  /types
  /stores
  ```
- [ ] Configuration ESLint + Prettier
- [ ] Configuration Tailwind (thème custom)
- [ ] Variables d'environnement (.env.local)

#### Jour 3: Configuration API
- [ ] Installer dépendances
  ```bash
  npm install axios @tanstack/react-query zustand
  npm install react-hook-form @hookform/resolvers zod
  npm install lucide-react date-fns
  ```
- [ ] Créer client API (`lib/api/client.ts`)
  - Configuration Axios
  - Intercepteurs JWT
  - Gestion erreurs
  - Refresh token
- [ ] Types TypeScript pour entités
  ```typescript
  // types/contact.ts
  // types/company.ts
  // types/deal.ts
  // types/activity.ts
  // types/user.ts
  ```

#### Jour 4-5: Shadcn/ui + Composants Base
- [ ] Installer Shadcn/ui
  ```bash
  npx shadcn-ui@latest init
  ```
- [ ] Installer composants essentiels
  ```bash
  npx shadcn-ui@latest add button input select textarea
  npx shadcn-ui@latest add dialog sheet popover dropdown-menu
  npx shadcn-ui@latest add table card badge avatar
  npx shadcn-ui@latest add form label checkbox radio
  npx shadcn-ui@latest add toast alert skeleton
  npx shadcn-ui@latest add tabs accordion
  ```
- [ ] Créer composants réutilisables de base
  - `LoadingSkeleton`
  - `EmptyState`
  - `ConfirmDialog`

---

## 🔴 PHASE 2: AUTHENTIFICATION (Semaine 1-2)

### Semaine 2 - Auth System

#### Jour 1-2: Pages Auth
- [ ] **Page Login** (`/auth/login`)
  - Formulaire email/password
  - Validation Zod
  - Remember me
  - Forgot password link
  - Messages erreur
  - Loading state

- [ ] **Page Register** (`/auth/register`)
  - Formulaire inscription
  - Validation password strength
  - Terms checkbox
  - Auto-login après inscription

- [ ] **Pages Password**
  - Forgot password
  - Reset password

#### Jour 3: Auth Context & Hooks
- [ ] `AuthProvider` (Context)
  ```typescript
  - État user
  - Token JWT
  - login()
  - logout()
  - register()
  - Auto-refresh token
  - Persist session
  ```
- [ ] Hooks Auth
  - `useAuth()`
  - `useUser()`
  - `useLogin()`
  - `useLogout()`
  - `useRegister()`

#### Jour 4: Protection Routes
- [ ] Middleware authentification
- [ ] ProtectedRoute wrapper
- [ ] Redirection si non connecté
- [ ] Gestion permissions (future)

#### Jour 5: Intégration API Auth
- [ ] `POST /auth/login`
- [ ] `POST /auth/register`
- [ ] `POST /auth/refresh`
- [ ] `POST /auth/logout`
- [ ] `GET /auth/me`
- [ ] Tests end-to-end auth

---

## 🔴 PHASE 3: LAYOUT & NAVIGATION (Semaine 2)

### Semaine 2 (suite) - Layout Principal

#### Jour 1-2: DashboardLayout
- [ ] **Sidebar Navigation**
  - Logo + nom app
  - Menu items avec icons
    ```
    📊 Dashboard
    👤 Contacts
    🏢 Entreprises
    💼 Deals
    📅 Activités
    📈 Pipeline
    🏷️ Tags
    ⚙️ Paramètres
    ```
  - Active state
  - Collapsible
  - Responsive (drawer mobile)

#### Jour 3: TopBar
- [ ] **Top Bar Component**
  - Breadcrumbs dynamiques
  - Search bar (trigger ⌘K)
  - Notifications bell + badge
  - User menu dropdown
    - Mon profil
    - Paramètres
    - Se déconnecter
  - Dark mode toggle (optionnel MVP)

#### Jour 4-5: Navigation Polish
- [ ] Animations transitions
- [ ] Mobile menu (Sheet)
- [ ] Breadcrumbs automatiques
- [ ] Active route highlighting
- [ ] Quick actions button

---

## 🔴 PHASE 4: DASHBOARD (Semaine 3)

### Semaine 3 - Dashboard & Analytics

#### Jour 1-2: Stats Cards
- [ ] **4 Cards Statistiques**
  - Total contacts (+ évolution %)
  - Total entreprises (+ évolution %)
  - Deals actifs (+ valeur)
  - Taux conversion (+ tendance)
- [ ] Loading skeletons
- [ ] Icons colorés
- [ ] Animation entrée

#### Jour 3: Graphiques
- [ ] Installer Recharts
  ```bash
  npm install recharts
  ```
- [ ] **Charts**
  - Deals par mois (bar chart)
  - Pipeline (funnel chart)
  - Sources contacts (pie chart)
  - Activités (line chart)

#### Jour 4: Widgets
- [ ] **Activités Récentes**
  - Liste 10 dernières
  - Avatar + nom + action
  - Timestamp relatif
  - Lien vers détail

- [ ] **Deals à Gagner**
  - Deals proches deadline
  - Valeur + probabilité
  - Actions rapides

- [ ] **Tâches du Jour**
  - Activités du jour
  - Checkbox completion
  - Création rapide

#### Jour 5: API Integration
- [ ] Créer endpoints backend (si nécessaire)
- [ ] Hooks React Query
  - `useDashboardStats()`
  - `useRecentActivities()`
  - `useUpcomingDeals()`
- [ ] Cache configuration
- [ ] Error handling

---

## 🔴 PHASE 5: CONTACTS (Semaine 3-4)

### Semaine 3 (suite) + Semaine 4 - Module Contacts

#### Jour 1-2: Page Liste Contacts
- [ ] **Tableau Contacts** (`/contacts`)
  - DataTable composant réutilisable
  - Colonnes:
    - Avatar
    - Nom complet
    - Email
    - Téléphone
    - Entreprise
    - Tags
    - Dernière activité
    - Actions
  - Tri par colonne
  - Pagination (25/50/100)
  - Sélection multiple
  - Loading skeleton

#### Jour 2-3: Filtres & Actions
- [ ] **Barre Actions**
  - Bouton "Nouveau contact"
  - Search bar
  - Bouton "Importer CSV"
  - Bouton "Exporter"
  - Actions groupées

- [ ] **Filtres Avancés**
  - Par entreprise
  - Par tags
  - Par manager
  - Par date création
  - Sauvegarder filtre (optionnel)

#### Jour 4: Page Détail Contact
- [ ] **Header Détail** (`/contacts/[id]`)
  - Avatar grande taille
  - Nom + titre
  - Entreprise (lien)
  - Tags
  - Boutons actions

- [ ] **Tabs Navigation**
  - Aperçu (default)
  - Activités
  - Deals
  - Fichiers
  - Notes

#### Jour 5: Tabs Content
- [ ] **Tab Aperçu**
  - Section Informations (card)
  - Section Entreprise (card)
  - Section Statistiques (card)

- [ ] **Tab Activités**
  - Timeline
  - Bouton "Nouvelle activité"
  - Filtres

- [ ] **Tab Deals**
  - Liste deals liés
  - Bouton "Nouveau deal"

#### Semaine 4 Jour 1-2: Modal Création
- [ ] **Dialog "Nouveau Contact"**
  - Formulaire complet:
    ```
    - Prénom *
    - Nom *
    - Email *
    - Téléphone
    - Entreprise (autocomplete)
    - Poste
    - Source
    - Tags
    - Manager
    - Propriétés custom
    ```
  - Validation Zod
  - Upload photo
  - Détection doublons

#### Jour 3: Import Contacts
- [ ] **Page Import** (`/contacts/import`)
  - Upload CSV/Excel
  - Drag & drop
  - Template téléchargeable
  - Preview + mapping colonnes
  - Options (ignorer doublons, etc.)
  - Progress bar
  - Rapport erreurs

#### Jour 4-5: Hooks & Polish
- [ ] Hooks React Query
  - `useContacts(filters)`
  - `useContact(id)`
  - `useCreateContact()`
  - `useUpdateContact(id)`
  - `useDeleteContact(id)`
  - `useImportContacts()`
  - `useMergeContacts()`
- [ ] Tests
- [ ] Optimisations performance
- [ ] Vue cartes (alternative tableau)

---

## 🔴 PHASE 6: ENTREPRISES (Semaine 4)

### Semaine 4 (suite) - Module Companies

#### Jour 1-2: Liste & Détail Entreprises
- [ ] **Page Liste** (`/companies`)
  - Tableau similaire contacts
  - Colonnes:
    - Logo
    - Nom
    - Industrie
    - Nb contacts
    - Nb deals
    - Valeur pipeline
    - Actions
  - Filtres par industrie, taille

- [ ] **Page Détail** (`/companies/[id]`)
  - Header entreprise
  - Tabs (Aperçu, Contacts, Deals, Activités, Fichiers, Notes)

#### Jour 3: Modal Création
- [ ] **Dialog "Nouvelle Entreprise"**
  - Formulaire:
    ```
    - Nom *
    - Industrie
    - Site web
    - Téléphone
    - Adresse
    - SIRET/SIREN
    - Taille
    - Logo upload
    - Propriétés custom
    ```

#### Jour 4: Hooks & API
- [ ] `useCompanies()`
- [ ] `useCompany(id)`
- [ ] `useCreateCompany()`
- [ ] `useUpdateCompany()`
- [ ] `useDeleteCompany()`

#### Jour 5: Polish & Tests
- [ ] Optimisations
- [ ] Tests
- [ ] Empty states
- [ ] Error handling

---

## 🔴 PHASE 7: DEALS (Semaine 5)

### Semaine 5 - Module Deals (Cœur du CRM)

#### Jour 1-2: Vue Kanban Pipeline
- [ ] **Page Pipeline** (`/deals`)
  - Vue Kanban par défaut
  - Colonnes = Étapes pipeline
    ```
    [Prospect] → [Qualification] → [Proposition]
    → [Négociation] → [Gagné/Perdu]
    ```
  - Cards deals:
    - Nom
    - Contact/Entreprise
    - Valeur
    - Probabilité %
    - Avatar owner
    - Deadline
  - Drag & drop entre colonnes (react-beautiful-dnd)
  - Animations smooth

#### Jour 2-3: Filtres & Vues Alternatives
- [ ] **Barre Actions**
  - Bouton "Nouveau deal"
  - Select pipeline
  - Filtres (owner, date, valeur, tags)

- [ ] **Vue Liste** (alternative)
  - Tableau deals
  - Tri par valeur, date, étape

- [ ] **Vue Forecast** (optionnel MVP)
  - Prévisions revenus
  - Chart projection

#### Jour 3-4: Page Détail Deal
- [ ] **Header Deal** (`/deals/[id]`)
  - Nom deal
  - Valeur grande taille
  - Étape actuelle (badge)
  - Probabilité (progress bar)
  - Boutons:
    - Éditer
    - Marquer gagné ✓
    - Marquer perdu ✗
    - Supprimer

- [ ] **Sections**
  - Informations deal (card)
  - Progression pipeline (stepper)
  - Participants
  - Notes
  - Activités timeline
  - Fichiers

#### Jour 4: Modal Création Deal
- [ ] **Dialog "Nouveau Deal"**
  - Formulaire:
    ```
    - Nom deal *
    - Contact * (autocomplete)
    - Entreprise (auto)
    - Pipeline *
    - Étape *
    - Valeur *
    - Devise
    - Probabilité %
    - Date clôture
    - Owner
    - Tags
    - Description
    ```

#### Jour 5: Actions & Hooks
- [ ] **Actions Deals**
  - Marquer gagné (dialog + animation 🎉)
  - Marquer perdu (raison + commentaire)
  - Changer étape (drag & drop ou select)

- [ ] **Hooks**
  - `useDeals(filters)`
  - `useDeal(id)`
  - `useCreateDeal()`
  - `useUpdateDeal()`
  - `useChangeDealStep()`
  - `useWinDeal()`
  - `useLoseDeal()`
  - `useDeleteDeal()`

---

## 🔴 PHASE 8: ACTIVITÉS (Semaine 5-6)

### Semaine 5 (fin) + Semaine 6 - Module Activités

#### Jour 1-2: Page Liste Activités
- [ ] **Vue Liste** (`/activities`)
  - Tableau activités
  - Colonnes:
    - Type (icon)
    - Titre
    - Contact/Entreprise
    - Date & heure
    - Owner
    - Statut (À faire/Fait)
    - Actions
  - Filtres:
    - Par type
    - Par statut
    - Par owner
    - Par date

#### Jour 2-3: Vue Calendrier
- [ ] **Calendar View**
  - Installer library
    ```bash
    npm install react-big-calendar
    ```
  - Calendrier mensuel
  - Activités sur jours
  - Click jour → liste
  - Drag & drop pour déplacer
  - Vue semaine/jour (optionnel)

#### Jour 3-4: Détail & Création
- [ ] **Page Détail** (`/activities/[id]`)
  - Informations activité
  - Liens vers contact/entreprise/deal
  - Notes
  - Éditer/Supprimer

- [ ] **Dialog "Nouvelle Activité"**
  - Type * (Appel, Email, Meeting, Tâche, Démo)
  - Titre *
  - Date & heure *
  - Durée
  - Contact (autocomplete)
  - Entreprise (autocomplete)
  - Deal (autocomplete)
  - Owner
  - Notes
  - Statut

#### Jour 4-5: Quick Actions & Hooks
- [ ] **Quick Create**
  - Bouton "+" flottant
  - Quick dialog
  - Création depuis fiche contact/deal

- [ ] **Hooks**
  - `useActivities(filters)`
  - `useActivity(id)`
  - `useCreateActivity()`
  - `useUpdateActivity()`
  - `useDeleteActivity()`
  - `useCompleteActivity()`

---

## 🟠 PHASE 9: PARAMÈTRES (Semaine 6)

### Semaine 6 (suite) - Settings

#### Jour 1: Pipelines & Tags
- [ ] **Page Pipelines** (`/settings/pipelines`)
  - Liste pipelines
  - Créer/Éditer pipeline
  - Gestion étapes (drag & drop)
  - Probabilités par étape
  - Couleurs

- [ ] **Page Tags** (`/settings/tags`)
  - Liste tags
  - Créer tag (nom + couleur)
  - Éditer/Supprimer
  - Utilisation count

#### Jour 2: Profil Utilisateur
- [ ] **Page Profil** (`/profile`)
  - Section Informations
    - Avatar upload
    - Nom/Prénom
    - Email
    - Téléphone
    - Poste
  - Section Mot de Passe
    - Ancien password
    - Nouveau password
    - Validation strength
  - Section Préférences
    - Langue
    - Fuseau horaire
    - Format date
    - Notifications

#### Jour 3-4: Paramètres App
- [ ] **Page Settings** (`/settings`)
  - Sidebar navigation:
    ```
    - Général
    - Équipe
    - Pipelines
    - Tags
    - Propriétés Custom
    - Intégrations (future)
    ```

- [ ] **Tab Général**
  - Nom entreprise/tenant
  - Logo
  - Devise par défaut
  - Fuseau horaire

- [ ] **Tab Équipe**
  - Liste utilisateurs (tableau)
  - Inviter utilisateur (email)
  - Rôles (Admin, Manager, User)
  - Actions (éditer, désactiver, supprimer)

- [ ] **Tab Propriétés Custom**
  - Liste par entité
  - Créer propriété:
    - Nom
    - Type (texte, nombre, date, select)
    - Options
    - Obligatoire

#### Jour 5: Hooks Settings
- [ ] `usePipelines()`
- [ ] `useCreatePipeline()`
- [ ] `useTags()`
- [ ] `useCreateTag()`
- [ ] `useProfile()`
- [ ] `useUpdateProfile()`
- [ ] `useChangePassword()`
- [ ] `useTeamMembers()`
- [ ] `useInviteUser()`

---

## 🟠 PHASE 10: FICHIERS & NOTES (Semaine 6-7)

### Semaine 7 - Gestion Fichiers & Notes

#### Jour 1-2: Upload Fichiers
- [ ] **Upload Component**
  - Drag & drop zone
  - Click to upload
  - Multiple files
  - Progress bar
  - Validation taille/type
  - Preview images

- [ ] **Liste Fichiers**
  - Grid ou liste
  - Thumbnail preview
  - Nom + taille + date
  - Download button
  - Delete button
  - Filtres par type

#### Jour 3: Notes
- [ ] **Éditeur Notes**
  - Rich text editor (TipTap)
    ```bash
    npm install @tiptap/react @tiptap/starter-kit
    ```
  - Formatting basique
  - Mentions @user (optionnel)
  - Auto-save draft

- [ ] **Liste Notes**
  - Cards notes
  - Auteur + date
  - Éditer/Supprimer
  - Pinned notes

#### Jour 4: Hooks & API
- [ ] `useFiles(entityType, entityId)`
- [ ] `useUploadFile()`
- [ ] `useDeleteFile()`
- [ ] `useNotes(entityType, entityId)`
- [ ] `useCreateNote()`
- [ ] `useUpdateNote()`
- [ ] `useDeleteNote()`

#### Jour 5: Integration
- [ ] Intégrer dans pages détail:
  - Contact
  - Entreprise
  - Deal
- [ ] Tests
- [ ] Optimisations

---

## 🟢 PHASE 11: SEARCH & NOTIFICATIONS (Semaine 7)

### Semaine 7 (suite) - Features Transversales

#### Jour 1-2: Search Global
- [ ] **Command Palette (⌘K)**
  - Dialog search (cmdk library)
    ```bash
    npm install cmdk
    ```
  - Trigger: ⌘K / Ctrl+K
  - Recherche temps réel (debounced)
  - Résultats groupés:
    ```
    📞 Contacts
    🏢 Entreprises
    💼 Deals
    📅 Activités
    ```
  - Navigation clavier
  - Highlight match
  - Raccourcis actions:
    ```
    > Nouveau contact
    > Nouveau deal
    > Nouvelle activité
    ```

- [ ] **Search Bar Header**
  - Input dans top bar
  - Dropdown résultats
  - Lien "Voir tout"

#### Jour 3-4: Notifications
- [ ] **Bell Icon + Badge**
  - Icon dans top bar
  - Badge count non lues

- [ ] **Dropdown Notifications**
  - Liste 10 dernières
  - Types:
    ```
    - Deal gagné
    - Nouveau contact assigné
    - Activité due
    - Mention note
    ```
  - Timestamp relatif
  - Mark as read
  - Mark all as read

- [ ] **Page Notifications** (`/notifications`)
  - Liste complète
  - Filtres
  - Pagination

#### Jour 4-5: Toast System
- [ ] Component Toast (Shadcn)
- [ ] Types: success, error, warning, info
- [ ] Auto-dismiss (5s)
- [ ] Actions dans toast
- [ ] Queue notifications

---

## 🟢 PHASE 12: RESPONSIVE & MOBILE (Semaine 8)

### Semaine 8 - Mobile Optimisation

#### Jour 1-2: Mobile Navigation
- [ ] **Responsive Breakpoints**
  - Mobile: < 640px
  - Tablet: 640px - 1024px
  - Desktop: > 1024px

- [ ] **Mobile Menu**
  - Burger menu (Sheet)
  - Bottom navigation (optionnel)
  - Swipe gestures

#### Jour 2-3: Mobile Tables
- [ ] **Vue Cartes au lieu de Tableaux**
  - Contacts cards
  - Companies cards
  - Deals cards
  - Swipe actions (delete, edit)
  - Infinite scroll

#### Jour 3-4: Mobile Forms
- [ ] **Optimisation Formulaires**
  - Inputs adaptés (type="tel", type="email")
  - Date pickers natifs
  - Upload photos (camera)
  - Validation temps réel

#### Jour 4-5: Touch Optimisation
- [ ] **Touch-Friendly**
  - Boutons min 44px
  - Espacement
  - Pull to refresh (optionnel)
  - Swipe gestures

- [ ] **Tests Mobile**
  - Tester toutes pages
  - Différents devices
  - Différents navigateurs

---

## 🟢 PHASE 13: PERFORMANCE (Semaine 8-9)

### Semaine 8 (fin) + Semaine 9 - Optimisations

#### Jour 1-2: Next.js Optimisations
- [ ] **Images**
  - Utiliser next/image partout
  - Lazy loading
  - Placeholders

- [ ] **Code Splitting**
  - Server Components où possible
  - Client Components minimal
  - Dynamic imports
  - Route prefetching

#### Jour 2-3: API Optimisations
- [ ] **React Query Config**
  - Stale time approprié
  - Cache time
  - Prefetching strategic
  - Optimistic updates

- [ ] **Pagination Virtualisée**
  - Pour grandes listes
  - react-virtual

- [ ] **Debouncing**
  - Search inputs
  - Filter inputs

#### Jour 3-4: Loading States
- [ ] **Skeletons Partout**
  - Tableau contacts
  - Cards dashboard
  - Détail pages
  - Forms

- [ ] **Suspense & Error Boundaries**
  - Suspense boundaries
  - Error boundaries
  - Empty states
  - Retry logic

#### Jour 4-5: Bundle Optimisation
- [ ] **Analyse Bundle**
  ```bash
  npm install @next/bundle-analyzer
  ```
- [ ] Tree shaking
- [ ] Optimize dependencies
- [ ] Remove unused code

---

## 🟢 PHASE 14: TESTS & QA (Semaine 9)

### Semaine 9 - Testing & Quality

#### Jour 1-2: Tests Unitaires
- [ ] **Setup Vitest**
  ```bash
  npm install -D vitest @testing-library/react @testing-library/jest-dom
  ```
- [ ] **Tests Utils**
  - Fonctions utilitaires
  - Hooks custom
  - API client

- [ ] **Tests Components**
  - Boutons
  - Formulaires
  - Cards

#### Jour 2-3: Tests Intégration
- [ ] **Tests Formulaires**
  - Login form
  - Contact form
  - Deal form

- [ ] **Tests API** (mocked)
  - Hooks React Query
  - Error handling

#### Jour 3-4: Tests E2E
- [ ] **Setup Playwright**
  ```bash
  npm install -D @playwright/test
  ```
- [ ] **User Flows Critiques**
  - Login → Dashboard
  - Créer contact
  - Créer deal
  - Changer étape deal
  - Marquer deal gagné

#### Jour 4-5: QA Manual
- [ ] **Checklist QA**
  - Toutes fonctionnalités
  - Tous user flows
  - Cross-browser (Chrome, Firefox, Safari)
  - Responsive (mobile, tablet, desktop)
  - Performance (Lighthouse)
  - Accessibilité (a11y)

---

## 🟢 PHASE 15: DÉPLOIEMENT (Semaine 9-10)

### Semaine 10 - Production Ready

#### Jour 1-2: Configuration Déploiement
- [ ] **Vercel Setup**
  - Créer compte Vercel
  - Connecter repo GitHub
  - Variables d'environnement:
    ```
    NEXT_PUBLIC_API_URL
    NEXT_PUBLIC_APP_NAME
    ```
  - Domaine custom
  - SSL automatique

#### Jour 2-3: CI/CD
- [ ] **GitHub Actions**
  - Workflow `.github/workflows/ci.yml`:
    ```yaml
    - Linter sur PR
    - Tests sur PR
    - Type check
    - Build check
    ```
  - Workflow `.github/workflows/deploy.yml`:
    ```yaml
    - Deploy preview sur PR
    - Deploy production sur main
    ```

#### Jour 3-4: Monitoring
- [ ] **Vercel Analytics**
  - Activer analytics
  - Web Vitals

- [ ] **Sentry**
  ```bash
  npm install @sentry/nextjs
  ```
  - Configuration
  - Error tracking
  - Performance monitoring

- [ ] **Google Analytics** (optionnel)

#### Jour 4-5: Documentation & Launch
- [ ] **Documentation**
  - README.md complet
  - Guide installation
  - Guide contribution
  - API documentation

- [ ] **Pre-launch Checklist**
  - [ ] Tests passent
  - [ ] Lighthouse score > 90
  - [ ] Mobile responsive
  - [ ] Cross-browser testé
  - [ ] Variables prod configurées
  - [ ] Monitoring actif
  - [ ] Backup plan

- [ ] **🚀 LAUNCH MVP**

---

## 📦 COMPOSANTS RÉUTILISABLES

### Core Components (à créer progressivement)
```
components/
  ui/                    # Shadcn components

  features/
    data-table/          # Tableau réutilisable
    autocomplete-select/ # Select avec recherche
    date-range-picker/   # Sélection range dates
    avatar-upload/       # Upload avatar avec crop
    rich-text-editor/    # Éditeur notes
    file-upload/         # Upload fichiers drag & drop
    empty-state/         # État vide
    loading-skeleton/    # Skeletons
    confirm-dialog/      # Dialog confirmation
    stats-card/          # Card stats dashboard
    tag-input/           # Input tags
    user-select/         # Select users
    currency-input/      # Input devise
    percentage-slider/   # Slider pourcentage
```

---

## 🎨 DESIGN SYSTEM

### Thème Tailwind
```typescript
// tailwind.config.ts
theme: {
  extend: {
    colors: {
      // Brand
      primary: {...},
      secondary: {...},

      // Status
      success: {...},
      error: {...},
      warning: {...},
      info: {...},

      // Greys
      grey: {...},
    },
    fontFamily: {
      sans: ['Inter', 'sans-serif'],
    },
  }
}
```

### Typography
- Font: Inter ou Roboto
- Scales: h1 (2.5rem), h2 (2rem), h3 (1.5rem), body (1rem), small (0.875rem)
- Weights: regular (400), medium (500), semibold (600), bold (700)

### Icons
- Library: Lucide Icons
- Taille: 16px, 20px, 24px
- Usage cohérent

---

## 📊 MÉTRIQUES DE SUCCÈS

### Performance
- [ ] Lighthouse Score > 90
- [ ] First Contentful Paint < 1.5s
- [ ] Time to Interactive < 3s
- [ ] Bundle size < 300KB (initial)

### Qualité
- [ ] 0 erreurs console
- [ ] 0 warnings TypeScript
- [ ] Test coverage > 50%
- [ ] E2E tests critiques 100%

### UX
- [ ] Toutes pages responsive
- [ ] Loading states partout
- [ ] Error states graceful
- [ ] Empty states informatifs

---

## 🚀 RÉSUMÉ PAR SEMAINE

| Semaine | Focus | Livrables |
|---------|-------|-----------|
| **1** | Setup + Auth | Projet initialisé, Auth fonctionnel |
| **2** | Layout + Dashboard | Navigation, Dashboard avec stats |
| **3-4** | Contacts + Entreprises | CRUD complet Contacts & Entreprises |
| **5** | Deals | Kanban pipeline, CRUD deals |
| **5-6** | Activités + Settings | Calendrier, Profil, Pipelines |
| **6-7** | Fichiers + Notes + Search | Upload, Notes, Search global |
| **7** | Notifications | System notifications complet |
| **8** | Responsive | Mobile optimisé |
| **8-9** | Performance | Optimisations, Tests |
| **9-10** | Deploy | Production ready |

---

## ✅ DÉFINITION OF DONE - MVP

Le MVP est considéré **TERMINÉ** quand:

### Fonctionnel
- [ ] Login/Register fonctionne
- [ ] Dashboard affiche vraies stats
- [ ] CRUD Contacts 100% opérationnel
- [ ] CRUD Entreprises 100% opérationnel
- [ ] CRUD Deals avec Kanban drag & drop
- [ ] CRUD Activités avec calendrier
- [ ] Search global fonctionne
- [ ] Profil utilisateur modifiable
- [ ] Upload fichiers fonctionne

### Technique
- [ ] 0 erreurs TypeScript
- [ ] 0 erreurs console
- [ ] Tests E2E critiques passent
- [ ] Mobile 100% responsive
- [ ] Lighthouse score > 90
- [ ] Déployé en production (Vercel)
- [ ] Monitoring actif (Sentry)

### UX
- [ ] Toutes pages ont loading states
- [ ] Toutes pages ont empty states
- [ ] Toutes erreurs sont gérées gracieusement
- [ ] Navigation intuitive
- [ ] Formulaires validés
- [ ] Feedbacks visuels (toasts, animations)

---

## 🎯 APRÈS MVP (Version 2.0)

### Fonctionnalités Non-MVP
- Rapports & Analytics avancés
- Export PDF/Excel personnalisé
- Email campaigns
- Workflows automation
- Webhooks
- Intégrations tierces (Zapier, etc.)
- Multi-langue (i18n)
- White labeling
- SSO (SAML, OAuth)
- Mobile apps natives

---

## 📞 SUPPORT DÉVELOPPEMENT

### Ressources
- Documentation Next.js: https://nextjs.org/docs
- Documentation Shadcn/ui: https://ui.shadcn.com
- Documentation React Query: https://tanstack.com/query
- Documentation Tailwind: https://tailwindcss.com

### Décisions Techniques
- **Pourquoi Next.js?** SSR, performance, SEO, DX
- **Pourquoi App Router?** Future-proof, Server Components
- **Pourquoi Shadcn/ui?** Composants flexibles, pas de vendor lock-in
- **Pourquoi React Query?** Meilleur data fetching, cache intelligent
- **Pourquoi Zustand?** Simple, léger, performant

---

## 📝 NOTES IMPORTANTES

### Backend Dependencies
Cette roadmap suppose que le backend Symfony CRM-API:
1. ✅ A tous les endpoints listés fonctionnels
2. ✅ Retourne les bonnes structures JSON
3. ✅ Gère l'authentification JWT
4. ✅ Gère les erreurs correctement
5. ⚠️ A les problèmes de sécurité corrigés (cf analyse)

### Ajustements Possibles
- Durée estimée: 9-10 semaines **pour 1 développeur senior**
- Avec 2 devs: 5-6 semaines
- Avec junior: +30% temps

### Priorités Variables
Si besoin de MVP plus rapide (6 semaines):
- Enlever: Import CSV, Fichiers, Notes, Notifications, Forecast
- Garder: Auth, Dashboard basique, CRUD Contact/Company/Deal, Kanban

---

**Dernière mise à jour:** 2025-11-27

**Version:** 1.0.0

**Statut:** 📋 Ready to start
