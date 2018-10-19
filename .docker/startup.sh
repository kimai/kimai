#!/bin/bash

until mysql -u lamp -plamp -h db lamp -e "show tables"; do
  >&2 echo "Mysql is unavailable - sleeping"
  sleep 5
done

APP_PATH=/var/www/html

# Check the mount point for the code base, this could be pretty much any file
if [ ! -e $APP_PATH/.env ]; then
    # Copy in the code base
    rm -rf /var/www/html/{*,.*}
    cp -r /var/tmp/kimai/* $APP_PATH
    cp -r /var/tmp/kimai/.??* $APP_PATH
fi

# If the schema does not exist then create it (and run the migrations)
TABLE_COUNT=$(/var/www/html/bin/console doctrine:query:sql "select * from kimai2_users")
if [ "$?" != 0 ]; then
    # We can't find the users table.  We'll usae this to guess we don't have a schema installed.
    # Is there a better way of doing this?
    /var/www/html/bin/console -y doctrine:schema:create
    /var/www/html/bin/console -y doctrine:migrations:version --add --all
fi

# If we have a start up/seed sql file run that.
for initfile in /var/tmp/init-sql/*; do

    if [ ${initfile: -4} == ".sh" ]; then
        sh $initfile

    elif [ ${initfile: -4} == ".dql" ]; then
        while IFS='' read -r line || [[ -n "$line" ]]; do
            echo $line
            /var/www/html/bin/console doctrine:query:sql "$line"
        done < $initfile

    elif [ ${initfile: -4} == ".sql" ]; then
        while IFS='' read -r line || [[ -n "$line" ]]; do
            echo $line
            /var/www/html/bin/console doctrine:query:sql "$line"
        done < $initfile
    fi
done

# Warm up the cache
/var/www/html/bin/console cache:warmup --env=prod
chown -R www-data:www-data /var/www/html/var

# Start listening
php-fpm
