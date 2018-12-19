# This file is part of the Kimai time-tracking app.
#
# For the full copyright and license information, please view the LICENSE
# file that was distributed with this source code.
 
FROM php:7.2.9-apache-stretch

WORKDIR /opt/kimai

RUN php -r "readfile('https://getcomposer.org/installer');" | php -- --install-dir=/usr/bin/ --filename=composer && \
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
        zip && \
    apt-get -y autoremove && \
    apt-get clean && \
    git clone https://github.com/kevinpapst/kimai2.git /opt/kimai && \
    sed "s/prod/dev/g" .env.dist > .env && \
    composer install --dev --optimize-autoloader && \
    bin/console doctrine:database:create && \
    bin/console doctrine:schema:create && \
    bin/console doctrine:migrations:version --add --all && \
    bin/console cache:warmup && \
    chown -R www-data:www-data var

EXPOSE 8001
USER www-data
CMD /opt/kimai/bin/console server:run 0.0.0.0:8001
