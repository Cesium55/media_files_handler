FROM php:8.4-cli-alpine

RUN apk add --no-cache \
    $PHPIZE_DEPS \
    zip unzip curl libpq-dev git ffmpeg \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && docker-php-ext-install pdo pdo_pgsql

WORKDIR /var/www

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY . .

RUN composer install

EXPOSE 8001
