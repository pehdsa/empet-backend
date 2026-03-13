FROM php:8.4-cli-alpine

RUN apk add --no-cache \
    bash curl git zip unzip \
    icu-dev oniguruma-dev postgresql-dev linux-headers $PHPIZE_DEPS \
 && docker-php-ext-install intl mbstring pdo pdo_pgsql pcntl opcache \
 && pecl install igbinary \
 && pecl install --configureoptions='enable-redis-igbinary="yes"' redis \
 && docker-php-ext-enable igbinary redis \
 && rm -rf /tmp/pear

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

RUN addgroup -g 1000 app && adduser -D -G app -u 1000 app

COPY docker/backend/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]
