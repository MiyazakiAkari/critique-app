#!/bin/bash
# Koyeb startup script
# This script runs migrations and starts the application

set -e

echo "🚀 Starting Critique Application..."

# Check if database connection is available
echo "⏳ Waiting for database connection..."
max_attempts=30
attempt=1

while [ $attempt -le $max_attempts ]; do
    if php artisan tinker --execute="DB::connection()->getPdo();" 2>/dev/null; then
        echo "✅ Database connection established"
        break
    fi
    echo "⏳ Attempt $attempt/$max_attempts - Waiting for database..."
    sleep 2
    attempt=$((attempt + 1))
done

if [ $attempt -gt $max_attempts ]; then
    echo "❌ Failed to connect to database after $max_attempts attempts"
    exit 1
fi

# Run migrations
echo "🔄 Running migrations..."
php artisan migrate --force

# Run cache config
echo "🔧 Caching configuration..."
php artisan config:cache
php artisan route:cache

# Start PHP-FPM in background
echo "▶️  Starting PHP-FPM..."
php-fpm -D

# Start Nginx in foreground
echo "▶️  Starting Nginx..."
exec nginx -g "daemon off;"
