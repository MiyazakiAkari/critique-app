#!/bin/sh
# Startup script for AWS Lightsail

echo "🚀 Starting Critique Application..."

# Start PHP-FPM in background
echo "▶️  Starting PHP-FPM..."
php-fpm -D

# Start Nginx in foreground
echo "▶️  Starting Nginx..."
exec nginx -g "daemon off;"
