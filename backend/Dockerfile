# Base image
FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
    bash \
    curl \
    git \
    unzip \
    zip \
    postgresql-dev \
    nodejs \
    npm \
    build-base \
    autoconf \
    re2c \
    libtool \
    make \
    pkgconfig \
    zlib-dev \
    libzip-dev \
    oniguruma-dev \
    libxml2-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install pdo pdo_pgsql pgsql zip gd

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html