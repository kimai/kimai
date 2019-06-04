# This file is part of the Kimai time-tracking app.
#
# For the full copyright and license information, please view the LICENSE
# file that was distributed with this source code.
 
FROM kimai/kimai2_base

RUN git clone https://github.com/kevinpapst/kimai2.git /opt/kimai && \
    sed "s/prod/dev/g" /opt/kimai/.env.dist > /opt/kimai/.env && \
    composer install --working-dir=/opt/kimai --dev --optimize-autoloader && \
    /opt/kimai/bin/console doctrine:database:create && \
    /opt/kimai/bin/console doctrine:schema:create && \
    /opt/kimai/bin/console doctrine:migrations:version --add --all && \
    /opt/kimai/bin/console cache:warmup && \
    chown -R www-data:www-data /opt/kimai/var && \
    chown www-data:www-data /opt/kimai/vendor/mpdf/mpdf/tmp

WORKDIR /opt/kimai

EXPOSE 8001
USER www-data
CMD /opt/kimai/bin/console server:run 0.0.0.0:8001