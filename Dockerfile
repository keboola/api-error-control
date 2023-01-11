ARG PHP_VERSION=8.0
FROM php:${PHP_VERSION}-cli as dev

ENV COMPOSER_ALLOW_SUPERUSER 1

WORKDIR /code

RUN apt-get update && apt-get install -y \
        git \
        unzip \
   --no-install-recommends && rm -r /var/lib/apt/lists/*

COPY ./docker/php/php.ini /usr/local/etc/php/php.ini

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin/ --filename=composer

RUN if [$PHP_VERSION == 8.0]; then pecl install xdebug \
 && docker-php-ext-enable xdebug; fi;

FROM dev as ci
COPY composer.* ./
RUN composer install $COMPOSER_FLAGS --no-scripts --no-autoloader
COPY . .
RUN composer install $COMPOSER_FLAGS
