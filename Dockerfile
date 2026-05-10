# Stage 1: Build JS assets
FROM node:22-alpine AS frontend
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

# Stage 2: PHP app
FROM php:8.3-cli-alpine

RUN apk add --no-cache bash git curl zip unzip nodejs npm \
    libzip-dev libpng-dev libxml2-dev freetype-dev libjpeg-turbo-dev \
    oniguruma-dev libcurl openssl-dev icu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring xml dom zip bcmath gd opcache intl

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

COPY . .
COPY --from=frontend /app/public/build ./public/build

RUN composer run-script post-autoload-dump 2>/dev/null || true

EXPOSE 8000

CMD php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan migrate --force && php artisan storage:link --force 2>/dev/null; exec php artisan serve --host=0.0.0.0 --port=$PORT
