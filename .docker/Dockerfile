# This file is part of the Kimai time-tracking app.
#
# For the full copyright and license information, please view the LICENSE
# file that was distributed with this source code.

FROM php:7.2-fpm-alpine as kimai

ARG VERSION
ARG MAILER_FROM
ARG MAILER_URL 

RUN php -r "readfile('http://getcomposer.org/installer');" | php -- --install-dir=/usr/bin/ --filename=composer && \
    apk add --no-cache \
        bash \
        git \
        icu-dev \
        freetype libpng libjpeg-turbo freetype-dev libpng-dev libjpeg-turbo-dev \
        mysql-client \
        unzip \
        zip \
        && \
    docker-php-ext-install \
        gd \
        intl \
        pdo_mysql \
        zip \
        && \
    docker-php-ext-install \
        gd \
        intl \
        pdo_mysql \
        zip && \
    mkdir -p /var/tmp/init-sql

ADD https://github.com/kevinpapst/kimai2/archive/${VERSION}.tar.gz /var/tmp/kimai.tgz
ADD env /var/tmp/kimai/.env

WORKDIR /var/tmp/kimai
RUN tar --strip-components=1 -zxvf /var/tmp/kimai.tgz && \
    composer install --no-dev --optimize-autoloader && \
    sed -i "s/kimai@example.com/${MAILER_FROM}/g" /var/tmp/kimai/.env && \
    sed -i "s|null://localhost|${MAILER_URL}|g" /var/tmp/kimai/.env && \
    sed -i "s/change_this_to_something_unique/$(</dev/urandom tr -dc 'A-Za-z0-9' | head -c48)/g" /var/tmp/kimai/.env && \
    rm /usr/bin/composer

ADD startup.sh /startup.sh

ENTRYPOINT /startup.sh
