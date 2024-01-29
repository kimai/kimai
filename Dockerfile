#  _  ___                 _
# | |/ (_)_ __ ___   __ _(_)
# | ' /| | '_ ` _ \ / _` | |
# | . \| | | | | | | (_| | |
# |_|\_\_|_| |_| |_|\__,_|_|
#

# Source base [fpm/apache]
ARG BASE="fpm"
ARG PHP_VER="8.2"
ARG COMPOSER_VER="latest"
# Branch name
ARG KIMAI="main"
# Timezone for images
ARG TIMEZONE="Europe/Berlin"

###########################
# Shared tools
###########################

# full kimai source
FROM alpine:latest AS git-dev
# pass-through Arguments in every stage. See: https://benkyriakou.com/posts/docker-args-empty
ARG KIMAI
ARG TIMEZONE
RUN apk add --no-cache git && \
    git clone --depth 1 --branch ${KIMAI} https://github.com/kimai/kimai.git /opt/kimai

# production kimai source
FROM git-dev AS git-prod

WORKDIR /opt/kimai
RUN rm -r tests

# composer base image
FROM composer:${COMPOSER_VER} AS composer

###########################
# PHP extensions
###########################

#fpm alpine php extension base
FROM php:${PHP_VER}-fpm-alpine AS fpm-php-ext-base
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
FROM php:${PHP_VER}-apache-bookworm AS apache-php-ext-base
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

# php extension redis
FROM ${BASE}-php-ext-base AS php-ext-redis
RUN yes no | pecl install redis && \
    docker-php-ext-enable redis

# php extension opcache
FROM ${BASE}-php-ext-base AS php-ext-opcache
RUN docker-php-ext-install -j$(nproc) opcache

###########################
# fpm base build
###########################

# fpm base build
FROM php:${PHP_VER}-fpm-alpine AS fpm-base
ARG KIMAI
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

FROM php:${PHP_VER}-apache-bookworm AS apache-base
ARG KIMAI
ARG TIMEZONE
COPY .docker/000-default.conf /etc/apache2/sites-available/000-default.conf
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
ARG KIMAI
ARG TIMEZONE

LABEL maintainer="tobias@neontribe.co.uk"
LABEL maintainer="bastian@schroll-software.de"

ENV KIMAI=${KIMAI}
ENV TIMEZONE=${TIMEZONE}
RUN ln -snf /usr/share/zoneinfo/${TIMEZONE} /etc/localtime && echo ${TIMEZONE} > /etc/timezone && \
    # make composer home dir
    mkdir /composer  && \
    chown -R www-data:www-data /composer

# copy startup script & DB checking script
COPY .docker/startup.sh /startup.sh
COPY .docker/service.sh /service.sh
COPY .docker/self-test.sh /self-test.sh
COPY .docker/dbtest.php /dbtest.php

# copy composer
COPY --from=composer /usr/bin/composer /usr/bin/composer

# copy php extensions

# PHP extension xsl
COPY --from=php-ext-xsl /usr/local/etc/php/conf.d/docker-php-ext-xsl.ini /usr/local/etc/php/conf.d/docker-php-ext-xsl.ini
COPY --from=php-ext-xsl /usr/local/lib/php/extensions/no-debug-non-zts-20220829/xsl.so /usr/local/lib/php/extensions/no-debug-non-zts-20220829/xsl.so
# PHP extension pdo_mysql
COPY --from=php-ext-pdo_mysql /usr/local/etc/php/conf.d/docker-php-ext-pdo_mysql.ini /usr/local/etc/php/conf.d/docker-php-ext-pdo_mysql.ini
COPY --from=php-ext-pdo_mysql /usr/local/lib/php/extensions/no-debug-non-zts-20220829/pdo_mysql.so /usr/local/lib/php/extensions/no-debug-non-zts-20220829/pdo_mysql.so
# PHP extension zip
COPY --from=php-ext-zip /usr/local/etc/php/conf.d/docker-php-ext-zip.ini /usr/local/etc/php/conf.d/docker-php-ext-zip.ini
COPY --from=php-ext-zip /usr/local/lib/php/extensions/no-debug-non-zts-20220829/zip.so /usr/local/lib/php/extensions/no-debug-non-zts-20220829/zip.so
# PHP extension ldap
COPY --from=php-ext-ldap /usr/local/etc/php/conf.d/docker-php-ext-ldap.ini /usr/local/etc/php/conf.d/docker-php-ext-ldap.ini
COPY --from=php-ext-ldap /usr/local/lib/php/extensions/no-debug-non-zts-20220829/ldap.so /usr/local/lib/php/extensions/no-debug-non-zts-20220829/ldap.so
# PHP extension gd
COPY --from=php-ext-gd /usr/local/etc/php/conf.d/docker-php-ext-gd.ini /usr/local/etc/php/conf.d/docker-php-ext-gd.ini
COPY --from=php-ext-gd /usr/local/lib/php/extensions/no-debug-non-zts-20220829/gd.so /usr/local/lib/php/extensions/no-debug-non-zts-20220829/gd.so
# PHP extension intl
COPY --from=php-ext-intl /usr/local/etc/php/conf.d/docker-php-ext-intl.ini /usr/local/etc/php/conf.d/docker-php-ext-intl.ini
COPY --from=php-ext-intl /usr/local/lib/php/extensions/no-debug-non-zts-20220829/intl.so /usr/local/lib/php/extensions/no-debug-non-zts-20220829/intl.so
# PHP extension redis
COPY --from=php-ext-redis /usr/local/etc/php/conf.d/docker-php-ext-redis.ini /usr/local/etc/php/conf.d/docker-php-ext-redis.ini
COPY --from=php-ext-redis /usr/local/lib/php/extensions/no-debug-non-zts-20220829/redis.so /usr/local/lib/php/extensions/no-debug-non-zts-20220829/redis.so
COPY --from=php-ext-opcache /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini  /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini

