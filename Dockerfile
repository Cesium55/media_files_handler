FROM php:8.4-cli-alpine

RUN apk add --no-cache \
    $PHPIZE_DEPS \
    zip unzip curl libpq-dev git ffmpeg \
    nodejs npm \
    linux-headers \
    g++ \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && docker-php-ext-install pdo pdo_pgsql sockets


WORKDIR /var/www

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY . .

RUN composer install

RUN echo "memory_limit=-1" > /usr/local/etc/php/conf.d/memory-limit.ini


RUN echo "upload_max_filesize=2048G" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size=2048G" >> /usr/local/etc/php/conf.d/uploads.ini

EXPOSE 8001

CMD ["composer", "run", "dev"]
