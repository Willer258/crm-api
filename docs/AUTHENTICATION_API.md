# Authentication API Documentation

## Overview

This document describes the complete SaaS-grade authentication system for the CRM API. The system includes:

- User registration with email verification
- JWT-based authentication with refresh tokens
- Password reset functionality
- Rate limiting and security features
- Login history and audit trail

## Base URL

All authentication endpoints are prefixed with `/auth`

```
http://localhost:8000/auth
```

## Authentication Flow

### New User Flow

1. **Register** (`POST /auth/register`) - Create account, receive OTP code via email
2. **Verify Email** (`POST /auth/verify-email-otp`) - Verify email address with OTP code
3. **Login** (`POST /auth/login`) - Get JWT and refresh token
4. Access protected resources with JWT token

### Existing User Flow

1. **Login** (`POST /auth/login`) - Get JWT and refresh token
2. **Refresh Token** (`POST /auth/refresh`) - Renew JWT when expired
3. **Logout** (`POST /auth/logout`) - Revoke refresh token

### Password Reset Flow

1. **Forgot Password** (`POST /auth/forgot-password`) - Request reset link
2. **Reset Password** (`POST /auth/reset-password`) - Set new password
3. **Login** (`POST /auth/login`) - Login with new password

---

## Endpoints

### 1. Register

Create a new user account. Email verification is required before login.

**Endpoint:** `POST /auth/register`

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "SecurePass123!",
  "firstname": "John",
  "lastname": "Doe"
}
```

**Success Response (201):**
```json
{
  "status": "success",
  "message": "User registered successfully. Please check your email to verify your account.",
  "data": {
    "userId": "123e4567-e89b-12d3-a456-426614174000",
    "email": "user@example.com",
    "verificationToken": "abc123..." // Only in development
  }
}
```

**Error Responses:**

- **400 Bad Request** - Missing required fields
```json
{
  "status": "error",
  "message": "Email and password are required"
}
```

- **400 Bad Request** - Invalid email format
```json
{
  "status": "error",
  "message": "Invalid email format"
}
```

- **400 Bad Request** - Weak password
```json
{
  "status": "error",
  "message": "Password must be at least 8 characters long"
}
```

- **409 Conflict** - User already exists
```json
{
  "status": "error",
  "message": "User with this email already exists"
}
```

**Password Requirements:**
- Minimum 8 characters
- At least one uppercase letter
- At least one lowercase letter
- At least one number
- At least one special character
- Not a common password

---

### 2. Verify Email with OTP

Verify email address using the 6-digit OTP code sent via email.

**Endpoint:** `POST /auth/verify-email-otp`

**Request Body:**
```json
{
  "email": "user@example.com",
  "code": "123456"
}
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Email verified successfully. You can now log in.",
  "data": {
    "userId": "123e4567-e89b-12d3-a456-426614174000",
    "email": "user@example.com"
  }
}
```

**Error Responses:**

- **400 Bad Request** - Missing email or code
```json
{
  "status": "error",
  "message": "Email and verification code are required"
}
```

- **404 Not Found** - Invalid or expired OTP
```json
{
  "status": "error",
  "message": "No valid verification code found. Please request a new one."
}
```

- **400 Bad Request** - Wrong code
```json
{
  "status": "error",
  "message": "Invalid verification code.",
  "attemptsLeft": 3
}
```

- **429 Too Many Requests** - Rate limit exceeded
```json
{
  "status": "error",
  "message": "Too many verification attempts. Please try again later."
}
```

**OTP Details:**
- Expiration: 10 minutes
- Max attempts: 5
- Rate limiting: 10 attempts per 15 minutes per IP

---

### 3. Resend OTP Code

Resend a new OTP verification code if the original one expired.

**Endpoint:** `POST /auth/resend-otp`

**Request Body:**
```json
{
  "email": "user@example.com"
}
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Verification code sent successfully.",
  "data": {
    "otpCode": "123456" // Only in development environment
  }
}
```

**Error Responses:**

- **400 Bad Request** - Missing email
```json
{
  "status": "error",
  "message": "Email is required"
}
```

- **400 Bad Request** - Already verified
```json
{
  "status": "error",
  "message": "Email is already verified"
}
```

**Note:** For security, this endpoint returns success even if the email doesn't exist.

---

### 4. Login

Authenticate user and receive JWT token and refresh token.

**Endpoint:** `POST /auth/login`

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "SecurePass123!"
}
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Login successful",
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "refreshToken": "def50200...",
    "expiresAt": "2025-11-28T12:00:00+00:00",
    "user": {
      "id": "123e4567-e89b-12d3-a456-426614174000",
      "email": "user@example.com",
      "firstname": "John",
      "lastname": "Doe",
      "roles": ["ROLE_USER"]
    }
  }
}
```

**Error Responses:**

