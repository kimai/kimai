#  _  ___                 _
# | |/ (_)_ __ ___   __ _(_)
# | ' /| | '_ ` _ \ / _` | |
# | . \| | | | | | | (_| | |
# |_|\_\_|_| |_| |_|\__,_|_|
#
# Kimai images for:
# - Apache with PHP (kimai/kimai2:apache)
# - Development     (kimai/kimai2:dev)
# ---------------------------------------------------------------------
# For local testing by maintainer:
#
# docker build --no-cache -t kimai-apache .
# docker run -d --name kimai-apache-app kimai-apache
# docker exec -ti kimai-apache-app /bin/bash
# ---------------------------------------------------------------------
# Official PHP images: https://hub.docker.com/_/php/
# https://github.com/docker-library/docs/blob/master/php/README.md#supported-tags-and-respective-dockerfile-links
# Pass-through Arguments: https://benkyriakou.com/posts/docker-args-empty
# Best practices: https://docs.docker.com/build/building/best-practices/
# ---------------------------------------------------------------------

# Kimai version label (used for OCI labels and the KIMAI env var inside the image).
# The actual source is read from the local build context, not fetched by version.
ARG KIMAI="dev"
# Timezone for images
ARG TIMEZONE="Europe/Berlin"

###########################
# Shared tools
###########################

# composer base image
FROM composer:latest AS composer

###########################
# PHP extensions
###########################

# apache debian php extension base
FROM php:8.5-apache-bookworm AS apache-php-ext-base
RUN apt-get update && \
    apt-get install -y \
        libldap2-dev \
        libicu-dev \
        libpng-dev \
        libzip-dev \
        libxslt1-dev \
        libfreetype6-dev

# php extension gd - 13.86s
FROM apache-php-ext-base AS php-ext-gd
RUN docker-php-ext-configure gd \
        --with-freetype && \
    docker-php-ext-install -j$(nproc) gd

# php extension intl : 15.26s
FROM apache-php-ext-base AS php-ext-intl
RUN docker-php-ext-install -j$(nproc) intl

# php extension ldap : 8.45s
FROM apache-php-ext-base AS php-ext-ldap
RUN docker-php-ext-configure ldap && \
    docker-php-ext-install -j$(nproc) ldap

# php extension pdo_mysql : 6.14s
FROM apache-php-ext-base AS php-ext-pdo_mysql
RUN docker-php-ext-install -j$(nproc) pdo_mysql

# php extension zip : 8.18s
FROM apache-php-ext-base AS php-ext-zip
RUN docker-php-ext-install -j$(nproc) zip

# php extension xsl : ?.?? s
FROM apache-php-ext-base AS php-ext-xsl
RUN docker-php-ext-install -j$(nproc) xsl

###########################
# apache base build
###########################

FROM php:8.5-apache-bookworm AS apache-base
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
        libfreetype6 \
        unzip && \
    echo "Listen 8001" > /etc/apache2/ports.conf && \
    a2enmod rewrite && \
    touch /use_apache

COPY .docker/000-default.conf /etc/apache2/sites-available/000-default.conf

EXPOSE 8001

HEALTHCHECK --interval=20s --timeout=10s --retries=3 \
    CMD curl -f http://127.0.0.1:8001 || exit 1

###########################
# global base build
###########################

FROM apache-base AS php-base
ARG TIMEZONE

ENV TIMEZONE=${TIMEZONE}
RUN ln -snf /usr/share/zoneinfo/${TIMEZONE} /etc/localtime && echo ${TIMEZONE} > /etc/timezone && \
    # make composer home dir
    mkdir /composer  && \
    chown -R www-data:www-data /composer

# copy composer
COPY --from=composer /usr/bin/composer /usr/bin/composer

# copy php extensions

