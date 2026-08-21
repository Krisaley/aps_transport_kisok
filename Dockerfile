FROM composer:2 AS vendor
WORKDIR /src
COPY transport/composer.json transport/composer.lock ./
RUN --mount=type=secret,id=composer_auth,required=false sh -c 'if [ -f /run/secrets/composer_auth ]; then export COMPOSER_AUTH="$(cat /run/secrets/composer_auth)"; fi; composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts'

FROM node:24-bookworm-slim AS assets
WORKDIR /src
COPY transport/package*.json ./
RUN npm ci
COPY transport/resources resources
COPY transport/vite.config.js ./
COPY --from=vendor /src/vendor vendor
RUN npm run build

FROM php:8.4-fpm-bookworm AS app
RUN apt-get update && apt-get install -y --no-install-recommends libicu-dev libpq-dev libzip-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev unzip curl && docker-php-ext-configure gd --with-freetype --with-jpeg && docker-php-ext-install -j$(nproc) bcmath exif gd intl opcache pcntl pdo_pgsql zip && pecl install redis-6.3.0 && docker-php-ext-enable redis && rm -rf /var/lib/apt/lists/*
WORKDIR /var/www/html
COPY transport/ .
COPY --from=vendor /src/vendor vendor
COPY --from=assets /src/public/build public/build
COPY docker/php/php.ini /usr/local/etc/php/conf.d/transport.ini
RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && php artisan package:discover --ansi
USER www-data
HEALTHCHECK --interval=15s --timeout=5s --retries=8 CMD php -r 'exit(@fsockopen("127.0.0.1",9000)?0:1);'

FROM nginx:1.28-alpine AS web
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY transport/public /var/www/html/public
COPY --from=assets /src/public/build /var/www/html/public/build
