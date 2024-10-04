#  _  ___                 _
# | |/ (_)_ __ ___   __ _(_)
# | ' /| | '_ ` _ \ / _` | |
# | . \| | | | | | | (_| | |
# |_|\_\_|_| |_| |_|\__,_|_|
#
# Base images for:
# - plain PHP FPM
# - Apache with PHP
# ---------------------------------------------------------------------
# For production builds:
#
# docker build -t kimai/kimai-base:fpm --build-arg BASE=fpm .
# docker build -t kimai/kimai-base:apache --build-arg BASE=apache .
# ---------------------------------------------------------------------
# For local testing by maintainer:
#
# docker build -t kimai-local-fpm-base --build-arg BASE=fpm .
# docker build -t kimai-local-apache-base --build-arg BASE=apache .
# ---------------------------------------------------------------------
# Documentation:
# https://hub.docker.com/_/php/
# https://github.com/docker-library/docs/blob/master/php/README.md#supported-tags-and-respective-dockerfile-links
# ---------------------------------------------------------------------

ARG BASE="fpm"
ARG TIMEZONE="Europe/Berlin"

###########################
# Shared tools
###########################

# composer base image
FROM composer:latest AS composer

###########################
# PHP extensions
###########################

# fpm alpine php extension base
FROM php:8.3-fpm-alpine AS fpm-php-ext-base
RUN apk add --no-cache \
    # build-tools
    autoconf \
    dpkg \
    dpkg-dev \
    file \
    g++ \
    gcc \
    icu-dev \
    libatomic \
    libc-dev \
    libgomp \
    libmagic \
    m4 \
    make \
    mpc1 \
    mpfr4 \
    musl-dev \
    perl \
    re2c \
    # gd
    freetype-dev \
    libpng-dev \
    # icu
    icu-dev \
    icu-data-full \
    # ldap
    openldap-dev \
    libldap \
    # zip
    libzip-dev \
    # xsl
    libxslt-dev

# apache debian php extension base
FROM php:8.3-apache-bookworm AS apache-php-ext-base
RUN apt-get update
RUN apt-get install -y \
        libldap2-dev \
        libicu-dev \
        libpng-dev \
        libzip-dev \
        libxslt1-dev \
        libfreetype6-dev

# php extension gd - 13.86s
FROM ${BASE}-php-ext-base AS php-ext-gd
RUN docker-php-ext-configure gd \
        --with-freetype && \
    docker-php-ext-install -j$(nproc) gd

# php extension intl : 15.26s
FROM ${BASE}-php-ext-base AS php-ext-intl
RUN docker-php-ext-install -j$(nproc) intl

# php extension ldap : 8.45s
FROM ${BASE}-php-ext-base AS php-ext-ldap
RUN docker-php-ext-configure ldap && \
    docker-php-ext-install -j$(nproc) ldap

# php extension pdo_mysql : 6.14s
FROM ${BASE}-php-ext-base AS php-ext-pdo_mysql
RUN docker-php-ext-install -j$(nproc) pdo_mysql

# php extension zip : 8.18s
FROM ${BASE}-php-ext-base AS php-ext-zip
RUN docker-php-ext-install -j$(nproc) zip

# php extension xsl : ?.?? s
FROM ${BASE}-php-ext-base AS php-ext-xsl
RUN docker-php-ext-install -j$(nproc) xsl

# php extension opcache
FROM ${BASE}-php-ext-base AS php-ext-opcache
RUN docker-php-ext-install -j$(nproc) opcache

###########################
# fpm base build
###########################

# fpm base build
FROM php:8.3-fpm-alpine AS fpm-base
ARG TIMEZONE
RUN apk add --no-cache \
        bash \
        coreutils \
        freetype \
        haveged \
        icu \
        icu-data-full \
        libldap \
        libpng \
        libzip \
        libxslt-dev \
        fcgi \
        tzdata && \
    touch /use_fpm

EXPOSE 9000

HEALTHCHECK --interval=20s --timeout=10s --retries=3 \
    CMD \
    SCRIPT_NAME=/ping \
    SCRIPT_FILENAME=/ping \
    REQUEST_METHOD=GET \
    cgi-fcgi -bind -connect 127.0.0.1:9000 || exit 1

