FROM php:8.1.3-fpm

RUN apt-get update\
    && apt-get install -y \
      curl \
      git \
      wget \
      zip unzip

RUN docker-php-ext-configure pcntl --enable-pcntl \
    && docker-php-ext-install \
    pcntl


COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
