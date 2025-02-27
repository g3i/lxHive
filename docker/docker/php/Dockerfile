FROM php:7.4-fpm

####
# https://hub.docker.com/_/php
# parent image: debian:11-slim
# default config location: /usr/local/etc/php
####

# - import develiper uid, gid form .env
ARG UID
ARG GID

# set php-fpm user to developer user id and group id
RUN usermod -u ${UID} www-data
RUN groupmod -g ${GID} www-data

# - dependencies (developer friendly)
RUN apt-get update \
    && apt-get install -y autoconf pkg-config zip unzip \
    && apt-get install -y gettext curl libssl-dev \
    && apt-get install -y bash git vim nano

# - mongo, etc
RUN pecl install mongodb apcu \
    && docker-php-ext-install opcache \
    && docker-php-ext-enable mongodb apcu opcache

#  - php config
COPY ./docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf

# - composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
RUN mkdir -p /var/www/.composer && chown -R www-data:www-data /var/www/.composer

RUN git config --global --add safe.directory /api/lxHive
RUN git clone https://github.com/g3i/lxHive /api/lxHive

WORKDIR /api
