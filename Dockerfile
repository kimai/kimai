# This file is part of the Kimai time-tracking app.
#
# For the full copyright and license information, please view the LICENSE
# file that was distributed with this source code.
FROM php:7.4.12-cli

ENV APP_ENV dev

# Install tools and dependencies
RUN set -ex \
    && apt-get update \
    && apt-get -y install \
        git haveged unzip zip wget \
        libicu-dev libcurl4-openssl-dev libjpeg-dev libldap2-dev \
        libldb-dev libpng-dev libxslt-dev libxml2-dev libssl-dev \
        libzip-dev libfreetype6-dev libjpeg62-turbo-dev libmcrypt-dev \
        libwebp-dev libmagickwand-dev \
    && rm -rf /var/lib/apt/lists/*

# Activate PHP extensions
RUN set -ex \
    && docker-php-ext-configure \
        gd --enable-gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install \
        gd \
        intl \
        ctype \
        ldap \
        pdo_mysql \
        xsl \
        xml \
        iconv \
        bcmath \
        curl \
        fileinfo \
        gettext \
        xmlrpc \
        xmlwriter \
        simplexml \
        zip \
        opcache

# Install composer
ENV COMPOSER_MEMORY_LIMIT=-1

RUN set -ex \
    && curl -sSL https://getcomposer.org/download/1.10.17/composer.phar -o /usr/local/bin/composer \
    && chmod +x /usr/local/bin/composer

# Install symfony cli
RUN set -ex \
    && curl -sSL https://github.com/symfony/cli/releases/download/v4.20.1/symfony_linux_amd64 -o /usr/local/bin/symfony \
    && chmod +x /usr/local/bin/symfony

# Install production php.ini and some tweaks
RUN set -ex \
    && cp /usr/local/etc/php/php.ini-development /usr/local/etc/php/php.ini \
    && sed -i 's/memory_limit = 128M/memory_limit = 512M/g' /usr/local/etc/php/php.ini \
    && sed -i 's/;opcache.memory_consumption=128/opcache.memory_consumption=256/g' /usr/local/etc/php/php.ini \
    && sed -i 's/;opcache.max_accelerated_files=10000/opcache.max_accelerated_files=20000/g' /usr/local/etc/php/php.ini \
    && sed -i 's/log_errors_max_len = 1024/log_errors_max_len = 8192/g' /usr/local/etc/php/php.ini \
    && chown -R www-data:www-data /var/www

USER www-data:www-data

WORKDIR /var/www/html

# Create shallow clone of kimai and configure it as dev role
RUN set -ex \
    && git clone --depth 1 https://github.com/kevinpapst/kimai2.git . \
    && composer install --optimize-autoloader

# Create default SQLite database with a default user and compile it into the
# image to offer some default setup to spool up incase of a demo.
RUN set -ex \
    && bin/console doctrine:database:create -n \
    && bin/console doctrine:migrations:migrate -n  \
    && bin/console doctrine:schema:update -n --force \
    && bin/console cache:warmup -n \
    && bin/console kimai:create-user admin admin@kimai.local ROLE_SUPER_ADMIN adminpassword

# Perform basic sanity checks
RUN set -ex \
    && symfony check:requirements \
    && symfony security:check --disable-exit-code

EXPOSE 8001

CMD symfony serve --port=8001 --no-tls
