FROM php:8.2-apache AS app

# Install PHP extensions required by Laravel
RUN apt-get update && apt-get install -y \
    unzip git libzip-dev libicu-dev \
    libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring bcmath exif gd zip intl opcache \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy Laravel source
COPY . .

# Install production dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Build frontend assets
FROM node:22 AS frontend

WORKDIR /app

COPY package*.json ./
RUN npm ci

COPY . .
RUN npm run build

# Add compiled frontend assets to Laravel
FROM app AS production

COPY --from=frontend /app/public/build /var/www/html/public/build

# Configure Apache to serve Laravel's public directory
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|' \
    /etc/apache2/sites-available/000-default.conf \
    && sed -i 's|AllowOverride None|AllowOverride All|' \
    /etc/apache2/apache2.conf \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Render assigns the port through the PORT environment variable
RUN printf '%s\n' \
    '#!/bin/sh' \
    'set -e' \
    'PORT="${PORT:-10000}"' \
    'sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf' \
    'sed -i "s/*:80>/*:${PORT}>/" /etc/apache2/sites-available/000-default.conf' \
    'exec apache2-foreground' \
    > /usr/local/bin/render-start \
    && chmod +x /usr/local/bin/render-start

EXPOSE 10000

CMD ["/usr/local/bin/render-start"]