# Build an intermediate container with all the php extensions and composer
# Use this to install kimai
FROM php:7.2.9-apache-stretch AS dev

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

# RUN apt-get purge -y \
        # git \
        # libicu-dev \
        # libjpeg-dev \
        # libldap2-dev \
        # libldb-dev \
        # libpng-dev \
        # zip \
        # git \
        # unzip \
        # zip \
        # && \
     # apt-get -y autoremove && \
     # apt-get clean

ADD .docker/startup.sh /startup.sh
WORKDIR /opt/kimai

ENV PORT=8080

ENTRYPOINT /startup.sh

# Copy the built app into a new container without the build devs, this keeps the container size down.
