# Authentication System Installation Guide

This guide will help you set up and configure the complete SaaS authentication system for the CRM API.

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Installation Steps](#installation-steps)
3. [Database Setup](#database-setup)
4. [JWT Configuration](#jwt-configuration)
5. [Email Configuration](#email-configuration)
6. [Testing the Installation](#testing-the-installation)
7. [Production Deployment](#production-deployment)
8. [Troubleshooting](#troubleshooting)

---

## Prerequisites

Before installing the authentication system, ensure you have:

- **PHP 8.2+** installed
- **Composer** installed
- **MySQL** database
- **Symfony CLI** (optional but recommended)
- **OpenSSL** for generating JWT keys

---

## Installation Steps

### 1. Install Dependencies

The required dependencies should already be installed. Verify:

```bash
composer require lexik/jwt-authentication-bundle
composer require symfony/mailer
```

### 2. Generate JWT Keys

Generate the public/private key pair for JWT:

```bash
php bin/console lexik:jwt:generate-keypair
```

This creates:
- `config/jwt/private.pem` - Private key for signing tokens
- `config/jwt/public.pem` - Public key for verifying tokens

**Important:** Add these files to `.gitignore`:

```gitignore
###> lexik/jwt-authentication-bundle ###
/config/jwt/*.pem
###< lexik/jwt-authentication-bundle ###
```

### 3. Configure Environment Variables

Add the following to your `.env` file:

```env
###> lexik/jwt-authentication-bundle ###
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=YOUR_PASSPHRASE_HERE
JWT_TOKEN_TTL=3600
###< lexik/jwt-authentication-bundle ###

###> symfony/mailer ###
MAILER_DSN=smtp://user:password@smtp.example.com:587
###< symfony/mailer ###

###> frontend configuration ###
FRONTEND_URL=http://localhost:3000
###< frontend configuration ###

###> authentication rate limiting (optional) ###
AUTH_MAX_LOGIN_ATTEMPTS_USER=5
AUTH_MAX_LOGIN_ATTEMPTS_IP=10
AUTH_LOGIN_WINDOW_MINUTES=15
AUTH_MAX_PASSWORD_RESET_ATTEMPTS=3
AUTH_PASSWORD_RESET_WINDOW_MINUTES=60
###< authentication rate limiting ###
```

**Production `.env.local` example:**

```env
JWT_PASSPHRASE=secure_random_passphrase_change_this
MAILER_DSN=smtp://noreply@yourdomain.com:password@smtp.sendgrid.net:587
FRONTEND_URL=https://app.yourdomain.com
APP_ENV=prod
APP_DEBUG=0
```

---

## Database Setup

### 1. Create Migration

Generate a migration for the new authentication entities:

```bash
php bin/console doctrine:migrations:diff
```

This creates a migration file for:
- `refresh_token` table
- `email_verification_token` table
- `password_reset_token` table
- `login_history` table

### 2. Review Migration

Check the generated migration file in `migrations/`:

```bash
ls -la migrations/
```

Review the migration SQL to ensure it's correct.

### 3. Run Migration

Execute the migration:

```bash
# Development
php bin/console doctrine:migrations:migrate

# Production (with confirmation)
php bin/console doctrine:migrations:migrate --no-interaction
```

### 4. Verify Tables

Check that the tables were created:

```bash
php bin/console doctrine:schema:validate
```

Expected output:
```
[OK] The mapping files are correct.
[OK] The database schema is in sync with the mapping files.
```

---

## JWT Configuration

### 1. Configure JWT Bundle

Verify `config/packages/lexik_jwt_authentication.yaml`:

```yaml
lexik_jwt_authentication:
    secret_key: '%env(resolve:JWT_SECRET_KEY)%'
    public_key: '%env(resolve:JWT_PUBLIC_KEY)%'
    pass_phrase: '%env(JWT_PASSPHRASE)%'
    token_ttl: '%env(int:JWT_TOKEN_TTL)%'
```

### 2. Configure Security

Update `config/packages/security.yaml`:

```yaml
security:
    password_hashers:
        App\Entity\User:
            algorithm: auto

    providers:
        app_user_provider:
            entity:
                class: App\Entity\User
                property: email

    firewalls:
        dev:
            pattern: ^/(_(profiler|wdt)|css|images|js)/
            security: false

        login:
            pattern: ^/auth/login
            stateless: true
            json_login:
                check_path: /auth/login
                success_handler: lexik_jwt_authentication.handler.authentication_success
                failure_handler: lexik_jwt_authentication.handler.authentication_failure

        api:
            pattern: ^/
            stateless: true
            jwt: ~

    access_control:
        # Public authentication endpoints
        - { path: ^/auth/register, roles: PUBLIC_ACCESS }
        - { path: ^/auth/login, roles: PUBLIC_ACCESS }
        - { path: ^/auth/verify-email-otp, roles: PUBLIC_ACCESS }
        - { path: ^/auth/resend-otp, roles: PUBLIC_ACCESS }
        - { path: ^/auth/forgot-password, roles: PUBLIC_ACCESS }
        - { path: ^/auth/reset-password, roles: PUBLIC_ACCESS }
        - { path: ^/auth/refresh, roles: PUBLIC_ACCESS }

        # Protected authentication endpoints
        - { path: ^/auth/me, roles: IS_AUTHENTICATED_FULLY }
        - { path: ^/auth/logout, roles: IS_AUTHENTICATED_FULLY }

        # All other routes require authentication
        - { path: ^/, roles: IS_AUTHENTICATED_FULLY }
```

### 3. Update Routes (if needed)

Ensure routes are registered in `config/routes.yaml`:

```yaml
controllers:
    resource:
        path: ../src/Controller/
        namespace: App\Controller
    type: attribute
```

---

## Email Configuration

### 1. Configure Mailer

Choose your email provider and configure `MAILER_DSN`:

**Gmail (for testing only):**
```env
MAILER_DSN=gmail://username:password@default
```

**SendGrid:**
```env
MAILER_DSN=smtp://apikey:YOUR_SENDGRID_API_KEY@smtp.sendgrid.net:587
```

**Mailgun:**
```env
MAILER_DSN=smtp://postmaster@YOUR_DOMAIN:YOUR_PASSWORD@smtp.mailgun.org:587
```

**Amazon SES:**
```env
MAILER_DSN=ses+smtp://ACCESS_KEY:SECRET_KEY@default?region=us-east-1
```

**Mailtrap (for development):**
```env
MAILER_DSN=smtp://username:password@smtp.mailtrap.io:2525
```

### 2. Configure EmailService

Update `src/Service/EmailService.php` constants:

```php
private const FROM_EMAIL = 'noreply@yourdomain.com';
private const FROM_NAME = 'Your Company Name';
```

### 3. Configure Frontend URL

Set the frontend URL in `.env`:

```env
FRONTEND_URL=http://localhost:3000  # Development
# FRONTEND_URL=https://app.yourdomain.com  # Production
```

Update `config/services.yaml` to inject it:

```yaml
services:
    App\Service\EmailService:
        arguments:
            $frontendUrl: '%env(FRONTEND_URL)%'
```

---

## Testing the Installation

### 1. Clear Cache

```bash
php bin/console cache:clear
```

### 2. Test User Registration

```bash
curl -X POST http://localhost:8000/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "TestPass123!",
    "firstname": "Test",
    "lastname": "User"
  }'
```

Expected response:
```json
{
  "status": "success",
  "message": "User registered successfully. Please check your email to verify your account."
}
```

### 3. Check Database

Verify user was created:

```bash
php bin/console doctrine:query:sql "SELECT email, is_active FROM user WHERE email = 'test@example.com'"
```

Expected output:
```
email: test@example.com
is_active: 0 (false - not verified yet)
```

### 4. Verify Email (Manual for Testing)

Get the OTP code from the database (in development):

```bash
php bin/console doctrine:query:sql "SELECT code FROM email_otp WHERE type = 'email_verification' ORDER BY created_at DESC LIMIT 1"
```

Verify the email with OTP code:

```bash
curl -X POST http://localhost:8000/auth/verify-email-otp \
  -H "Content-Type: application/json" \
  -d '{
    "email": "demo@example.com",
    "code": "123456"
  }'
```

### 5. Test Login

```bash
curl -X POST http://localhost:8000/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "TestPass123!"
  }'
```

Expected response:
```json
{
  "status": "success",
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "refreshToken": "def50200...",
    "user": { ... }
  }
}
```

### 6. Test Protected Endpoint

```bash
# Replace JWT_TOKEN with token from login response
curl -X GET http://localhost:8000/auth/me \
  -H "Authorization: Bearer JWT_TOKEN"
```

---

## Production Deployment

### 1. Environment Configuration

Create `.env.local` with production values:

```env
APP_ENV=prod
APP_DEBUG=0
JWT_PASSPHRASE=STRONG_RANDOM_PASSPHRASE
MAILER_DSN=smtp://production@config
FRONTEND_URL=https://app.yourdomain.com
DATABASE_URL=mysql://user:pass@host:3306/dbname
```

### 2. Optimize Autoloader

```bash
composer install --no-dev --optimize-autoloader
```

### 3. Clear and Warm Cache

```bash
APP_ENV=prod php bin/console cache:clear
APP_ENV=prod php bin/console cache:warmup
```

### 4. Set Up Cron Jobs

Add to crontab (`crontab -e`):

```cron
# Clean up expired tokens daily at 2 AM
0 2 * * * cd /path/to/crm-api && php bin/console app:auth:cleanup-tokens >> /var/log/auth-cleanup.log 2>&1
```

### 5. File Permissions

```bash
# Set proper permissions
chmod -R 755 /path/to/crm-api
chmod -R 775 /path/to/crm-api/var

# Protect JWT keys
chmod 600 config/jwt/*.pem
```

### 6. SSL/HTTPS

**Important:** Always use HTTPS in production!

Configure your web server (Nginx/Apache) with SSL certificate:

```nginx
# Nginx example
server {
    listen 443 ssl http2;
    server_name api.yourdomain.com;

    ssl_certificate /path/to/certificate.crt;
    ssl_certificate_key /path/to/private.key;

    root /path/to/crm-api/public;

    location / {
        try_files $uri /index.php$is_args$args;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        internal;
    }
}
```

### 7. Monitoring

Set up monitoring for:
- Failed login attempts (check login_history table)
- Token cleanup job execution
- Email delivery failures
- API response times

```bash
# Check recent failed logins
php bin/console doctrine:query:sql "SELECT COUNT(*) FROM login_history WHERE success = 0 AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)"
```

---

## Troubleshooting

### Problem: JWT token generation fails

**Solution:**
```bash
# Regenerate JWT keys
php bin/console lexik:jwt:generate-keypair --overwrite

# Check permissions
ls -la config/jwt/
chmod 600 config/jwt/*.pem
```

### Problem: Email not sending

**Solution:**
```bash
# Test mailer configuration
php bin/console debug:config symfony/mailer

# Check logs
tail -f var/log/dev.log | grep -i mail

# Verify MAILER_DSN is correct
echo $MAILER_DSN
```

### Problem: Database tables not created

**Solution:**
```bash
# Check migration status
php bin/console doctrine:migrations:status

# Run migrations
php bin/console doctrine:migrations:migrate

# Validate schema
php bin/console doctrine:schema:validate
```

### Problem: "Not authenticated" error

**Solution:**
```bash
# Verify JWT token is valid
# Check security.yaml configuration
php bin/console debug:config security

# Check if JWT bundle is properly installed
composer show lexik/jwt-authentication-bundle

# Clear cache
php bin/console cache:clear
```

### Problem: Rate limiting not working

**Solution:**

Check that login attempts are being logged:
```bash
php bin/console doctrine:query:sql "SELECT * FROM login_history ORDER BY created_at DESC LIMIT 10"
```

### Problem: Emails have wrong URLs

**Solution:**

Verify `FRONTEND_URL` is set correctly in `.env`:
```bash
# Check current value
php bin/console debug:container --env-vars | grep FRONTEND

# Update in .env
FRONTEND_URL=https://your-correct-domain.com
```

---

## Next Steps

After installation:

1. **Configure email templates** - Customize email templates in `EmailService.php`
2. **Set up monitoring** - Monitor failed logins and security events
3. **Test thoroughly** - Test all authentication flows
4. **Review security** - Enable CORS, rate limiting, etc.
5. **Document for team** - Share credentials and setup info with team

---

## Security Checklist

- [ ] JWT keys generated and secured (chmod 600)
- [ ] Strong JWT_PASSPHRASE set
- [ ] HTTPS enabled in production
- [ ] Email verification required
- [ ] Rate limiting configured
- [ ] Cron job for token cleanup set up
- [ ] Password strength requirements enforced
- [ ] Login history monitored
- [ ] Production debugging disabled
- [ ] Database credentials secured

---

## Support

For issues:
1. Check application logs in `var/log/`
2. Review this guide
3. Check Symfony documentation: https://symfony.com/doc/current/security.html
4. Contact system administrator

---

**Last Updated:** 2025-11-27
**Version:** 1.0.0
