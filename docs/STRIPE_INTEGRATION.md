# Stripe Integration Documentation

## Table of Contents
1. [Overview](#overview)
2. [Prerequisites](#prerequisites)
3. [Configuration](#configuration)
4. [Architecture](#architecture)
5. [API Endpoints](#api-endpoints)
6. [Webhook Events](#webhook-events)
7. [Common Workflows](#common-workflows)
8. [Testing](#testing)
9. [Production Deployment](#production-deployment)
10. [Troubleshooting](#troubleshooting)

---

## Overview

This CRM application includes a complete Stripe integration for SaaS subscription billing. The integration handles:

- **Subscription Management**: Create, update, cancel, and reactivate subscriptions
- **Payment Processing**: Secure card payments via Stripe
- **Invoice Generation**: Automatic invoice creation and tracking
- **Quota Management**: Usage-based quotas tied to subscription plans
- **Webhook Handling**: Real-time event processing from Stripe
- **Multi-Tenant Support**: Per-tenant subscriptions and billing

### Key Features

✅ **Multiple Plans**: Support for Free, Pro, and Enterprise plans
✅ **Trial Periods**: Automatic trial management
✅ **Plan Changes**: Seamless upgrades/downgrades with proration
✅ **Payment Methods**: Multiple payment method support
✅ **Automatic Billing**: Recurring charges via Stripe
✅ **Usage Tracking**: Real-time quota monitoring
✅ **Comprehensive Logging**: All operations logged for audit trail

---

## Prerequisites

### Stripe Account
1. Create a Stripe account at https://stripe.com
2. Get your API keys from the Dashboard
3. Set up webhook endpoints
4. Create products and prices in Stripe (or use the sync API)

### Required PHP Packages
```bash
composer require stripe/stripe-php
```

Already included in this project's `composer.json`.

---

## Configuration

### Environment Variables

Add the following to your `.env` or `.env.local`:

```env
# Stripe API Keys
STRIPE_SECRET_KEY=sk_test_...
STRIPE_PUBLISHABLE_KEY=pk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...

# Stripe Webhook URL (for production)
STRIPE_WEBHOOK_URL=https://yourdomain.com/webhooks/stripe

# Frontend URL (for redirects)
FRONTEND_URL=http://localhost:3000
```

### Symfony Services Configuration

The `StripeService` is automatically configured via dependency injection:

```yaml
# config/services.yaml
services:
    App\Service\StripeService:
        arguments:
            $stripeSecretKey: '%env(STRIPE_SECRET_KEY)%'

    App\Controller\StripeWebhookController:
        arguments:
            $stripeWebhookSecret: '%env(STRIPE_WEBHOOK_SECRET)%'
```

---

## Architecture

### Component Overview

```
┌─────────────────────────────────────────────────────────┐
│                    Frontend / Client                     │
└───────────────────────┬─────────────────────────────────┘
                        │
                        │ REST API Calls
                        ▼
┌─────────────────────────────────────────────────────────┐
│              Controllers (API Layer)                     │
│  - PlanController                                        │
│  - SubscriptionController                               │
│  - StripeWebhookController                              │
└───────────────────────┬─────────────────────────────────┘
                        │
                        │ Business Logic
                        ▼
┌─────────────────────────────────────────────────────────┐
│                  Managers (Business Layer)               │
│  - SubscriptionManager                                   │
│  - BillingManager                                        │
│  - QuotaManager                                          │
└───────────────────────┬─────────────────────────────────┘
                        │
        ┌───────────────┴────────────────┐
        │                                │
        ▼                                ▼
┌──────────────────┐          ┌──────────────────┐
│   StripeService  │          │   Repositories   │
│  (Stripe API)    │          │   (Database)     │
└────────┬─────────┘          └──────────────────┘
         │
         │ Stripe API Calls
         ▼
┌─────────────────────────────────────────────────────────┐
│                      Stripe Platform                     │
│  - Subscriptions                                         │
│  - Invoices                                              │
│  - Payments                                              │
│  - Webhooks                                              │
└─────────────────────────────────────────────────────────┘
```

### Database Entities

- **Plan**: Subscription plan definitions (price, quotas, features)
- **Subscription**: Tenant subscriptions with status and billing info
- **Invoice**: Generated invoices for subscriptions
- **Payment**: Payment records linked to invoices
- **Quota**: Real-time usage tracking per tenant
- **UsageMetric**: Historical usage metrics

---

## API Endpoints

### Plans API

#### List All Plans
```http
GET /api/plans/list
```

**Response:**
```json
{
  "status": "success",
  "data": [
    {
      "id": "uuid",
      "code": "pro_monthly",
      "name": "Pro Monthly",
      "description": "Professional plan billed monthly",
      "price": 49.99,
      "currency": "USD",
      "billing_interval": "monthly",
      "trial_days": 14,
      "is_active": true,
      "is_popular": true,
      "quotas": {
        "max_contacts": 10000,
        "max_companies": 2000,
        "max_deals": 500,
        "max_users": 10,
        "max_storage_mb": 10240,
        "max_api_calls_per_day": 10000
      },
      "features": [
        "Advanced reporting",
        "Custom fields",
        "API access",
        "Priority support"
      ]
    }
  ]
}
```

#### Get Plan by Code
```http
GET /api/plans/{code}
```

**Example:**
```bash
curl -X GET https://api.example.com/api/plans/pro_monthly
```

#### Compare Plans
```http
GET /api/plans/compare
```

Returns a feature comparison matrix useful for pricing pages.

---

### Subscription API

#### Get Current Subscription
```http
GET /api/subscription/current
Headers:
  X-Tenant-ID: tenant_123
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "id": "uuid",
    "tenant_id": "tenant_123",
    "status": "active",
    "plan": {
      "code": "pro_monthly",
      "name": "Pro Monthly",
      "price": 49.99,
      "currency": "USD",
      "billing_interval": "monthly"
    },
    "trial": {
      "is_in_trial": false,
      "trial_ends_at": null,
      "days_remaining": 0
    },
    "billing": {
      "current_period_start": "2025-01-01 00:00:00",
      "current_period_end": "2025-02-01 00:00:00",
      "days_until_renewal": 15,
      "cancel_at_period_end": false,
      "cancels_at": null
    },
    "stripe": {
      "subscription_id": "sub_1234567890",
      "customer_id": "cus_1234567890"
    }
  }
}
```

#### Subscribe to a Plan
```http
POST /api/subscription/subscribe
Headers:
  X-Tenant-ID: tenant_123
  Content-Type: application/json

Body:
{
  "plan_code": "pro_monthly",
  "email": "user@example.com",
  "payment_method_id": "pm_1234567890"
}
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "subscription": { /* subscription data */ },
    "client_secret": "pi_1234567890_secret_abcdef"
  },
  "message": "Subscription created successfully"
}
```

**Notes:**
- `payment_method_id` is obtained from Stripe Elements on the frontend
- `client_secret` is used to confirm payment on the frontend
- Trial period is automatically applied if configured on the plan

#### Change Plan
```http
POST /api/subscription/change-plan
Headers:
  X-Tenant-ID: tenant_123
  Content-Type: application/json

Body:
{
  "new_plan_code": "enterprise_monthly"
}
```

**Notes:**
- Stripe automatically calculates proration
- Quotas are updated immediately
- If upgrading, additional charge is created
- If downgrading, credit is applied to next invoice

#### Cancel Subscription
```http
POST /api/subscription/cancel
Headers:
  X-Tenant-ID: tenant_123
  Content-Type: application/json

Body:
{
  "immediately": false
}
```

**Options:**
- `immediately: false` - Cancel at period end (default)
- `immediately: true` - Cancel immediately

#### Reactivate Subscription
```http
POST /api/subscription/reactivate
Headers:
  X-Tenant-ID: tenant_123
```

Only works if subscription is scheduled for cancellation (cancel_at_period_end = true).

#### Get Usage & Quotas
```http
GET /api/subscription/usage
Headers:
  X-Tenant-ID: tenant_123
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "subscription": {
      "plan": "Pro Monthly",
      "status": "active"
    },
    "quotas": [
      {
        "quota_type": "contacts",
        "limit": 10000,
        "current_usage": 2543,
        "percentage": 25.43,
        "is_exceeded": false,
        "remaining": 7457,
        "is_hard_limit": true
      },
      {
        "quota_type": "storage_bytes",
        "limit": 10737418240,
        "current_usage": 5368709120,
        "percentage": 50.0,
        "is_exceeded": false,
        "remaining": 5368709120,
        "is_hard_limit": true
      }
    ]
  }
}
```

#### Get Billing History
```http
GET /api/subscription/billing-history
Headers:
  X-Tenant-ID: tenant_123
```

Returns all invoices for the subscription.

#### Payment Methods

**List Payment Methods:**
```http
GET /api/subscription/payment-methods
Headers:
  X-Tenant-ID: tenant_123
```

**Add Payment Method:**
```http
POST /api/subscription/payment-methods/add
Headers:
  X-Tenant-ID: tenant_123
  Content-Type: application/json

Body:
{
  "payment_method_id": "pm_1234567890"
}
```

**Remove Payment Method:**
```http
DELETE /api/subscription/payment-methods/{paymentMethodId}
```

---

## Webhook Events

### Configuration

1. **In Stripe Dashboard:**
   - Go to Developers → Webhooks
   - Add endpoint: `https://yourdomain.com/webhooks/stripe`
   - Select events to listen to (see below)
   - Copy the signing secret

2. **Add to .env:**
   ```env
   STRIPE_WEBHOOK_SECRET=whsec_...
   ```

### Supported Events

The `StripeWebhookController` handles these events:

#### Subscription Events
- `customer.subscription.created` - Subscription created in Stripe
- `customer.subscription.updated` - Subscription status changed
- `customer.subscription.deleted` - Subscription canceled
- `customer.subscription.trial_will_end` - Trial ending soon (3 days)

#### Invoice Events
- `invoice.created` - Invoice draft created
- `invoice.finalized` - Invoice finalized and ready to pay
- `invoice.paid` - Invoice paid successfully
- `invoice.payment_failed` - Invoice payment failed
- `invoice.payment_action_required` - Requires customer action (3D Secure)

#### Payment Events
- `payment_intent.succeeded` - Payment successful
- `payment_intent.payment_failed` - Payment failed

#### Customer Events
- `customer.created` - Customer created
- `customer.updated` - Customer updated
- `customer.deleted` - Customer deleted

#### Payment Method Events
- `payment_method.attached` - Payment method attached to customer
- `payment_method.detached` - Payment method removed

### Event Processing

All webhook events are:
1. **Verified** - Signature verification prevents unauthorized requests
2. **Logged** - All events logged with full context
3. **Processed** - Database updated to match Stripe state
4. **Error Handled** - Failures logged but don't block webhook response

**Example webhook log:**
```
[INFO] Stripe webhook received - type: invoice.paid, id: evt_1234567890
[INFO] Invoice marked as paid - invoice_id: uuid, stripe_invoice_id: in_1234567890
```

---

## Common Workflows

### 1. New Customer Subscription

**Frontend Flow:**
```javascript
// 1. Collect payment method using Stripe Elements
const {paymentMethod} = await stripe.createPaymentMethod({
  type: 'card',
  card: cardElement,
});

// 2. Subscribe via API
const response = await fetch('/api/subscription/subscribe', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-Tenant-ID': 'tenant_123'
  },
  body: JSON.stringify({
    plan_code: 'pro_monthly',
    email: 'user@example.com',
    payment_method_id: paymentMethod.id
  })
});

const {data} = await response.json();

// 3. Confirm payment if needed (3D Secure)
if (data.client_secret) {
  const {error} = await stripe.confirmCardPayment(data.client_secret);
  if (error) {
    // Handle error
  }
}
```

**Backend Flow:**
1. `SubscriptionController::subscribe()` receives request
2. Creates/retrieves Stripe customer via `StripeService::createOrGetCustomer()`
3. Creates Stripe subscription via `StripeService::createSubscription()`
4. Creates subscription in database via `SubscriptionManager::createSubscription()`
5. Initializes quotas via `QuotaManager::initializeQuotasForSubscription()`
6. Returns subscription data + client_secret

**Webhook Flow:**
1. Stripe sends `customer.subscription.created` webhook
2. `StripeWebhookController::handleSubscriptionCreated()` logs the event
3. Subscription already exists (created in step 4), so just log

### 2. Plan Change (Upgrade/Downgrade)

**API Call:**
```bash
curl -X POST https://api.example.com/api/subscription/change-plan \
  -H "Content-Type: application/json" \
  -H "X-Tenant-ID: tenant_123" \
  -d '{"new_plan_code": "enterprise_monthly"}'
```

**Backend Flow:**
1. `SubscriptionController::changePlan()` receives request
2. Updates Stripe subscription via `StripeService::updateSubscription()`
3. Updates subscription in database via `SubscriptionManager::changePlan()`
4. Updates quotas via `QuotaManager::initializeQuotasForSubscription()`
5. Returns updated subscription data

**Webhook Flow:**
1. Stripe sends `customer.subscription.updated` webhook
2. `StripeWebhookController::handleSubscriptionUpdated()` processes event
3. Updates subscription status, periods, and cancel info

### 3. Failed Payment Recovery

**Automatic Flow:**
1. Stripe attempts to charge customer
2. Payment fails (insufficient funds, expired card, etc.)
3. Stripe sends `invoice.payment_failed` webhook
4. `StripeWebhookController::handleInvoicePaymentFailed()` processes event
5. Subscription marked as `past_due` via `SubscriptionManager::markAsPastDue()`
6. (TODO) Email notification sent to customer

**Customer Action:**
1. Customer updates payment method
2. Stripe automatically retries payment
3. If successful, `invoice.paid` webhook received
4. Subscription reactivated via `SubscriptionManager::activateSubscription()`

### 4. Trial to Paid Conversion

**Day 0: Trial Start**
- Customer subscribes with `trial_days: 14`
- Subscription status: `trialing`
- No charge created

**Day 11: Trial Ending Soon**
1. Stripe sends `customer.subscription.trial_will_end` webhook
2. `StripeWebhookController::handleTrialWillEnd()` processes event
3. (TODO) Email notification sent to customer

**Day 14: Trial Ends**
1. Stripe automatically charges customer
2. If successful:
   - `invoice.paid` webhook → subscription activated
   - Status changes from `trialing` to `active`
3. If failed:
   - `invoice.payment_failed` webhook → subscription marked `past_due`
   - Customer has grace period to update payment method

---

## Testing

### Test Mode

Use Stripe test keys for development:

```env
STRIPE_SECRET_KEY=sk_test_...
STRIPE_PUBLISHABLE_KEY=pk_test_...
```

### Test Cards

Stripe provides test card numbers:

| Card Number         | Scenario                    |
|--------------------|-----------------------------|
| 4242 4242 4242 4242 | Successful payment          |
| 4000 0027 6000 3184 | Requires 3D Secure          |
| 4000 0000 0000 0002 | Card declined               |
| 4000 0000 0000 9995 | Insufficient funds          |

Use any future expiration date and any 3-digit CVC.

### Testing Webhooks Locally

**Option 1: Stripe CLI**
```bash
# Install Stripe CLI
brew install stripe/stripe-cli/stripe

# Login
stripe login

# Forward webhooks to local server
stripe listen --forward-to http://localhost:8000/webhooks/stripe

# Test specific event
stripe trigger customer.subscription.created
```

**Option 2: ngrok**
```bash
# Expose local server
ngrok http 8000

# Use ngrok URL in Stripe webhook settings
https://abc123.ngrok.io/webhooks/stripe
```

### Manual Testing Scenarios

**1. Create Subscription:**
```bash
curl -X POST http://localhost:8000/api/subscription/subscribe \
  -H "Content-Type: application/json" \
  -H "X-Tenant-ID: test_tenant" \
  -d '{
    "plan_code": "pro_monthly",
    "email": "test@example.com",
    "payment_method_id": "pm_card_visa"
  }'
```

**2. Get Current Subscription:**
```bash
curl -X GET http://localhost:8000/api/subscription/current \
  -H "X-Tenant-ID: test_tenant"
```

**3. Check Quotas:**
```bash
curl -X GET http://localhost:8000/api/subscription/usage \
  -H "X-Tenant-ID: test_tenant"
```

**4. Change Plan:**
```bash
curl -X POST http://localhost:8000/api/subscription/change-plan \
  -H "Content-Type: application/json" \
  -H "X-Tenant-ID: test_tenant" \
  -d '{"new_plan_code": "enterprise_monthly"}'
```

**5. Cancel Subscription:**
```bash
curl -X POST http://localhost:8000/api/subscription/cancel \
  -H "Content-Type: application/json" \
  -H "X-Tenant-ID: test_tenant" \
  -d '{"immediately": false}'
```

---

## Production Deployment

### Pre-Launch Checklist

- [ ] Switch to live Stripe API keys
- [ ] Configure webhook endpoint in Stripe Dashboard
- [ ] Set `STRIPE_WEBHOOK_SECRET` from live webhook
- [ ] Create actual products and prices in Stripe
- [ ] Update plans in database with Stripe product/price IDs
- [ ] Test all payment flows with real test cards
- [ ] Configure email notifications for billing events
- [ ] Set up monitoring and alerting for failed payments
- [ ] Review Stripe Dashboard settings (retry logic, emails, etc.)
- [ ] Document customer support procedures

### Environment Variables (Production)

```env
# Stripe Live Keys
STRIPE_SECRET_KEY=sk_live_...
STRIPE_PUBLISHABLE_KEY=pk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...

# Production URLs
STRIPE_WEBHOOK_URL=https://api.yourdomain.com/webhooks/stripe
FRONTEND_URL=https://app.yourdomain.com
```

### Webhook Configuration

1. **Create webhook in Stripe Dashboard:**
   - URL: `https://api.yourdomain.com/webhooks/stripe`
   - API Version: Latest
   - Events: Select all subscription, invoice, and payment events

2. **Test webhook:**
   ```bash
   stripe webhooks trigger customer.subscription.created \
     --endpoint https://api.yourdomain.com/webhooks/stripe
   ```

### Monitoring

**Key Metrics to Monitor:**
- Webhook delivery success rate
- Failed payment rate
- Subscription churn rate
- MRR (Monthly Recurring Revenue)
- Quota usage trends

**Logs to Watch:**
```bash
# Watch webhook logs
tail -f var/log/prod.log | grep "Stripe webhook"

# Watch failed payments
tail -f var/log/prod.log | grep "payment_failed"

# Watch subscription changes
tail -f var/log/prod.log | grep "Subscription"
```

### Stripe Dashboard

Regularly review:
- **Payments** → Recent charges and refunds
- **Subscriptions** → Active, trialing, past due
- **Invoices** → Paid, open, uncollectible
- **Webhooks** → Delivery status and failures
- **Logs** → API calls and errors

---

## Troubleshooting

### Webhook Not Receiving Events

**Symptoms:**
- Events visible in Stripe Dashboard
- No logs in application

**Solutions:**
1. Verify webhook URL is correct
2. Check firewall allows Stripe IPs
3. Test webhook with Stripe CLI
4. Verify `STRIPE_WEBHOOK_SECRET` matches Dashboard
5. Check webhook endpoint is accessible (no authentication required)

**Debug:**
```bash
# Check if webhook endpoint is accessible
curl -X POST https://api.yourdomain.com/webhooks/stripe \
  -H "Stripe-Signature: test"

# Should return 400 (signature verification failed), not 404 or 500
```

### Webhook Signature Verification Failed

**Error:**
```
Stripe webhook signature verification failed
```

**Solutions:**
1. Verify `STRIPE_WEBHOOK_SECRET` is correct
2. Check you're using the signing secret from the webhook, not API keys
3. Ensure request body is not modified before verification
4. Use raw request body (not parsed JSON)

**Code check:**
```php
// In StripeWebhookController
$payload = $request->getContent(); // ✅ Raw body
// NOT: $payload = json_encode(json_decode($request->getContent())); // ❌
```

### Payment Failed - Insufficient Funds

**Customer sees:**
```
Your card has insufficient funds.
```

**Backend logs:**
```
[WARNING] Invoice payment failed - subscription_id: uuid, stripe_invoice_id: in_1234
```

**Actions:**
1. Subscription automatically marked as `past_due`
2. Customer should update payment method
3. Stripe retries payment automatically (default: 4 times over 3 weeks)
4. Send email notification to customer (implement TODO)

### Subscription Not Activating After Payment

**Symptoms:**
- Payment succeeded in Stripe
- Subscription still shows `trialing` or `past_due`

**Debug:**
1. Check webhook logs for `invoice.paid` event
2. Verify webhook was processed successfully
3. Check subscription status in database
4. Manually trigger activation:
   ```php
   $subscriptionManager->activateSubscription($subscription);
   ```

### Quota Not Updating After Plan Change

**Symptoms:**
- Plan changed successfully
- Quotas still reflect old plan

**Solutions:**
1. Check `QuotaManager::initializeQuotasForSubscription()` was called
2. Verify quotas exist in database for tenant
3. Manually re-initialize quotas:
   ```bash
   php bin/console app:quota:init-for-tenant tenant_123
   ```

### Proration Charges Unexpected

**Customer complaint:**
```
"I was charged $XX when changing plans"
```

**Explanation:**
- Stripe automatically prorates plan changes
- Upgrade: Immediate charge for remaining period
- Downgrade: Credit applied to next invoice

**Example:**
- Currently on Pro Monthly ($50/month)
- 15 days into billing period
- Upgrade to Enterprise Monthly ($100/month)
- Proration charge: ~$25 (half of $50 difference)

**Disable proration:**
```php
// In StripeService::updateSubscription()
'proration_behavior' => 'none', // or 'create_prorations'
```

### Testing Failed Payments

**Test cards for specific scenarios:**

```php
// Stripe test cards
const TEST_CARDS = [
    'success' => '4242424242424242',
    'declined' => '4000000000000002',
    'insufficient_funds' => '4000000000009995',
    'expired_card' => '4000000000000069',
    'incorrect_cvc' => '4000000000000127',
    'processing_error' => '4000000000000119',
    'requires_3ds' => '4000002760003184',
];
```

**Trigger failed payment:**
1. Subscribe with `4000000000000002` (declined card)
2. Webhook: `invoice.payment_failed`
3. Subscription marked as `past_due`

### Common Errors and Fixes

| Error | Cause | Fix |
|-------|-------|-----|
| `No such customer` | Stripe customer deleted | Recreate customer |
| `No such subscription` | Subscription canceled in Stripe | Sync database |
| `Invalid source` | Payment method expired | Update payment method |
| `Plan not found` | Plan code typo | Verify plan code |
| `Quota exceeded` | Usage limit reached | Upgrade plan |

---

## Advanced Configuration

### Custom Email Notifications

Implement email notifications for billing events:

```php
// In StripeWebhookController

private function handleInvoicePaymentFailed(\Stripe\Event $event): void
{
    // ... existing code ...

    // Send email notification
    $this->emailService->send([
        'to' => $subscription->getTenant()->getEmail(),
        'template' => 'billing/payment_failed',
        'context' => [
            'subscription' => $subscription,
            'invoice' => $invoice,
        ]
    ]);
}
```

### Cron Jobs for Maintenance

Create Symfony commands for:

1. **Check Expiring Trials:**
```bash
php bin/console app:subscriptions:check-trials
```

2. **Process Quota Resets:**
```bash
php bin/console app:quotas:reset-daily
```

3. **Sync with Stripe:**
```bash
php bin/console app:stripe:sync-subscriptions
```

### Rate Limiting

Implement rate limiting on API endpoints:

```php
use Symfony\Component\RateLimiter\RateLimiterFactory;

#[Route('/api/subscription/subscribe')]
public function subscribe(Request $request, RateLimiterFactory $apiLimiter)
{
    $limiter = $apiLimiter->create($request->getClientIp());

    if (!$limiter->consume(1)->isAccepted()) {
        return $this->json(['error' => 'Too many requests'], 429);
    }

    // ... rest of method
}
```

---

## Security Best Practices

1. **Always verify webhook signatures** - Prevents unauthorized events
2. **Use HTTPS in production** - Protects API keys and customer data
3. **Store API keys in environment** - Never commit to version control
4. **Implement rate limiting** - Prevents abuse
5. **Log all billing events** - Audit trail for disputes
6. **Validate tenant ownership** - Prevent cross-tenant access
7. **Use Stripe test mode** - For development and testing

---

## Resources

### Stripe Documentation
- [Subscriptions Guide](https://stripe.com/docs/billing/subscriptions/overview)
- [Webhooks Guide](https://stripe.com/docs/webhooks)
- [Testing Guide](https://stripe.com/docs/testing)
- [API Reference](https://stripe.com/docs/api)

### Internal Documentation
- [SaaS Billing System](../SAAS_BILLING_SYSTEM.md)
- [API Documentation](../API_DOCUMENTATION.md)
- [Authentication Testing](../AUTH_TESTING_GUIDE.md)

### Support
- Stripe Support: https://support.stripe.com
- Stripe Status: https://status.stripe.com

---

**Last Updated:** 2025-11-29
**Version:** 1.0
**Maintainer:** Development Team