# PHP extension xsl
COPY --from=php-ext-xsl /usr/local/etc/php/conf.d/docker-php-ext-xsl.ini /usr/local/etc/php/conf.d/docker-php-ext-xsl.ini
COPY --from=php-ext-xsl /usr/local/lib/php/extensions/no-debug-non-zts-20250925/xsl.so /usr/local/lib/php/extensions/no-debug-non-zts-20250925/xsl.so
# PHP extension pdo_mysql
COPY --from=php-ext-pdo_mysql /usr/local/etc/php/conf.d/docker-php-ext-pdo_mysql.ini /usr/local/etc/php/conf.d/docker-php-ext-pdo_mysql.ini
COPY --from=php-ext-pdo_mysql /usr/local/lib/php/extensions/no-debug-non-zts-20250925/pdo_mysql.so /usr/local/lib/php/extensions/no-debug-non-zts-20250925/pdo_mysql.so
# PHP extension zip
COPY --from=php-ext-zip /usr/local/etc/php/conf.d/docker-php-ext-zip.ini /usr/local/etc/php/conf.d/docker-php-ext-zip.ini
COPY --from=php-ext-zip /usr/local/lib/php/extensions/no-debug-non-zts-20250925/zip.so /usr/local/lib/php/extensions/no-debug-non-zts-20250925/zip.so
# PHP extension ldap
COPY --from=php-ext-ldap /usr/local/etc/php/conf.d/docker-php-ext-ldap.ini /usr/local/etc/php/conf.d/docker-php-ext-ldap.ini
COPY --from=php-ext-ldap /usr/local/lib/php/extensions/no-debug-non-zts-20250925/ldap.so /usr/local/lib/php/extensions/no-debug-non-zts-20250925/ldap.so
# PHP extension gd
COPY --from=php-ext-gd /usr/local/etc/php/conf.d/docker-php-ext-gd.ini /usr/local/etc/php/conf.d/docker-php-ext-gd.ini
COPY --from=php-ext-gd /usr/local/lib/php/extensions/no-debug-non-zts-20250925/gd.so /usr/local/lib/php/extensions/no-debug-non-zts-20250925/gd.so
# PHP extension intl
COPY --from=php-ext-intl /usr/local/etc/php/conf.d/docker-php-ext-intl.ini /usr/local/etc/php/conf.d/docker-php-ext-intl.ini
COPY --from=php-ext-intl /usr/local/lib/php/extensions/no-debug-non-zts-20250925/intl.so /usr/local/lib/php/extensions/no-debug-non-zts-20250925/intl.so

###########################
# global base build
###########################

FROM php-base AS base
ARG KIMAI
ARG TIMEZONE

LABEL org.opencontainers.image.title="Kimai" \
      org.opencontainers.image.description="Kimai is a time-tracking application." \
      org.opencontainers.image.authors="Kimai Community" \
      org.opencontainers.image.url="https://www.kimai.org/" \
      org.opencontainers.image.documentation="https://www.kimai.org/documentation/" \
      org.opencontainers.image.source="https://github.com/kimai/kimai" \
      org.opencontainers.image.version="${KIMAI}" \
      org.opencontainers.image.vendor="Kevin Papst" \
      org.opencontainers.image.licenses="AGPL-3.0"

ENV KIMAI=${KIMAI}
ENV TIMEZONE=${TIMEZONE}
RUN ln -snf /usr/share/zoneinfo/${TIMEZONE} /etc/localtime && echo ${TIMEZONE} > /etc/timezone && \
    mkdir -p /composer  && \
    chown -R www-data:www-data /composer

# copy startup script & DB checking script
COPY .docker/dbtest.php /dbtest.php
COPY .docker/entrypoint.sh /entrypoint.sh

