#syntax=docker.io/docker/dockerfile:1.7-labs

#  _  ___                 _
# | |/ (_)_ __ ___   __ _(_)
# | ' /| | '_ ` _ \ / _` | |
# | . \| | | | | | | (_| | |
# |_|\_\_|_| |_| |_|\__,_|_|
#
# Kimai images for:
# - plain PHP FPM   (kimai/kimai2:fpm)
# - Apache with PHP (kimai/kimai2:apache)
# ---------------------------------------------------------------------
# For local testing by maintainer:
#
# docker build --no-cache -t kimai-fpm --target fpm .
# docker build --no-cache -t kimai-apache --target apache .
# docker run -d --name kimai-apache-app kimai-apache
# docker exec -ti kimai-apache-app /bin/bash
# ---------------------------------------------------------------------
# Official PHP images: https://hub.docker.com/_/php/
# https://github.com/docker-library/docs/blob/master/php/README.md#supported-tags-and-respective-dockerfile-links
# Pass-through Arguments: https://benkyriakou.com/posts/docker-args-empty
# Best practices: https://docs.docker.com/build/building/best-practices/
# ---------------------------------------------------------------------

# Kimai branch/tag to run
ARG KIMAI="main"
ARG PHP_VERSION=83

FROM alpine:3.20 AS base

ARG PHP_VERSION
ARG KIMAI

LABEL org.opencontainers.image.title="Kimai" \
      org.opencontainers.image.description="Kimai is a time-tracking application." \
      org.opencontainers.image.authors="Kimai Community" \
      org.opencontainers.image.url="https://www.kimai.org/" \
      org.opencontainers.image.documentation="https://www.kimai.org/documentation/" \
      org.opencontainers.image.source="https://github.com/kimai/kimai" \
      org.opencontainers.image.version="${KIMAI}" \
      org.opencontainers.image.vendor="Kevin Papst" \
      org.opencontainers.image.licenses="AGPL-3.0"

WORKDIR /opt/kimai

RUN --mount=type=cache,target=/var/cache/apk \
    apk --update add \
    bash \
    curl \
    shadow-conv \
    composer \
    unzip \
    php${PHP_VERSION} \
    php${PHP_VERSION}-pdo \
    php${PHP_VERSION}-pdo_mysql \
    php${PHP_VERSION}-fileinfo \
    php${PHP_VERSION}-iconv \
    php${PHP_VERSION}-xml \
    php${PHP_VERSION}-xsl \
    php${PHP_VERSION}-dom \
    php${PHP_VERSION}-xsl \
    php${PHP_VERSION}-zip \
    php${PHP_VERSION}-ldap \
    php${PHP_VERSION}-gd \
    php${PHP_VERSION}-intl \
    php${PHP_VERSION}-simplexml \
    php${PHP_VERSION}-opcache \
    php${PHP_VERSION}-xmlreader \
    php${PHP_VERSION}-xmlwriter\
    php${PHP_VERSION}-tokenizer \
    php${PHP_VERSION}-session \
    php${PHP_VERSION}-ctype

ENV KIMAI=${KIMAI} \
    APP_ENV=prod \
    APP_SECRET=change_this_to_something_unique \
    # The default container name for nginx is nginx \
    TRUSTED_PROXIES=nginx,localhost,127.0.0.1 \
    DATABASE_URL="mysql://kimai:kimai@127.0.0.1:3306/kimai?charset=utf8mb4&serverVersion=8.3" \
    MAILER_FROM=kimai@example.com \
    MAILER_URL=null://localhost \
    ADMINPASS="" \
    ADMINMAIL="" \
    PHP_MEMORY_LIMIT=512M \
    COMPOSER_MEMORY_LIMIT=-1

RUN \
    sed -i "s|memory_limit = 128M|memory_limit = \${PHP_MEMORY_LIMIT}|g" /etc/php${PHP_VERSION}/php.ini && \
    sed -i "s|128M|-1|g" /etc/php${PHP_VERSION}/php.ini > /opt/kimai/php-cli.ini

FROM base AS dev
ARG PHP_VERSION

RUN --mount=type=cache,target=/var/cache/apk \
    apk --update add \
    apache2 \
    php${PHP_VERSION}-apache2

