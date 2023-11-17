#!/bin/bash -x

function waitForDB() {
  # Parse sql connection data
  if [ ! -z "$DATABASE_URL" ]; then
    DB_TYPE=$(awk -F '[/:@]' '{print $1}' <<< "$DATABASE_URL")
    DB_USER=$(awk -F '[/:@]' '{print $4}' <<< "$DATABASE_URL")
    DB_PASS=$(awk -F '[/:@]' '{print $5}' <<< "$DATABASE_URL")
    DB_HOST=$(awk -F '[/:@]' '{print $6}' <<< "$DATABASE_URL")
    DB_PORT=$(awk -F '[/:@]' '{print $7}' <<< "$DATABASE_URL")
    DB_BASE=$(awk -F '[/?]' '{print $4}' <<< "$DATABASE_URL")
  else
    DB_TYPE=${DB_TYPE:mysql}
    if [ "$DB_TYPE" == "mysql" ]; then
      export DATABASE_URL="${DB_TYPE}://${DB_USER:=kimai}:${DB_PASS:=kimai}@${DB_HOST:=sqldb}:${DB_PORT:=3306}/${DB_BASE:=kimai}"
    else
      echo "Unknown database type, cannot proceed. Only 'mysql' is supported, received: [$DB_TYPE]"
      exit 1
    fi
  fi

  re='^[0-9]+$'
  if ! [[ $DB_PORT =~ $re ]] ; then
     DB_PORT=3306
  fi

  echo "Wait for MySQL DB connection ..."
  until php /dbtest.php $DB_HOST $DB_BASE $DB_PORT $DB_USER $DB_PASS; do
    echo Checking DB: $?
    sleep 3
  done
  echo "Connection established"
}

function handleStartup() {
  # These are idempotent, run them anyway
  /opt/kimai/bin/console -n kimai:install
  /opt/kimai/bin/console -n kimai:update
  if [ ! -z "$ADMINPASS" ] && [ ! -a "$ADMINMAIL" ]; then
    /opt/kimai/bin/console kimai:user:create superadmin "$ADMINMAIL" ROLE_SUPER_ADMIN "$ADMINPASS"
  fi
  echo "$KIMAI" > /opt/kimai/var/installed
  echo "Kimai2 ready"
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