- **400 Bad Request** - Missing credentials
```json
{
  "status": "error",
  "message": "Email and password are required"
}
```

- **401 Unauthorized** - Invalid credentials
```json
{
  "status": "error",
  "message": "Invalid credentials"
}
```

- **403 Forbidden** - Email not verified
```json
{
  "status": "error",
  "message": "Please verify your email before logging in"
}
```

- **429 Too Many Requests** - Rate limit exceeded
```json
{
  "status": "error",
  "message": "Too many failed login attempts. Please try again later or reset your password."
}
```

**Rate Limits:**
- 5 failed attempts per user in 15 minutes
- 10 failed attempts per IP in 15 minutes

**Token Expiration:**
- JWT token: 1 hour
- Refresh token: 30 days

---

### 5. Refresh Token

Get a new JWT token using a refresh token.

**Endpoint:** `POST /auth/refresh`

**Request Body:**
```json
{
  "refreshToken": "def50200..."
}
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Token refreshed successfully",
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "refreshToken": "ghi78900...",
    "expiresAt": "2025-12-28T12:00:00+00:00"
  }
}
```

**Error Responses:**

- **400 Bad Request** - Missing refresh token
```json
{
  "status": "error",
  "message": "Refresh token is required"
}
```

- **404 Not Found** - Invalid refresh token
```json
{
  "status": "error",
  "message": "Invalid refresh token"
}
```

- **401 Unauthorized** - Expired or revoked token
```json
{
  "status": "error",
  "message": "Refresh token has expired or been revoked"
}
```

**Note:** This endpoint implements token rotation - the old refresh token is revoked and a new one is issued.

---

### 6. Logout

Logout user by revoking their refresh token.

**Endpoint:** `POST /auth/logout`

**Request Body:**
```json
{
  "refreshToken": "def50200..."
}
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Logged out successfully"
}
```

**Error Responses:**

- **400 Bad Request** - Missing refresh token
```json
{
  "status": "error",
  "message": "Refresh token is required"
}
```

**Note:** This endpoint always returns success, even if the token is invalid.

---

### 7. Forgot Password

Request a password reset link via email.

**Endpoint:** `POST /auth/forgot-password`

**Request Body:**
```json
{
  "email": "user@example.com"
}
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "If an account exists with this email, a password reset link will be sent.",
  "data": {
    "resetToken": "reset_token..." // Only in development
  }
}
```

**Error Responses:**

- **400 Bad Request** - Missing email
```json
{
  "status": "error",
  "message": "Email is required"
}
```

- **429 Too Many Requests** - Rate limit exceeded
```json
{
  "status": "error",
  "message": "Too many password reset requests. Please try again later."
}
```

**Rate Limits:**
- 3 reset requests per user in 60 minutes

**Token Expiration:** 1 hour

**Note:** For security, this endpoint returns success even if the email doesn't exist.

---

### 8. Reset Password

Reset password using the token from the forgot password email.

**Endpoint:** `POST /auth/reset-password`

**Request Body:**
```json
{
  "token": "reset_token_from_email",
  "password": "NewSecurePass123!"
}
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Password reset successfully. You can now log in with your new password."
}
```

**Error Responses:**

- **400 Bad Request** - Missing fields
```json
{
  "status": "error",
  "message": "Token and new password are required"
}
```

- **404 Not Found** - Invalid token
```json
{
  "status": "error",
  "message": "Invalid reset token"
}
```

- **400 Bad Request** - Expired or used token
```json
{
  "status": "error",
  "message": "Reset token has expired or already been used"
}
```

- **400 Bad Request** - Weak password
```json
{
  "status": "error",
  "message": "Password must be at least 8 characters long"
}
```

**Security:** All refresh tokens are revoked after password reset.

---

### 9. Get Current User

Get information about the currently authenticated user.

**Endpoint:** `GET /auth/me`

**Headers:**
```
Authorization: Bearer {jwt_token}
```

**Success Response (200):**
```json
{
  "status": "success",
  "data": {
    "id": "123e4567-e89b-12d3-a456-426614174000",
    "email": "user@example.com",
    "firstname": "John",
    "lastname": "Doe",
    "roles": ["ROLE_USER"],
    "isActive": true,
    "createdAt": "2025-11-01T10:30:00+00:00"
  }
}
```

**Error Responses:**

- **401 Unauthorized** - Not authenticated
```json
{
  "status": "error",
  "message": "Not authenticated"
}
```

---

## Using JWT Tokens

### Include in Request Headers

For all protected endpoints, include the JWT token in the Authorization header:

```
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

### Token Refresh Strategy

1. Store both JWT token and refresh token securely
2. Use JWT token for API requests
3. When JWT expires (401 error), use refresh token to get new JWT
4. If refresh token is invalid/expired, redirect to login

**Example Flow:**
```javascript
// Make API request
const response = await fetch('/api/contacts', {
  headers: {
    'Authorization': `Bearer ${jwtToken}`
  }
});