ENV DATABASE_URL="mysql://kimai:kimai@127.0.0.1:3306/kimai?charset=utf8mb4&serverVersion=8.3"
# APP_SECRET is intentionally not set here. The entrypoint resolves it (user-provided
# via -e APP_SECRET=... wins; otherwise a unique value is generated and persisted to
# the var/data volume and mirrored into .env.local). Setting it as a Dockerfile ENV
# would create a real env var that always wins over .env*, breaking `docker exec`
# console invocations.
# The default container name for nginx is nginx
ENV TRUSTED_PROXIES=nginx,localhost,127.0.0.1
ENV MAILER_FROM=kimai@example.com
ENV MAILER_URL=null://localhost
ENV ADMINPASS=
ENV ADMINMAIL=
ENV USER_ID=
ENV GROUP_ID=
# default values to configure composer behavior
ENV COMPOSER_MEMORY_LIMIT=-1
ENV COMPOSER_ALLOW_SUPERUSER=1

VOLUME [ "/opt/kimai/var" ]

CMD [ "/entrypoint.sh" ]

###########################
# final builds
###########################

# development build
FROM base AS dev
# copy kimai source from local build context (see .dockerignore for what is excluded)
COPY --chown=www-data:www-data . /opt/kimai
COPY .docker /assets
# do the composer deps installation
RUN \
    export COMPOSER_HOME=/composer && \
    composer --no-ansi install --working-dir=/opt/kimai --optimize-autoloader && \
    composer --no-ansi require --working-dir=/opt/kimai laminas/laminas-ldap && \
    composer --no-ansi clearcache && \
    cp /usr/local/etc/php/php.ini-development /usr/local/etc/php/php.ini && \
    chown -R www-data:www-data /opt/kimai /usr/local/etc/php/php.ini && \
    mkdir -p /opt/kimai/var/logs && chmod 777 /opt/kimai/var/logs && \
    sed "s/128M/-1/g" /usr/local/etc/php/php.ini-development > /opt/kimai/php-cli.ini && \
    sed -i "s/env php/env -S php -c \/opt\/kimai\/php-cli.ini/g" /opt/kimai/bin/console && \
    /opt/kimai/bin/console kimai:version | awk '{print $2}' > /opt/kimai/version.txt
ENV APP_ENV=dev
ENV DATABASE_URL=
ENV memory_limit=512M

# the "prod" stage (production build) is configured as last stage in the file, as this is the default target in BuildKit
FROM base AS prod
# copy kimai source from local build context (see .dockerignore for what is excluded)
COPY --chown=www-data:www-data . /opt/kimai
COPY .docker /assets
# do the composer deps installation
RUN \
    export COMPOSER_HOME=/composer && \
    composer --no-ansi install --working-dir=/opt/kimai --no-dev --optimize-autoloader && \
    composer --no-ansi require --update-no-dev --working-dir=/opt/kimai laminas/laminas-ldap && \
    composer --no-ansi clearcache && \
    cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini && \
    sed -i "s/expose_php = On/expose_php = Off/g" /usr/local/etc/php/php.ini && \
    sed -i "s/;opcache.enable=1/opcache.enable=1/g" /usr/local/etc/php/php.ini && \
    sed -i "s/;opcache.memory_consumption=128/opcache.memory_consumption=256/g" /usr/local/etc/php/php.ini && \
    sed -i "s/;opcache.interned_strings_buffer=8/opcache.interned_strings_buffer=24/g" /usr/local/etc/php/php.ini && \
    sed -i "s/;opcache.max_accelerated_files=10000/opcache.max_accelerated_files=100000/g" /usr/local/etc/php/php.ini && \
    sed -i "s/opcache.validate_timestamps=1/opcache.validate_timestamps=0/g" /usr/local/etc/php/php.ini && \
    sed -i "s/session.gc_maxlifetime = 1440/session.gc_maxlifetime = 604800/g" /usr/local/etc/php/php.ini && \
    mkdir -p /opt/kimai/var/logs && chmod 777 /opt/kimai/var/logs && \
    sed "s/128M/-1/g" /usr/local/etc/php/php.ini-development > /opt/kimai/php-cli.ini && \
    chown -R www-data:www-data /opt/kimai /usr/local/etc/php/php.ini && \
    /opt/kimai/bin/console kimai:version | awk '{print $2}' > /opt/kimai/version.txt
ENV APP_ENV=prod
ENV DATABASE_URL=
ENV memory_limit=512M