ENV DATABASE_URL="mysql://kimai:kimai@127.0.0.1:3306/kimai?charset=utf8mb4&serverVersion=5.7.40"
ENV APP_SECRET=change_this_to_something_unique
# The default container name for nginx is nginx
ENV TRUSTED_PROXIES=nginx,localhost,127.0.0.1
ENV TRUSTED_HOSTS=nginx,localhost,127.0.0.1
ENV MAILER_FROM=kimai@example.com
ENV MAILER_URL=null://localhost
ENV ADMINPASS=
ENV ADMINMAIL=
ENV DB_TYPE=
ENV DB_USER=
ENV DB_PASS=
ENV DB_HOST=
ENV DB_PORT=
ENV DB_BASE=
ENV COMPOSER_MEMORY_LIMIT=-1
# If this set then the image will start, run a self test and then exit. It's used for the release process
ENV TEST_AND_EXIT=
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV USER_ID=
ENV GROUP_ID=

VOLUME [ "/opt/kimai/var" ]

CMD [ "/startup.sh" ]



###########################
# final builds
###########################

# developement build
FROM base AS dev
# copy kimai develop source
COPY --from=git-dev --chown=www-data:www-data /opt/kimai /opt/kimai
COPY .docker /assets
# do the composer deps installation
RUN echo \$PATH
RUN \
    export COMPOSER_HOME=/composer && \
    composer --no-ansi install --working-dir=/opt/kimai --optimize-autoloader && \
    composer --no-ansi clearcache && \
    composer --no-ansi require --working-dir=/opt/kimai laminas/laminas-ldap && \
    cp /usr/local/etc/php/php.ini-development /usr/local/etc/php/php.ini && \
    chown -R www-data:www-data /opt/kimai /usr/local/etc/php/php.ini && \
    mkdir -p /opt/kimai/var/logs && chmod 777 /opt/kimai/var/logs && \
    sed "s/128M/-1/g" /usr/local/etc/php/php.ini-development > /opt/kimai/php-cli.ini && \
    sed -i "s/env php/env -S php -c \/opt\/kimai\/php-cli.ini/g" /opt/kimai/bin/console && \
    tar -C /opt/kimai -zcvf /var/tmp/public.tgz public && \
    /opt/kimai/bin/console kimai:version | awk '{print $2}' > /opt/kimai/version.txt
ENV APP_ENV=dev
ENV DATABASE_URL=
ENV memory_limit=256M

# production build
FROM base AS prod
# copy kimai production source
COPY --from=git-prod --chown=www-data:www-data /opt/kimai /opt/kimai
COPY .docker /assets
# do the composer deps installation
RUN \
    export COMPOSER_HOME=/composer && \
    composer --no-ansi install --working-dir=/opt/kimai --no-dev --optimize-autoloader && \
    composer --no-ansi clearcache && \
    composer --no-ansi require --working-dir=/opt/kimai laminas/laminas-ldap && \
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
    tar -C /opt/kimai -zcvf /var/tmp/public.tgz public && \
    /opt/kimai/bin/console kimai:version | awk '{print $2}' > /opt/kimai/version.txt
ENV APP_ENV=prod
ENV DATABASE_URL=
ENV memory_limit=256M
