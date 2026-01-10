# ========== Stage 1: Frontend Build ==========
FROM node:20-alpine AS frontend-builder
WORKDIR /frontend

COPY frontend/package*.json ./
RUN npm ci

COPY frontend .
ENV NODE_OPTIONS="--max-old-space-size=4096"
RUN npm run build

# ========== Stage 2: Laravel with Apache ==========
FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    zip unzip git curl \
    libpng-dev libonig-dev libxml2-dev libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql mbstring bcmath gd

# Configure Apache DocumentRoot
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN a2enmod rewrite

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy backend
COPY backend .
RUN rm -f bootstrap/cache/*.php

# Install PHP dependencies (without dev)
# Create necessary directories first
RUN mkdir -p storage/framework/cache \
    && mkdir -p storage/framework/sessions \
    && mkdir -p storage/framework/views \
    && mkdir -p storage/logs \
    && mkdir -p storage/app/public/posts \
    && mkdir -p bootstrap/cache

RUN composer install --no-dev --optimize-autoloader --no-scripts

# Copy frontend build
COPY --from=frontend-builder /frontend/dist ./public

# PHP Configs (Increase upload limits)
RUN echo "upload_max_filesize = 10M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 12M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/uploads.ini

# Set permissions
RUN chown -R www-data:www-data storage bootstrap/cache public

# Copy startup script
COPY start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 80

CMD ["/start.sh"]
