#!/bin/sh
# Startup script for AWS Lightsail

echo "🚀 Starting Critique Application..."

# Run migrations if DB is configured
if [ -n "$DB_HOST" ]; then
    echo "🔄 Running migrations..."
    php artisan migrate --force || echo "⚠️ Migration failed or already up to date"
    
    echo "🔗 Creating storage symlink..."
    php artisan storage:link || echo "⚠️ Storage link already exists or failed"
    
    echo "📁 Ensuring storage directories exist..."
    mkdir -p storage/app/public/posts
    chmod -R 777 storage/app/public
fi

# Start PHP-FPM in background
echo "▶️  Starting PHP-FPM..."
php-fpm -D

# Start Nginx in foreground
echo "▶️  Starting Nginx..."
exec nginx -g "daemon off;"

