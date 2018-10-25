# This file is part of the Kimai time-tracking app.
#
# For the full copyright and license information, please view the LICENSE
# file that was distributed with this source code.
 
FROM php:7.2.9-apache-stretch

RUN php -r "readfile('http://getcomposer.org/installer');" | php -- --install-dir=/usr/bin/ --filename=composer && \
    apt update && \
    apt install -y \
        git \
        libicu-dev \
        libjpeg-dev \
        libldap2-dev \
        libldb-dev \
        libpng-dev \
        unzip \
        zip \
        && \
    docker-php-ext-install \
        gd \
        intl \
        ldap \
        pdo_mysql \
        zip \
        && \
    docker-php-ext-install \
        gd \
        intl \
        ldap \
        pdo_mysql \
        zip

RUN apt-get -y autoremove && \
     apt-get clean

ADD .docker/startup-dev.sh /startup.sh
WORKDIR /opt/kimai

ENV PORT=8080
ENV _UID=33
ENV _GID=33

ENTRYPOINT /startup.sh
