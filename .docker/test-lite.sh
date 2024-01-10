#!/bin/sh -e

if [ -z "$DATABASE_URL" ]; then
  DATABASE_URL="mysql://kimai:kimai@127.0.0.1:3306/kimai?charset=utf8mb4&serverVersion=5.7.40"
fi

/opt/kimai/bin/console kimai:version
if [ $? != 0 ]; then
  echo "PHP/Kimai not responding"
  exit 1
fi

# Test FPM CGI
if [ -f /use_fpm ]; then
    echo Testing FPM
    php-fpm -t
fi

# Test Apache/httpd
if [ -f /use_apache ]; then
    echo Testing Apache
    apache2ctl -t
fi

exit 0