// If token expired
if (response.status === 401) {
  // Refresh the token
  const refreshResponse = await fetch('/auth/refresh', {
    method: 'POST',
    body: JSON.stringify({ refreshToken })
  });

  const { data } = await refreshResponse.json();

  // Store new tokens
  jwtToken = data.token;
  refreshToken = data.refreshToken;

  // Retry original request
  return fetch('/api/contacts', {
    headers: {
      'Authorization': `Bearer ${jwtToken}`
    }
  });
}
```

---

## Security Features

### Rate Limiting

- **Login:** 5 failed attempts per user, 10 per IP in 15 minutes
- **Password Reset:** 3 requests per user in 60 minutes
- **Account Lockout:** Temporary after exceeding login attempts

### Password Strength

Passwords must contain:
- Minimum 8 characters
- At least 1 uppercase letter
- At least 1 lowercase letter
- At least 1 number
- At least 1 special character
- Must not be a common password

### Token Security

- **Email Verification:** 24 hour expiration, single use
- **Password Reset:** 1 hour expiration, single use
- **Refresh Token:** 30 day expiration, revocable, rotation on refresh
- **JWT Token:** 1 hour expiration, stateless

### Login History

All login attempts (successful and failed) are logged with:
- IP address
- User agent
- Timestamp
- Location (if available)
- Success/failure reason

### Email Notifications

Users receive emails for:
- Email verification
- Password reset requests
- Successful password changes
- Suspicious activity detection

---

## Maintenance Commands

### Cleanup Expired Tokens

Run this command periodically (e.g., daily via cron) to clean up expired tokens:

```bash
# Clean up expired tokens
php bin/console app:auth:cleanup-tokens

# Preview what would be deleted
php bin/console app:auth:cleanup-tokens --dry-run
```

**Cron Example (daily at 2 AM):**
```
0 2 * * * cd /path/to/crm-api && php bin/console app:auth:cleanup-tokens
```

This cleans up:
- Expired refresh tokens
- Expired email verification tokens
- Expired password reset tokens
- Login history older than 90 days

---

## Error Codes Summary

| Status Code | Meaning |
|-------------|---------|
| 200 | Success |
| 201 | Created (registration) |
| 400 | Bad Request (validation error) |
| 401 | Unauthorized (invalid credentials/token) |
| 403 | Forbidden (email not verified) |
| 404 | Not Found (invalid token) |
| 409 | Conflict (user already exists) |
| 429 | Too Many Requests (rate limited) |
| 500 | Internal Server Error |

---

## Configuration

### Environment Variables

Configure these in your `.env` file:

```env
# JWT Configuration
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=your_passphrase

# Email Configuration
MAILER_DSN=smtp://user:pass@smtp.example.com:587
FRONTEND_URL=http://localhost:3000

# Rate Limiting (optional, defaults in AuthManager)
AUTH_MAX_LOGIN_ATTEMPTS_USER=5
AUTH_MAX_LOGIN_ATTEMPTS_IP=10
AUTH_LOGIN_WINDOW_MINUTES=15
```

### Generate JWT Keys

```bash
php bin/console lexik:jwt:generate-keypair
```

---

## Testing

### Test User Registration

```bash
curl -X POST http://localhost:8000/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "SecurePass123!",
    "firstname": "Test",
    "lastname": "User"
  }'
```

### Test Login

```bash
curl -X POST http://localhost:8000/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "SecurePass123!"
  }'
```

### Test Protected Endpoint

```bash
curl -X GET http://localhost:8000/auth/me \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

---

## Best Practices

### Frontend Integration

1. **Store Tokens Securely**
   - Use httpOnly cookies for refresh tokens (recommended)
   - Or use secure storage (localStorage/sessionStorage) with XSS protection

2. **Handle Token Refresh**
   - Implement automatic token refresh before expiration
   - Retry failed requests after refreshing token

3. **Logout Cleanup**
   - Clear all stored tokens on logout
   - Redirect to login page

4. **Error Handling**
   - Show user-friendly error messages
   - Handle rate limiting gracefully
   - Guide users to password reset on failed login

### Backend Integration

1. **Use AuthManager for Business Logic**
   - Don't implement auth logic in controllers
   - Leverage rate limiting and validation methods

2. **Monitor Security Events**
   - Review login history regularly
   - Set up alerts for suspicious activity
   - Monitor failed login patterns

3. **Regular Maintenance**
   - Run cleanup command daily
   - Monitor database size
   - Review and update security policies

---

## Support

For issues or questions:
- Check application logs in `var/log/`
- Review login history for security issues
- Contact system administrator

---

**Last Updated:** 2025-11-27
**Version:** 1.0.0
