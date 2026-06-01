FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

COPY . .
RUN composer dump-autoload --optimize


FROM php:8.4-cli-alpine AS production

WORKDIR /app

RUN apk add --no-cache \
    icu-libs \
    libzip \
    postgresql-libs \
    zip \
    unzip \
    && apk add --no-cache --virtual .build-deps \
    icu-dev \
    oniguruma-dev \
    libzip-dev \
    postgresql-dev \
    $PHPIZE_DEPS \
    && docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql \
    intl \
    mbstring \
    zip \
    opcache \
    && apk del .build-deps

COPY --from=vendor /app /app

RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]