###########################
# apache base build
###########################

FROM php:8.3-apache-bookworm AS apache-base
ARG TIMEZONE
RUN apt-get update && \
    apt-get install -y \
        bash \
        haveged \
        libicu72 \
        libldap-common \
        libpng16-16 \
        libzip4 \
        libxslt1.1 \
        libfreetype6 && \
    echo "Listen 8001" > /etc/apache2/ports.conf && \
    a2enmod rewrite && \
    touch /use_apache

EXPOSE 8001

HEALTHCHECK --interval=20s --timeout=10s --retries=3 \
    CMD curl -f http://127.0.0.1:8001 || exit 1

###########################
# global base build
###########################

FROM ${BASE}-base AS base
ARG TIMEZONE

ENV TIMEZONE=${TIMEZONE}
RUN ln -snf /usr/share/zoneinfo/${TIMEZONE} /etc/localtime && echo ${TIMEZONE} > /etc/timezone && \
    # make composer home dir
    mkdir /composer  && \
    chown -R www-data:www-data /composer && \
    echo "echo \"This image is not intended to be used stand alone. It's a base image for the Kimai project.\"" > /entrypoint.sh && \
    chmod 777 /entrypoint.sh

# copy composer
COPY --from=composer /usr/bin/composer /usr/bin/composer

# copy php extensions

# PHP extension xsl
COPY --from=php-ext-xsl /usr/local/etc/php/conf.d/docker-php-ext-xsl.ini /usr/local/etc/php/conf.d/docker-php-ext-xsl.ini
COPY --from=php-ext-xsl /usr/local/lib/php/extensions/no-debug-non-zts-20230831/xsl.so /usr/local/lib/php/extensions/no-debug-non-zts-20230831/xsl.so
# PHP extension pdo_mysql
COPY --from=php-ext-pdo_mysql /usr/local/etc/php/conf.d/docker-php-ext-pdo_mysql.ini /usr/local/etc/php/conf.d/docker-php-ext-pdo_mysql.ini
COPY --from=php-ext-pdo_mysql /usr/local/lib/php/extensions/no-debug-non-zts-20230831/pdo_mysql.so /usr/local/lib/php/extensions/no-debug-non-zts-20230831/pdo_mysql.so
# PHP extension zip
COPY --from=php-ext-zip /usr/local/etc/php/conf.d/docker-php-ext-zip.ini /usr/local/etc/php/conf.d/docker-php-ext-zip.ini
COPY --from=php-ext-zip /usr/local/lib/php/extensions/no-debug-non-zts-20230831/zip.so /usr/local/lib/php/extensions/no-debug-non-zts-20230831/zip.so
# PHP extension ldap
COPY --from=php-ext-ldap /usr/local/etc/php/conf.d/docker-php-ext-ldap.ini /usr/local/etc/php/conf.d/docker-php-ext-ldap.ini
COPY --from=php-ext-ldap /usr/local/lib/php/extensions/no-debug-non-zts-20230831/ldap.so /usr/local/lib/php/extensions/no-debug-non-zts-20230831/ldap.so
# PHP extension gd
COPY --from=php-ext-gd /usr/local/etc/php/conf.d/docker-php-ext-gd.ini /usr/local/etc/php/conf.d/docker-php-ext-gd.ini
COPY --from=php-ext-gd /usr/local/lib/php/extensions/no-debug-non-zts-20230831/gd.so /usr/local/lib/php/extensions/no-debug-non-zts-20230831/gd.so
# PHP extension intl
COPY --from=php-ext-intl /usr/local/etc/php/conf.d/docker-php-ext-intl.ini /usr/local/etc/php/conf.d/docker-php-ext-intl.ini
COPY --from=php-ext-intl /usr/local/lib/php/extensions/no-debug-non-zts-20230831/intl.so /usr/local/lib/php/extensions/no-debug-non-zts-20230831/intl.so
# PHP extension opcache
COPY --from=php-ext-opcache /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini  /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini

CMD [ "/entrypoint.sh" ]