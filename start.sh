#!/bin/sh
# Startup script for AWS Lightsail
# This script starts the application (migrations are optional)

set -e

echo "🚀 Starting Critique Application..."

# Check if database environment variables are set
if [ -n "$DB_HOST" ] && [ -n "$DB_DATABASE" ]; then
    echo "⏳ Waiting for database connection..."
    max_attempts=10
    attempt=1

    while [ $attempt -le $max_attempts ]; do
        if php artisan tinker --execute="DB::connection()->getPdo();" 2>/dev/null; then
            echo "✅ Database connection established"
            
            # Run migrations
            echo "🔄 Running migrations..."
            php artisan migrate --force
            break
        fi
        echo "⏳ Attempt $attempt/$max_attempts - Waiting for database..."
        sleep 3
        attempt=$((attempt + 1))
    done

    if [ $attempt -gt $max_attempts ]; then
        echo "⚠️ Could not connect to database, starting without migrations"
    fi
else
    echo "ℹ️ No database configured, skipping migrations"
fi

# Cache config (skip if no APP_KEY)
if [ -n "$APP_KEY" ]; then
    echo "🔧 Caching configuration..."
    php artisan config:cache 2>/dev/null || true
    php artisan route:cache 2>/dev/null || true
fi

# Start PHP-FPM in background
echo "▶️  Starting PHP-FPM..."
php-fpm -D

# Start Nginx in foreground
echo "▶️  Starting Nginx..."
exec nginx -g "daemon off;"
