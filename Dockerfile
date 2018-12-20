# This file is part of the Kimai time-tracking app.
#
# For the full copyright and license information, please view the LICENSE
# file that was distributed with this source code.
 
FROM php:7.2.9-apache-stretch

RUN apt update && \
    apt install -y \
        git \
        libicu-dev \
        libjpeg-dev \
        libldap2-dev \
        libldb-dev \
        libpng-dev \
        unzip \
        wget \
        zip \
        && \
    EXPECTED_SIGNATURE="$(wget -q -O - https://composer.github.io/installer.sig)" && \
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
    ACTUAL_SIGNATURE="$(php -r "echo hash_file('sha384', 'composer-setup.php');")" && \
    if [ "$EXPECTED_SIGNATURE" != "$ACTUAL_SIGNATURE" ]; then >&2 echo 'ERROR: Invalid installer signature'; rm composer-setup.php; exit 1; fi && \
    php composer-setup.php --quiet && \
    rm composer-setup.php && \
    mv /var/www/html/composer.phar /usr/bin/composer && \
    apt remove -y wget && \
    docker-php-ext-install \
        gd \
        intl \
        ldap \
        pdo_mysql \
        zip && \
    apt-get -y autoremove && \
    apt-get clean && \
    git clone https://github.com/kevinpapst/kimai2.git /opt/kimai && \
    sed "s/prod/dev/g" /opt/kimai/.env.dist > /opt/kimai/.env && \
    composer install --working-dir=/opt/kimai --dev --optimize-autoloader && \
    /opt/kimai/bin/console doctrine:database:create && \
    /opt/kimai/bin/console doctrine:schema:create && \
    /opt/kimai/bin/console doctrine:migrations:version --add --all && \
    /opt/kimai/bin/console cache:warmup && \
    chown -R www-data:www-data /opt/kimai/var

WORKDIR /opt/kimai

EXPOSE 8001
USER www-data
CMD /opt/kimai/bin/console server:run 0.0.0.0:8001
