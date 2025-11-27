# Guide de Test du Système d'Authentification

Ce guide explique comment tester le système d'authentification SaaS une fois la base de données configurée.

## ⚠️ Prérequis

### 1. Base de données configurée

Vous devez avoir une base de données MySQL/MariaDB accessible.

**Option A : Utiliser Docker (Recommandé)**
```bash
docker run --name crm-mysql \
  -e MYSQL_ROOT_PASSWORD=root \
  -e MYSQL_DATABASE=crm_db \
  -e MYSQL_USER=crm_user \
  -e MYSQL_PASSWORD=crm_password \
  -p 3306:3306 \
  -d mysql:8.0
```

**Option B : MySQL local**
```sql
CREATE DATABASE crm_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'crm_user'@'localhost' IDENTIFIED BY 'crm_password';
GRANT ALL PRIVILEGES ON crm_db.* TO 'crm_user'@'localhost';
FLUSH PRIVILEGES;
```

### 2. Configuration de l'environnement

Modifiez `.env.local` avec vos paramètres de base de données :

```env
# MySQL
DATABASE_URL="mysql://crm_user:crm_password@127.0.0.1:3306/crm_db?serverVersion=8.0&charset=utf8mb4"

# OU MariaDB
# DATABASE_URL="mysql://crm_user:crm_password@127.0.0.1:3306/crm_db?serverVersion=10.11.2-MariaDB&charset=utf8mb4"
```

### 3. Exécuter les migrations

```bash
# Vérifier les migrations en attente
php bin/console doctrine:migrations:status

# Exécuter les migrations
php bin/console doctrine:migrations:migrate --no-interaction

# Vérifier que les tables sont créées
php bin/console doctrine:schema:validate
```

Vous devriez voir 4 nouvelles tables :
- `refresh_token`
- `email_verification_token`
- `password_reset_token`
- `login_history`

---

## 📝 Tests Étape par Étape

### Test 1 : Inscription Utilisateur

**Endpoint:** `POST /auth/register`

```bash
curl -X POST http://localhost:8000/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "TestPass123!",
    "firstname": "John",
    "lastname": "Doe"
  }'
```

**Réponse attendue (201):**
```json
{
  "status": "success",
  "message": "User registered successfully. Please check your email to verify your account.",
  "data": {
    "userId": "...",
    "email": "test@example.com",
    "verificationToken": "..."
  }
}
```

**Vérifications :**
```bash
# Vérifier que l'utilisateur est créé
php bin/console doctrine:query:sql "SELECT email, is_active FROM user WHERE email = 'test@example.com'"

# Résultat attendu:
# email: test@example.com
# is_active: 0 (false - pas encore vérifié)

# Vérifier qu'un token de vérification existe
php bin/console doctrine:query:sql "SELECT token, expires_at FROM email_verification_token ORDER BY created_at DESC LIMIT 1"
```

---

### Test 2 : Vérification Email

**Endpoint:** `POST /auth/verify-email`

```bash
# Récupérer le token depuis la base de données
TOKEN=$(php bin/console doctrine:query:sql "SELECT token FROM email_verification_token ORDER BY created_at DESC LIMIT 1" | tail -1 | awk '{print $2}')

curl -X POST http://localhost:8000/auth/verify-email \
  -H "Content-Type: application/json" \
  -d "{
    \"token\": \"$TOKEN\"
  }"
```

**Réponse attendue (200):**
```json
{
  "status": "success",
  "message": "Email verified successfully. You can now log in.",
  "data": {
    "userId": "...",
    "email": "test@example.com"
  }
}
```

**Vérifications :**
```bash
# Vérifier que l'utilisateur est maintenant actif
php bin/console doctrine:query:sql "SELECT email, is_active FROM user WHERE email = 'test@example.com'"

# Résultat attendu:
# is_active: 1 (true - vérifié)
```

---

### Test 3 : Login

**Endpoint:** `POST /auth/login`

```bash
curl -X POST http://localhost:8000/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "TestPass123!"
  }'
```

**Réponse attendue (200):**
```json
{
  "status": "success",
  "message": "Login successful",
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "refreshToken": "abc123def456...",
    "expiresAt": "2025-12-27T20:30:00+00:00",
    "user": {
      "id": "...",
      "email": "test@example.com",
      "firstname": "John",
      "lastname": "Doe",
      "roles": ["ROLE_USER"]
    }
  }
}
```

**Sauvegarder les tokens pour les tests suivants :**
```bash
# Sauvegarder dans des variables
JWT_TOKEN="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
REFRESH_TOKEN="abc123def456..."
```

