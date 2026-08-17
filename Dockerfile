#  _  ___                 _
# | |/ (_)_ __ ___   __ _(_)
# | ' /| | '_ ` _ \ / _` | |
# | . \| | | | | | | (_| | |
# |_|\_\_|_| |_| |_|\__,_|_|
#
# Kimai images for:
# - Apache with PHP (kimai/kimai2:stable)
# - Development     (kimai/kimai2:dev)
# ---------------------------------------------------------------------
# For local testing by maintainer:
#
# docker build --no-cache -t kimai-local .
# docker run -d --name kimai-local-app kimai-local
# docker exec -ti kimai-local-app /bin/bash
# ---------------------------------------------------------------------
# Official PHP images: https://hub.docker.com/_/php/
# https://github.com/docker-library/docs/blob/master/php/README.md#supported-tags-and-respective-dockerfile-links
# Best practices: https://docs.docker.com/build/building/best-practices/
# ---------------------------------------------------------------------

ARG KIMAI="dev"
ARG TIMEZONE="Europe/Berlin"

###########################
# Shared tools
###########################

FROM composer:latest AS composer

###########################
# Runtime base
###########################

FROM php:8.5-apache AS runtime
ARG KIMAI
ARG TIMEZONE

RUN apt-get update && \
    apt-get install -y --no-install-recommends \
        supervisor \
        unzip \
        git \
        libicu76 \
        libldap-common \
        libzip5 \
        libxslt1.1 \
        libldap2-dev \
        libicu-dev \
        libzip-dev \
        libxslt1-dev \
        libpng16-16t64 \
        libpng-dev \
        libfreetype6 \
        libfreetype6-dev \
        libjpeg62-turbo \
        libjpeg62-turbo-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install -j$(nproc) intl \
    && docker-php-ext-configure ldap \
    && docker-php-ext-install -j$(nproc) ldap \
    && docker-php-ext-install -j$(nproc) pdo_mysql \
    && docker-php-ext-install -j$(nproc) zip \
    && docker-php-ext-install -j$(nproc) xsl \
    && apt-get remove -y \
        libldap2-dev \
        libicu-dev \
        libzip-dev \
        libxslt1-dev \
        libpng-dev \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
    && apt-get autoremove -y \
    && rm -rf /var/lib/apt/lists/* \
    && ln -snf /usr/share/zoneinfo/${TIMEZONE} /etc/localtime && echo ${TIMEZONE} > /etc/timezone \
    && echo "Listen 8001" > /etc/apache2/ports.conf \
    && a2enmod rewrite

COPY .docker/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY .docker/dbtest.php /dbtest.php
COPY .docker/entrypoint.sh /entrypoint.sh
COPY .docker/supervisord.conf /etc/supervisor/supervisord.conf
COPY --from=composer /usr/bin/composer /usr/bin/composer

EXPOSE 8001

HEALTHCHECK --interval=20s --timeout=10s --retries=3 \
    CMD curl -f http://127.0.0.1:8001 || exit 1

LABEL org.opencontainers.image.title="Kimai" \
      org.opencontainers.image.description="Kimai is a time-tracking application." \
      org.opencontainers.image.authors="Kimai Community" \
      org.opencontainers.image.url="https://www.kimai.org/" \
      org.opencontainers.image.documentation="https://www.kimai.org/documentation/" \
      org.opencontainers.image.source="https://github.com/kimai/kimai" \
      org.opencontainers.image.version="${KIMAI}" \
      org.opencontainers.image.vendor="Kevin Papst" \
      org.opencontainers.image.licenses="AGPL-3.0"

ENV KIMAI=${KIMAI} \
    TIMEZONE=${TIMEZONE} \
    DATABASE_URL="mysql://kimai:kimai@127.0.0.1:3306/kimai?charset=utf8mb4&serverVersion=10.6.27-MariaDB" \
    TRUSTED_PROXIES=nginx,localhost,127.0.0.1 \
    MAILER_FROM=kimai@example.com \
    MAILER_URL=null://localhost \
    ADMINPASS= \
    ADMINMAIL= \
    USER_ID= \
    GROUP_ID= \
    COMPOSER_HOME=/tmp/composer \
    COMPOSER_MEMORY_LIMIT=-1 \
    COMPOSER_ALLOW_SUPERUSER=1

ENTRYPOINT [ "/entrypoint.sh" ]
CMD [ "/usr/sbin/apache2", "-D", "FOREGROUND" ]

###########################
# Development build
###########################

FROM runtime AS dev

COPY --chown=www-data:www-data . /opt/kimai
COPY .docker/monolog.yaml /assets/monolog.yaml
COPY .docker/php.ini-development /usr/local/etc/php/conf.d/kimai.ini

RUN cp /usr/local/etc/php/php.ini-development /usr/local/etc/php/php.ini && \
    composer --no-ansi install --working-dir=/opt/kimai --optimize-autoloader && \
    composer --no-ansi require --working-dir=/opt/kimai laminas/laminas-ldap && \
    rm -rf /tmp/composer && \
    mkdir -p /opt/kimai/var/log && chmod 777 /opt/kimai/var/log && \
    /opt/kimai/bin/console kimai:version | awk '{print $2}' > /opt/kimai/version.txt

ENV APP_ENV=dev \
    DATABASE_URL=

###########################
# Production build (default)
###########################

FROM runtime AS prod

COPY --chown=www-data:www-data . /opt/kimai
COPY .docker/monolog.yaml /assets/monolog.yaml
COPY .docker/php.ini-production /usr/local/etc/php/conf.d/kimai.ini

RUN cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini && \
    composer --no-ansi install --working-dir=/opt/kimai --no-dev --optimize-autoloader && \
    composer --no-ansi require --update-no-dev --working-dir=/opt/kimai laminas/laminas-ldap && \
    rm -rf /tmp/composer && \
    mkdir -p /opt/kimai/var/log && chmod 777 /opt/kimai/var/log && \
    /opt/kimai/bin/console kimai:version | awk '{print $2}' > /opt/kimai/version.txt

ENV APP_ENV=prod \
    DATABASE_URL=
