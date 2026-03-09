# CRM API

API REST complète pour la gestion de la relation client (CRM) avec architecture multi-tenant.

## 🚀 Fonctionnalités

- ✅ **Authentification JWT** avec vérification par email OTP
- ✅ **Architecture multi-tenant** avec base de données par tenant
- ✅ **Gestion complète CRM**: Contacts, Entreprises, Deals, Activités
- ✅ **Pipeline de ventes** personnalisable
- ✅ **Système de tags** et notes
- ✅ **Gestion de fichiers** et documents
- ✅ **Import/Export** de données (CSV, Excel)
- ✅ **API RESTful** avec 120+ endpoints
- ✅ **Collection Postman** complète avec scripts automatiques

## 📋 Prérequis

- PHP 8.2+
- MySQL 8.0+
- Composer
- Symfony CLI (optionnel)

## 🛠️ Installation

### 1. Cloner le projet
```bash
git clone <repository-url>
cd crm-api
```

### 2. Installer les dépendances
```bash
composer install
```

### 3. Configurer l'environnement
Copiez `.env` et configurez vos paramètres:
```bash
cp .env .env.local
```

Modifiez `.env.local`:
```env
APP_ENV=dev
DATABASE_URL="mysql://user:password@127.0.0.1:3306/crm_db"
MAILER_DSN=smtp://username:password@smtp.mailtrap.io:2525
JWT_PASSPHRASE=your_secret_passphrase
```

### 4. Générer les clés JWT
```bash
php bin/console lexik:jwt:generate-keypair
```

### 5. Créer la base de données
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

### 6. Charger les données de test (optionnel)
```bash
php bin/console doctrine:fixtures:load
```

### 7. Démarrer le serveur
```bash
symfony server:start
# ou
php -S localhost:8000 -t public
```

## 🔐 Authentification avec OTP

L'API utilise un système d'authentification par email avec code OTP (One-Time Password).

### Flux d'inscription et vérification

#### 1. Inscription
```bash
POST /auth/register
Content-Type: application/json

{
    "email": "user@example.com",
    "password": "SecurePassword123!",
    "firstName": "John",
    "lastName": "Doe"
}
```

**Réponse:**
```json
{
    "status": "success",
    "message": "User registered successfully. Please check your email for the verification code.",
    "data": {
        "userId": 1,
        "email": "user@example.com",
        "otpCode": "123456"
    }
}
```

#### 2. Vérification avec OTP
```bash
POST /auth/verify-email-otp
Content-Type: application/json

{
    "email": "user@example.com",
    "code": "123456"
}
```

#### 3. Renvoyer un code OTP
```bash
POST /auth/resend-otp
Content-Type: application/json

{
    "email": "user@example.com"
}
```

#### 4. Connexion
```bash
POST /auth/login
Content-Type: application/json

{
    "email": "user@example.com",
    "password": "SecurePassword123!"
}
```

**Réponse:**
```json
{
    "status": "success",
    "message": "Login successful",
    "data": {
        "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
        "refreshToken": "def502001a2b3c...",
        "user": {
            "id": 1,
            "email": "user@example.com",
            "roles": ["ROLE_USER"]
        }
    }
}
```

### Sécurité OTP

- ✅ Codes à 6 chiffres
- ✅ Expiration après 10 minutes
- ✅ Maximum 5 tentatives par code
- ✅ Rate limiting: 10 tentatives/15 min par IP
- ✅ Invalidation automatique des anciens codes

## 📮 Configuration de l'email

### Développement (Mailtrap)
```env
MAILER_DSN=smtp://username:password@smtp.mailtrap.io:2525
```

### Production (Sendgrid)
```env
MAILER_DSN=sendgrid://API_KEY@default
```

### Production (Mailgun)
```env
MAILER_DSN=mailgun://API_KEY:DOMAIN@default
```

Pour plus de détails, consultez [docs/EMAIL_CONFIGURATION.md](docs/EMAIL_CONFIGURATION.md)

## 📚 Documentation

- **[Guide Postman avec OTP](docs/POSTMAN_AUTH_OTP.md)** - Guide complet d'utilisation de Postman
- **[Configuration Email](docs/EMAIL_CONFIGURATION.md)** - Configuration détaillée du système d'email
- **Collection Postman**: `CRM_API_COMPLETE.postman_collection.json`

## 🎯 Endpoints principaux

### Authentification
- `POST /auth/register` - Inscription avec envoi OTP
- `POST /auth/verify-email-otp` - Vérification email avec code OTP
- `POST /auth/resend-otp` - Renvoyer un code OTP
- `POST /auth/login` - Connexion
- `POST /auth/refresh` - Rafraîchir le token
- `POST /auth/logout` - Déconnexion
- `GET /auth/me` - Informations utilisateur