**Vérifications :**
```bash
# Vérifier qu'un refresh token a été créé
php bin/console doctrine:query:sql "SELECT token, is_revoked FROM refresh_token ORDER BY created_at DESC LIMIT 1"

# Vérifier l'historique de connexion
php bin/console doctrine:query:sql "SELECT ip_address, success, created_at FROM login_history ORDER BY created_at DESC LIMIT 5"
```

---

### Test 4 : Accéder à une ressource protégée

**Endpoint:** `GET /auth/me`

```bash
curl -X GET http://localhost:8000/auth/me \
  -H "Authorization: Bearer $JWT_TOKEN"
```

**Réponse attendue (200):**
```json
{
  "status": "success",
  "data": {
    "id": "...",
    "email": "test@example.com",
    "firstname": "John",
    "lastname": "Doe",
    "roles": ["ROLE_USER"],
    "isActive": true,
    "createdAt": "2025-11-27T20:25:00+00:00"
  }
}
```

---

### Test 5 : Refresh Token

**Endpoint:** `POST /auth/refresh`

```bash
curl -X POST http://localhost:8000/auth/refresh \
  -H "Content-Type: application/json" \
  -d "{
    \"refreshToken\": \"$REFRESH_TOKEN\"
  }"
```

**Réponse attendue (200):**
```json
{
  "status": "success",
  "message": "Token refreshed successfully",
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "refreshToken": "new_token_abc123...",
    "expiresAt": "2025-12-27T21:30:00+00:00"
  }
}
```

**Vérifications :**
```bash
# L'ancien token devrait être révoqué
php bin/console doctrine:query:sql "SELECT token, is_revoked FROM refresh_token ORDER BY created_at DESC LIMIT 2"

# Résultat attendu:
# Le premier (nouveau) : is_revoked = 0
# Le second (ancien) : is_revoked = 1
```

---

### Test 6 : Demande de Reset de Mot de Passe

**Endpoint:** `POST /auth/forgot-password`

```bash
curl -X POST http://localhost:8000/auth/forgot-password \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com"
  }'
```

**Réponse attendue (200):**
```json
{
  "status": "success",
  "message": "If an account exists with this email, a password reset link will be sent.",
  "data": {
    "resetToken": "..."
  }
}
```

**Vérifications :**
```bash
# Récupérer le token de reset
RESET_TOKEN=$(php bin/console doctrine:query:sql "SELECT token FROM password_reset_token ORDER BY created_at DESC LIMIT 1" | tail -1 | awk '{print $2}')

echo "Reset token: $RESET_TOKEN"
```

---

### Test 7 : Reset de Mot de Passe

**Endpoint:** `POST /auth/reset-password`

```bash
curl -X POST http://localhost:8000/auth/reset-password \
  -H "Content-Type: application/json" \
  -d "{
    \"token\": \"$RESET_TOKEN\",
    \"password\": \"NewPassword123!\"
  }"
```

**Réponse attendue (200):**
```json
{
  "status": "success",
  "message": "Password reset successfully. You can now log in with your new password."
}
```

**Vérifications :**
```bash
# Tous les refresh tokens devraient être révoqués
php bin/console doctrine:query:sql "SELECT COUNT(*) as revoked FROM refresh_token WHERE is_revoked = 1"

# Le token de reset devrait être marqué comme utilisé
php bin/console doctrine:query:sql "SELECT is_used FROM password_reset_token WHERE token = '$RESET_TOKEN'"
```

**Test de login avec nouveau mot de passe :**
```bash
curl -X POST http://localhost:8000/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "NewPassword123!"
  }'
```

---

### Test 8 : Logout

**Endpoint:** `POST /auth/logout`

```bash
# Utiliser le nouveau refresh token du login précédent
curl -X POST http://localhost:8000/auth/logout \
  -H "Content-Type: application/json" \
  -d "{
    \"refreshToken\": \"$NEW_REFRESH_TOKEN\"
  }"
```

**Réponse attendue (200):**
```json
{
  "status": "success",
  "message": "Logged out successfully"
}
```

**Vérifications :**
```bash
# Le refresh token devrait être révoqué
php bin/console doctrine:query:sql "SELECT is_revoked FROM refresh_token WHERE token = '$NEW_REFRESH_TOKEN'"
```

---

## 🔒 Tests de Sécurité

### Test 9 : Rate Limiting - Login

Essayez de vous connecter 6 fois avec un mauvais mot de passe :

```bash
for i in {1..6}; do
  echo "Tentative $i"
  curl -X POST http://localhost:8000/auth/login \
    -H "Content-Type: application/json" \
    -d '{
      "email": "test@example.com",
      "password": "WrongPassword"
    }'
  echo ""
done
```

**Résultat attendu après la 5ème tentative (429):**
```json
{
  "status": "error",
  "message": "Too many failed login attempts. Please try again later or reset your password."
}
```

