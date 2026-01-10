#!/bin/sh
# Startup script for AWS Lightsail (Apache version)

echo "🚀 Starting Critique Application (Apache)..."

# Run migrations if DB is configured
if [ -n "$DB_HOST" ]; then
    echo "🔄 Running migrations..."
    php artisan migrate --force || echo "⚠️ Migration failed or already up to date"
    
    echo "🔗 Creating storage symlink..."
    php artisan storage:link || echo "⚠️ Storage link already exists or failed"
    
    echo "📁 Ensuring storage directories exist..."
    mkdir -p storage/app/public/posts
    chmod -R 777 storage/app/public
    
    # Apache runs as www-data, ensure it owns the storage
    chown -R www-data:www-data storage/app/public
fi

# Start Apache in foreground
echo "▶️  Starting Apache..."
exec apache2-foreground
