FROM php:7.2-apache

VOLUME /usr/src/var/data

RUN apt-get update && apt-get install -y --no-install-recommends libicu-dev git zip unzip && \
    docker-php-ext-configure intl && \
    docker-php-ext-install intl

RUN php -r "readfile('http://getcomposer.org/installer');" | php -- --install-dir=/usr/bin/ --filename=composer && \
    apt-get -y autoremove && \
    apt-get clean

COPY ./ /usr/src/
WORKDIR /usr/src
RUN rmdir /var/www/html && ln -s /usr/src/public /var/www/html
RUN chown -R www-data:www-data /usr/src && a2enmod rewrite

USER www-data
WORKDIR /usr/src
RUN composer install --no-dev --optimize-autoloader

USER root

EXPOSE 80
CMD ["apache2-foreground"]