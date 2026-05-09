FROM php:8.3-cli-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends git curl zip unzip libzip-dev libpng-dev libxml2-dev libfreetype6-dev libjpeg62-turbo-dev libcurl4-openssl-dev libssl-dev && docker-php-ext-configure gd --with-freetype --with-jpeg && docker-php-ext-install pdo_mysql mbstring xml dom curl zip bcmath gd tokenizer ctype fileinfo opcache

RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - && apt-get install -y nodejs

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

COPY package.json package-lock.json ./
RUN npm ci

COPY . .
RUN composer run-script post-autoload-dump 2>/dev/null || true
RUN npm run build

EXPOSE 8000

CMD php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan migrate --force && php artisan storage:link --force 2>/dev/null; exec php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
