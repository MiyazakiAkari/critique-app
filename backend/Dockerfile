# ベースイメージ
FROM php:8.2-fpm

# 作業ディレクトリ
WORKDIR /var/www/html

# 必要なパッケージインストール
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    libzip-dev \
    zip \
    && docker-php-ext-install pdo pdo_pgsql zip

# Composer インストール
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# 権限設定
RUN chown -R www-data:www-data /var/www/html
