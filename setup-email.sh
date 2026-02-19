#!/bin/bash

# CRM API - Email Configuration Setup Script
# This script ensures proper email configuration

echo "🔧 CRM API - Email Configuration Setup"
echo "======================================"
echo ""

# Export MAILER_DSN
export MAILER_DSN="smtp://c090ba90f02bf3:9a70d62ce1bf1b@sandbox.smtp.mailtrap.io:2525"
echo "✅ MAILER_DSN exported"

# Clean cache
echo "🧹 Cleaning cache..."
rm -rf var/cache/* .env.local.php 2>/dev/null
php bin/console cache:clear --no-warmup

echo "✅ Cache cleared"
echo ""

# Test email configuration
echo "📧 Testing email configuration..."
php bin/console app:test-email <<< "test@example.com"

echo ""
echo "======================================"
echo "✨ Email setup complete!"
echo ""
echo "📝 To start the server with proper email config:"
echo "   export MAILER_DSN=\"smtp://c090ba90f02bf3:9a70d62ce1bf1b@sandbox.smtp.mailtrap.io:2525\""
echo "   symfony server:start"
echo ""
echo "OR use this script before starting:"
echo "   source setup-email.sh"
echo "   symfony server:start"
