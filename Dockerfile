# ========== Stage 1: Frontend Build ==========
FROM node:20-alpine AS frontend-builder

WORKDIR /frontend

COPY frontend/package*.json ./
RUN npm ci

COPY frontend .
ENV NODE_OPTIONS="--max-old-space-size=4096"
RUN npm run build

# ========== Stage 2: Laravel with PHP ==========
FROM php:8.2-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    curl \
    git \
    unzip \
    postgresql-dev \
    linux-headers \
    oniguruma-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_pgsql bcmath mbstring fileinfo exif gd

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy backend
COPY backend .

# Install PHP dependencies (without dev)
# Create necessary directories first
RUN mkdir -p storage/framework/cache \
    && mkdir -p storage/framework/sessions \
    && mkdir -p storage/framework/views \
    && mkdir -p storage/logs \
    && mkdir -p storage/app/public/posts \
    && mkdir -p bootstrap/cache \
    && mkdir -p /var/lib/nginx/logs \
    && mkdir -p /run/nginx

RUN composer install --no-dev --optimize-autoloader --no-scripts

# Copy frontend build (merge with existing public, don't overwrite index.php)
COPY --from=frontend-builder /frontend/dist/index.html ./public/index.html
COPY --from=frontend-builder /frontend/dist/assets ./public/assets
COPY --from=frontend-builder /frontend/dist/vite.svg ./public/vite.svg

# Generate package manifest and set permissions
RUN php artisan package:discover --ansi || true \
    && chmod -R 777 storage bootstrap/cache \
    && chown -R nobody:nobody storage bootstrap/cache public /var/lib/nginx /run/nginx

# Configure Nginx
COPY nginx/default.conf /etc/nginx/http.d/default.conf

# Configure PHP-FPM to listen on port 9000
RUN sed -i 's/listen = 127.0.0.1:9000/listen = 9000/' /usr/local/etc/php-fpm.d/www.conf || true

# Increase upload limits
RUN echo "upload_max_filesize = 10M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 12M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/uploads.ini

# Copy startup script
COPY start.sh /start.sh
RUN chmod +x /start.sh

# Set permissions
RUN chown -R nobody:nobody /var/www/html

EXPOSE 8000

CMD ["/start.sh"]
