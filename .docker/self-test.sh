#!/bin/sh

/startup.sh 2>&1
echo $$ > /tmp/startup.pid
echo "Waiting for kimai to install"
while [ ! -f /opt/kimai/var/installed ]; do
  echo -n ". "
  sleep 5
done
echo

# Test FPM CGI
if [ -f /use_fpm ]; then

  export SCRIPT_NAME=/opt/kimai/public/index.php
  export SCRIPT_FILENAME=/opt/kimai/public/index.php
  export REQUEST_METHOD=GET
  export SERVER_ADDR=localhost

  COUNT=0
  until cgi-fcgi -bind -connect 127.0.0.1:9000 &> /dev/null
  do
    COUNT=$((COUNT+1))
    echo "Waiting for FPM Server to start (${COUNT})"
    sleep 3
    if [ "$COUNT" -gt 5 ]; then
      echo "FPM Failed to start."
      exit 1
    fi
  done

fi

# Test Apache/httpd
if [ -f /use_apache ]; then

  COUNT=0
  until curl -s -o /dev/null http://localhost:8001
  do
    COUNT=$((COUNT+1))
    echo "Waiting for Apache/HTTP to start (${COUNT})" &> /dev/null
    sleep 3
    if [ "$COUNT" -gt 5 ]; then
      echo "Apache/HTTP failed to start."
      exit 1
    fi
  done

fi

# Test PHP/Kimai
/opt/kimai/bin/console kimai:version
if [ $? != 0 ]; then
  echo "PHP/Kimai not responding"
  exit 1
fi
kill $(cat /tmp/startup.pid)


