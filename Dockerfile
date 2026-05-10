# Stage 1: Build JS assets (node:alpine is small and fast)
FROM node:22-alpine AS frontend
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

# Stage 2: PHP runtime (bookworm has libonig-dev for mbstring)
FROM php:8.3-cli-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
    git zip unzip \
    libzip-dev libpng-dev libxml2-dev \
    libfreetype6-dev libjpeg62-turbo-dev \
    libonig-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring xml dom zip bcmath gd \
    && apt-get clean && apt-get autoremove -y

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

COPY . .
COPY --from=frontend /app/public/build ./public/build

RUN composer run-script post-autoload-dump 2>/dev/null || true

EXPOSE 8000

CMD php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan migrate --force && php artisan storage:link --force 2>/dev/null; exec php artisan serve --host=0.0.0.0 --port=$PORT