RUN \
    sed -i "s|ErrorLog logs/error.log|ErrorLog /dev/stderr|g" /etc/apache2/httpd.conf && \
    sed -i "s|CustomLog logs/access.log|CustomLog /dev/stdout|g" /etc/apache2/httpd.conf && \
    sed -i "s|#LoadModule rewrite_module|LoadModule rewrite_module|g" /etc/apache2/httpd.conf && \
    sed -i "s|#ServerName www.example.com:80|ServerName localhost|g" /etc/apache2/httpd.conf && \
    echo "Listen 8001" >> /etc/apache2/httpd.conf

COPY .docker/000-default.conf /etc/apache2/conf.d/000-default.conf

EXPOSE 8001

HEALTHCHECK --interval=20s --timeout=10s --retries=3 \
    CMD curl -f http://127.0.0.1:8001 || exit 1

CMD ["httpd", "-DFOREGROUND"]

FROM base AS prod
ARG PHP_VERSION

COPY --exclude=./.docker . .

RUN --mount=type=cache,target=/tmp/cache \
    composer install  --no-dev --optimize-autoloader && \
    composer require --update-no-dev  laminas/laminas-ldap

RUN \
    sed -i "s|expose_php = On|expose_php = Off|g" /etc/php${PHP_VERSION}/php.ini && \
    sed -i "s|;opcache.enable=1|opcache.enable=1|g" /etc/php${PHP_VERSION}/php.ini && \
    sed -i "s|;opcache.memory_consumption=128|opcache.memory_consumption=256|g" /etc/php${PHP_VERSION}/php.ini && \
    sed -i "s|;opcache.interned_strings_buffer=8|opcache.interned_strings_buffer=24|g" /etc/php${PHP_VERSION}/php.ini && \
    sed -i "s|;opcache.max_accelerated_files=10000|opcache.max_accelerated_files=100000|g" /etc/php${PHP_VERSION}/php.ini && \
    sed -i "s|opcache.validate_timestamps=1|opcache.validate_timestamps=0|g" /etc/php${PHP_VERSION}/php.ini && \
    sed -i "s|session.gc_maxlifetime = 1440|session.gc_maxlifetime = 604800|g" /etc/php${PHP_VERSION}/php.ini

COPY .docker/rootfs /

ENTRYPOINT [ "/entrypoint.sh" ]

VOLUME [ "/opt/kimai/var" ]

FROM prod AS apache
ARG PHP_VERSION

RUN --mount=type=cache,target=/var/cache/apk \
    apk --update add \
    apache2 \
    php${PHP_VERSION}-apache2

RUN \
    sed -i "s|ErrorLog logs/error.log|ErrorLog /dev/stderr|g" /etc/apache2/httpd.conf && \
    sed -i "s|CustomLog logs/access.log|CustomLog /dev/stdout|g" /etc/apache2/httpd.conf && \
    sed -i "s|#LoadModule rewrite_module|LoadModule rewrite_module|g" /etc/apache2/httpd.conf && \
    sed -i "s|#ServerName www.example.com:80|ServerName localhost|g" /etc/apache2/httpd.conf && \
    echo "Listen 8001" >> /etc/apache2/httpd.conf

COPY .docker/000-default.conf /etc/apache2/conf.d/000-default.conf

EXPOSE 8001

HEALTHCHECK --interval=20s --timeout=10s --retries=3 \
    CMD curl -f http://127.0.0.1:8001 || exit 1

CMD ["httpd", "-DFOREGROUND"]


FROM prod AS fpm
ARG PHP_VERSION

RUN --mount=type=cache,target=/var/cache/apk \
    apk --update add \
    fcgi \
    php${PHP_VERSION}-fpm \
    php${PHP_VERSION}-cgi && \
    ln -s /usr/sbin/php-fpm${PHP_VERSION} /usr/sbin/php-fpm

RUN \
    sed -i "s|;ping.path|ping.path|g" /etc/php${PHP_VERSION}/php-fpm.d/www.conf && \
    sed -i "s|;access.suppress_path\[\] = /ping|access.suppress_path\[\] = /ping|g" /etc/php${PHP_VERSION}/php-fpm.d/www.conf

RUN --mount=type=cache,target=/tmp/cache \
    composer install  --no-dev --optimize-autoloader && \
    composer require --update-no-dev  laminas/laminas-ldap

EXPOSE 9000

HEALTHCHECK --interval=20s --timeout=10s --retries=3 \
    CMD \
    SCRIPT_NAME=/ping \
    SCRIPT_FILENAME=/ping \
    REQUEST_METHOD=GET \
    cgi-fcgi -bind -connect 127.0.0.1:9000 || exit 1

CMD ["php-fpm", "-F"]