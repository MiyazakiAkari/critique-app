# ========== Stage 1: Frontend Build ==========
FROM node:20-alpine AS frontend-builder

WORKDIR /frontend

COPY frontend/package*.json ./
RUN npm ci

COPY frontend .
RUN npm run build

# ========== Stage 2: Laravel with PHP ==========
FROM php:8.2-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    curl \
    git \
    unzip \
    libpq \
    postgresql-client \
    bash \
    && docker-php-ext-install pdo pdo_pgsql bcmath mbstring

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy backend
COPY backend .

# Install PHP dependencies (without dev)
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Copy frontend build
COPY --from=frontend-builder /frontend/dist ./public

# Create necessary directories
RUN mkdir -p storage/framework/cache \
    && mkdir -p storage/framework/sessions \
    && mkdir -p storage/framework/views \
    && mkdir -p storage/logs \
    && chown -R www-data:www-data storage bootstrap/cache public

# Configure Nginx
COPY nginx/default.conf /etc/nginx/http.d/default.conf

# Copy startup script
COPY start.sh /start.sh
RUN chmod +x /start.sh

# Set permissions
RUN chown -R www-data:www-data /var/www/html

USER www-data

EXPOSE 8000

CMD ["/start.sh"]
