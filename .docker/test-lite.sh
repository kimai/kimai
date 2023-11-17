#!/bin/sh -e

# Test PHP/Kimai
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