**Vérifications :**
```bash
# Vérifier l'historique des tentatives échouées
php bin/console doctrine:query:sql "SELECT ip_address, success, failure_reason, created_at FROM login_history WHERE success = 0 ORDER BY created_at DESC LIMIT 10"
```

---

### Test 10 : Validation de Mot de Passe Faible

```bash
curl -X POST http://localhost:8000/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "weak@example.com",
    "password": "weak"
  }'
```

**Réponse attendue (400):**
```json
{
  "status": "error",
  "message": "Password must be at least 8 characters long"
}
```

---

### Test 11 : Token Expiré

```bash
# Utiliser un vieux token de vérification (expiré)
curl -X POST http://localhost:8000/auth/verify-email \
  -H "Content-Type: application/json" \
  -d '{
    "token": "expired_token_123"
  }'
```

**Réponse attendue (400 ou 404):**
```json
{
  "status": "error",
  "message": "Verification token has expired or already been used"
}
```

---

## 🧪 Test de la Commande de Nettoyage

```bash
# Mode dry-run
php bin/console app:auth:cleanup-tokens --dry-run

# Nettoyage réel
php bin/console app:auth:cleanup-tokens
```

**Résultat attendu :**
```
Authentication Token Cleanup
============================

Cleanup completed successfully!

Results:
--------

 * Refresh tokens deleted: X
 * Email verification tokens deleted: Y
 * Password reset tokens deleted: Z
 * Login history records deleted: W

 [INFO] Total records deleted: N
```

---

## 📊 Vérifications de la Base de Données

### Compter les enregistrements

```bash
# Utilisateurs
php bin/console doctrine:query:sql "SELECT COUNT(*) as total FROM user"

# Refresh tokens actifs
php bin/console doctrine:query:sql "SELECT COUNT(*) as total FROM refresh_token WHERE is_revoked = 0"

# Tentatives de connexion (dernières 24h)
php bin/console doctrine:query:sql "SELECT COUNT(*) as total FROM login_history WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)"

# Tokens de vérification non utilisés
php bin/console doctrine:query:sql "SELECT COUNT(*) as total FROM email_verification_token WHERE is_used = 0"
```

### Statistiques de sécurité

```bash
# Taux de réussite des connexions
php bin/console doctrine:query:sql "
  SELECT
    COUNT(*) as total,
    SUM(CASE WHEN success = 1 THEN 1 ELSE 0 END) as successful,
    SUM(CASE WHEN success = 0 THEN 1 ELSE 0 END) as failed,
    ROUND(SUM(CASE WHEN success = 1 THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as success_rate
  FROM login_history
"

# Top 10 des IP avec le plus de tentatives échouées
php bin/console doctrine:query:sql "
  SELECT ip_address, COUNT(*) as attempts
  FROM login_history
  WHERE success = 0
  GROUP BY ip_address
  ORDER BY attempts DESC
  LIMIT 10
"
```

---

## ✅ Checklist de Test Complet

- [ ] Inscription fonctionne
- [ ] Email de vérification créé
- [ ] Vérification email fonctionne
- [ ] Login retourne JWT + refresh token
- [ ] Accès aux ressources protégées avec JWT
- [ ] Refresh token fonctionne
- [ ] Logout révoque le refresh token
- [ ] Reset mot de passe fonctionne
- [ ] Rate limiting bloque après 5 tentatives
- [ ] Mots de passe faibles sont rejetés
- [ ] Tokens expirés sont rejetés
- [ ] Commande de nettoyage fonctionne
- [ ] Historique de connexion enregistré
- [ ] Les tokens utilisés sont marqués comme tels

---

## 🐛 Troubleshooting

### Problème : JWT invalide

```bash
# Vérifier la configuration JWT
php bin/console debug:config lexik_jwt_authentication

# Vérifier que les clés existent
ls -la config/jwt/
```

### Problème : Tokens non révoqués

```bash
# Révoquer manuellement tous les tokens d'un utilisateur
php bin/console doctrine:query:sql "UPDATE refresh_token SET is_revoked = 1 WHERE user_id = X"
```

### Problème : Rate limiting ne fonctionne pas

```bash
# Vérifier que les tentatives sont enregistrées
php bin/console doctrine:query:sql "SELECT * FROM login_history ORDER BY created_at DESC LIMIT 20"
```

---

## 📈 Prochaines Étapes

Après avoir validé tous les tests :

1. **Configurer un vrai service d'email** (Mailtrap, SendGrid, etc.)
2. **Tester les emails** de vérification et reset
3. **Configurer le CORS** pour le frontend
4. **Ajouter des tests automatisés** (PHPUnit)
5. **Optimiser les performances** (cache, indexes)
6. **Monitoring** (logs, alertes)
7. **Documentation utilisateur**

---

**Date de création:** 2025-11-27
**Version:** 1.0.0
