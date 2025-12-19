# --- Stage 1: Node ---
FROM node:20 AS node-builder
WORKDIR /app

COPY composer.json composer.lock ./
COPY package.json package-lock.json ./

RUN npm install

COPY . .

RUN npm run build
# Vueのビルド（Viteを想定）
RUN npm install && npm run build

# --- Stage 2: PHP + Apache (本番環境) ---
FROM php:8.2-apache

# 必要なライブラリのインストール
RUN apt-get update && apt-get install -y \
    zip unzip git curl \
    libpq-dev \
    libpng-dev libonig-dev libxml2-dev \
    && docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd

# Apacheの設定: ドキュメントルートをLaravelのpublicに変更
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Apacheのmod_rewriteを有効化
RUN a2enmod rewrite

# Composerのインストール
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

# Stage 1 でビルドしたVueの成果物（public/build）をコピー
COPY --from=node-builder /app/public/build ./public/build

# Laravelの依存関係をインストール
RUN composer install --no-dev --optimize-autoloader

# 権限の設定
RUN chown -R www-data:www-data storage bootstrap/cache

EXPOSE 80