### Contacts
- `POST /contact/list` - Liste des contacts
- `GET /contact/info/{id}` - Détails d'un contact
- `POST /contact/edit` - Créer/modifier un contact
- `POST /contact/import` - Import CSV/Excel
- `DELETE /contact/delete/{id}` - Supprimer un contact

### Entreprises
- `POST /company/list` - Liste des entreprises
- `GET /company/info/{id}` - Détails d'une entreprise
- `POST /company/edit` - Créer/modifier une entreprise
- `POST /company/import` - Import CSV/Excel

### Deals (Opportunités)
- `POST /deal/edit` - Créer/modifier une opportunité
- `GET /deal/info/{id}` - Détails d'une opportunité
- `PATCH /deal/change/step/{id}` - Changer l'étape du pipeline
- `GET /deal/win/{id}` - Marquer comme gagné
- `GET /deal/lose/{id}` - Marquer comme perdu

### Activités
- `GET /activity/list` - Liste des activités
- `POST /activity/calendar` - Vue calendrier
- `POST /activity/edit` - Créer/modifier une activité

Pour la liste complète des endpoints, consultez la collection Postman.

## 🧪 Tests

### Lancer tous les tests
```bash
php bin/phpunit
```

### Tester un fichier spécifique
```bash
php bin/phpunit tests/Controller/ContactControllerTest.php
```

### Tests avec couverture
```bash
php bin/phpunit --coverage-html coverage
```

## 🏗️ Architecture

### Multi-Tenancy
- **Switcher**: Gestion du changement de tenant
- **ConnectionWrapper**: Wrapper Doctrine pour bases de données multi-tenant
- **KernelListener**: Écoute des événements pour le switching automatique

### Managers
Chaque entité a un Manager dédié pour la logique métier:
- `ContactManager`
- `CompanyManager`
- `DealManager`
- `ActivityManager`
- etc.

### Sécurité
- **JWT Authentication**: Tokens JWT avec LexikJWTAuthenticationBundle
- **API Key**: Authentification par clé API pour les services
- **Role Hierarchy**: ROLE_USER < ROLE_MANAGER < ROLE_ADMIN

## 🔧 Commandes utiles

### Database
```bash
# Créer une migration
php bin/console doctrine:migrations:diff

# Exécuter les migrations
php bin/console doctrine:migrations:migrate

# Charger les fixtures
php bin/console doctrine:fixtures:load
```

### Cache
```bash
# Vider le cache
php bin/console cache:clear

# Préchauffer le cache
php bin/console cache:warmup
```

### Multi-Tenancy
```bash
# Définir le tenant
php bin/console app:tenant:set tenant_name

# Obtenir le tenant actuel
php bin/console app:tenant:get

# Exécuter une commande pour chaque tenant
php bin/console app:tenant:foreach doctrine:migrations:migrate
```

## 📦 Collection Postman

La collection Postman inclut:
- ✅ 124+ endpoints organisés en 20 catégories
- ✅ Auto-capture du JWT token après login
- ✅ Auto-capture des IDs (contact, company, deal, etc.)
- ✅ Auto-capture du code OTP (en dev)
- ✅ Scripts automatiques pour tous les endpoints critiques
- ✅ Exemples de données réalistes

### Import dans Postman
1. Ouvrez Postman
2. Cliquez sur "Import"
3. Sélectionnez `CRM_API_COMPLETE.postman_collection.json`
4. La collection est prête à l'emploi!

### Quick Start Postman
1. Exécutez `Auth > Auth Register`
2. Vérifiez votre email dans Mailtrap et copiez le code
3. Exécutez `Auth > Auth Verify Email OTP` avec le code
4. Exécutez `Auth > Auth Login` → Token sauvegardé automatiquement
5. Tous les autres endpoints sont prêts!

## 🚀 Production

### Points importants

1. **Code OTP automatiquement masqué** ✅
   - Le code OTP n'est visible que si `APP_ENV=dev`
   - En production, définissez `APP_ENV=prod` dans votre `.env`
   - Le code est automatiquement exclu de la réponse JSON

2. **Configurer un service d'email professionnel**
   - Sendgrid, Mailgun, Amazon SES
   - Ne jamais utiliser Gmail en production

3. **Sécuriser les secrets**
   ```bash
   php bin/console secrets:set DATABASE_URL
   php bin/console secrets:set MAILER_DSN
   php bin/console secrets:set JWT_PASSPHRASE
   ```

4. **Optimiser pour la production**
   ```bash
   APP_ENV=prod
   composer install --no-dev --optimize-autoloader
   php bin/console cache:clear --env=prod
   php bin/console cache:warmup --env=prod
   ```

## 📝 Licence

Ce projet est sous licence privée.

## 👥 Support

Pour toute question ou problème, consultez la documentation dans le dossier `docs/`.
# crm-api
