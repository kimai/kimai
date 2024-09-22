#!/bin/bash -x

function waitForDB() {
  # Parse sql connection data
  DATABASE_USER=$(awk -F '[/:@]' '{print $4}' <<< "$DATABASE_URL")
  DATABASE_PASS=$(awk -F '[/:@]' '{print $5}' <<< "$DATABASE_URL")
  DATABASE_HOST=$(awk -F '[/:@]' '{print $6}' <<< "$DATABASE_URL")
  DATABASE_PORT=$(awk -F '[/:@]' '{print $7}' <<< "$DATABASE_URL")
  DATABASE_BASE=$(awk -F '[/?]' '{print $4}' <<< "$DATABASE_URL")

  re='^[0-9]+$'
  if ! [[ $DATABASE_PORT =~ $re ]] ; then
     DATABASE_PORT=3306
  fi

  echo "Wait for database connection ..."
  until php /dbtest.php "$DATABASE_HOST" "$DATABASE_BASE" "$DATABASE_PORT" "$DATABASE_USER" "$DATABASE_PASS"; do
    echo Checking DB: $?
    sleep 3
  done
  echo "Connection established"
}

function handleStartup() {
  # These are idempotent, so we can run them on every start-up
  /opt/kimai/bin/console -n kimai:install
  /opt/kimai/bin/console -n kimai:update
  if [ ! -z "$ADMINPASS" ] && [ ! -a "$ADMINMAIL" ]; then
    /opt/kimai/bin/console kimai:user:create admin "$ADMINMAIL" ROLE_SUPER_ADMIN "$ADMINPASS"
  fi
  echo "$KIMAI" > /opt/kimai/var/installed
  echo "Kimai is ready"
}

function runServer() {
  # Just while I'm fixing things
  /opt/kimai/bin/console kimai:reload --env="$APP_ENV"
  chown -R $USER_ID:$GROUP_ID /opt/kimai/var
  if [ -e /use_apache ]; then
    exec /usr/sbin/apache2 -D FOREGROUND
  elif [ -e /use_fpm ]; then
    exec php-fpm
  else
    echo "Error, unknown server type"
  fi
}

waitForDB
handleStartup
runServer
