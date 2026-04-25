ARG PHP_VERSION=8.4

FROM php:${PHP_VERSION}-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
    && rm -rf /var/lib/apt/lists/*

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

ENV XDEBUG_MODE=coverage

RUN curl -sS https://getcomposer.org/installer \
    | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /app
