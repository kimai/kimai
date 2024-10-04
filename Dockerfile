#  _  ___                 _
# | |/ (_)_ __ ___   __ _(_)
# | ' /| | '_ ` _ \ / _` | |
# | . \| | | | | | | (_| | |
# |_|\_\_|_| |_| |_|\__,_|_|
#
# Kimai images for:
# - plain PHP FPM
# - Apache with PHP
# ---------------------------------------------------------------------
# For local testing by maintainer:
#
# docker build -t kimai-local-fpm --build-arg BASE=fpm .
# docker build -t kimai-local-apache --build-arg BASE=apache .
# ---------------------------------------------------------------------

# Source base [fpm/apache]
ARG BASE="fpm"
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

# FPM base
FROM kimai/kimai-base:fpm AS fpm-base
RUN sed -i "s/;ping.path/ping.path/g" /usr/local/etc/php-fpm.d/www.conf && \
    sed -i "s/;access.suppress_path\[\] = \/ping/access.suppress_path\[\] = \/ping/g" /usr/local/etc/php-fpm.d/www.conf

# Apache base
FROM kimai/kimai-base:apache AS apache-base
COPY .docker/000-default.conf /etc/apache2/sites-available/000-default.conf

###########################
# global base build
###########################

FROM ${BASE}-base AS base
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
      org.opencontainers.image.licenses="AGPL-3.0" \
      org.opencontainers.image.base.name="docker.io/library/alpine"

ENV KIMAI=${KIMAI}
ENV TIMEZONE=${TIMEZONE}
RUN ln -snf /usr/share/zoneinfo/${TIMEZONE} /etc/localtime && echo ${TIMEZONE} > /etc/timezone && \
    mkdir -p /composer  && \
    chown -R www-data:www-data /composer

# copy startup script & DB checking script
COPY .docker/dbtest.php /dbtest.php
COPY .docker/startup.sh /startup.sh

ENV DATABASE_URL="mysql://kimai:kimai@127.0.0.1:3306/kimai?charset=utf8mb4&serverVersion=8.3"
ENV APP_SECRET=change_this_to_something_unique
# The default container name for nginx is nginx
ENV TRUSTED_PROXIES=nginx,localhost,127.0.0.1
ENV TRUSTED_HOSTS=nginx,localhost,127.0.0.1
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

CMD [ "/startup.sh" ]

###########################
# final builds
###########################

# development build
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
    /opt/kimai/bin/console kimai:version | awk '{print $2}' > /opt/kimai/version.txt
ENV APP_ENV=dev
ENV DATABASE_URL=
ENV memory_limit=512M

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
    /opt/kimai/bin/console kimai:version | awk '{print $2}' > /opt/kimai/version.txt
ENV APP_ENV=prod
ENV DATABASE_URL=
ENV memory_limit=512M
