# ---------- Stage 1: Frontend build ----------
FROM node:20-alpine AS frontend-builder
WORKDIR /frontend

COPY frontend/package.json frontend/package-lock.json ./

# Clear npm cache and install with peer dependency warnings ignored
RUN npm cache clean --force && \
    npm ci --legacy-peer-deps --silent

COPY frontend .
RUN npm run build --silent


# ---------- Stage 2: Laravel ----------
FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    zip unzip git curl \
    libpng-dev libonig-dev libxml2-dev libpq-dev \
 && docker-php-ext-install pdo pdo_pgsql mbstring bcmath gd

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf
RUN a2enmod rewrite

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY backend .

COPY --from=frontend-builder /frontend/dist ./public

RUN composer install --no-dev --optimize-autoloader
RUN chown -R www-data:www-data storage bootstrap/cache

EXPOSE 80
