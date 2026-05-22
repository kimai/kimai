#!/bin/bash -x

KIMAI=$(cat /opt/kimai/version.txt)
echo $KIMAI

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
  # set mem limits and copy in custom logger config
  if [ -z "$memory_limit" ]; then
    memory_limit=512M
  fi
  sed -i "s/memory_limit.*/memory_limit=$memory_limit/g" /usr/local/etc/php/php.ini
  cp /assets/monolog.yaml /opt/kimai/config/packages/monolog.yaml

  if [ -z "$USER_ID" ]; then
    USER_ID=$(id -u www-data)
  fi
  if [ -z "$GROUP_ID" ]; then
    GROUP_ID=$(id -g www-data)
  fi

  # if group doesn't exist
  if grep -w "$GROUP_ID" /etc/group &>/dev/null; then
    echo Group already exists
  else
    echo www-kimai:x:"$GROUP_ID": >> /etc/group
    grpconv
  fi

  # if user doesn't exist
  if id "$USER_ID" &>/dev/null; then
    echo User already exists
  else
    echo www-kimai:x:"$USER_ID":"$GROUP_ID":www-kimai:/var/www:/usr/sbin/nologin >> /etc/passwd
    pwconv
  fi

  if [ -e /use_apache ]; then
    export APACHE_RUN_USER=$(id -nu "$USER_ID")
    # This doesn't _exactly_ run as the specified GID, it runs as the GID of the specified user but WTF
    export APACHE_RUN_GROUP=$(id -ng "$USER_ID")
    export APACHE_PID_FILE=/var/run/apache2/apache2.pid
    export APACHE_RUN_DIR=/var/run/apache2
    export APACHE_LOCK_DIR=/var/lock/apache2
    export APACHE_LOG_DIR=/var/log/apache2
    export LANG=C
  elif [ -e /use_fpm ]; then
    sed -i "s/user = .*/user = $USER_ID/g" /usr/local/etc/php-fpm.d/www.conf
    sed -i "s/group = .*/group = $GROUP_ID/g" /usr/local/etc/php-fpm.d/www.conf
    echo "Setting fpm to run as ${USER_ID}:${GROUP_ID}"
  else
    echo "Error, unknown server type"
  fi
}

function prepareKimai() {
  # These are idempotent, so we can run them on every start-up
  /opt/kimai/bin/console -n kimai:install
  if [ ! -z "$ADMINPASS" ] && [ ! -a "$ADMINMAIL" ]; then
    /opt/kimai/bin/console kimai:user:create admin "$ADMINMAIL" ROLE_SUPER_ADMIN "$ADMINPASS"
  fi
  echo "$KIMAI" > /opt/kimai/var/installed
  echo "Kimai is ready"
}

function ensureAppSecret() {
  # GHSA-jr9p-4h4j-6c58
  # Make sure the container never runs with the publicly-known default APP_SECRET.
  # If the user provided their own value (via -e APP_SECRET=...) it is kept untouched.
  # Otherwise a unique secret is generated once and persisted below var/data, which
  # is the directory mounted as a named volume in the documented Docker setup, so it
  # stays stable across container restarts and re-creations.
  local SECRET_FILE=/opt/kimai/var/data/.appsecret

  if [ -n "$APP_SECRET" ] && [ "$APP_SECRET" != "change_this_to_something_unique" ]; then
    return
  fi

  if [ -s "$SECRET_FILE" ]; then
    APP_SECRET=$(cat "$SECRET_FILE")
    echo "APP_SECRET: using persisted auto-generated secret"
  else
    mkdir -p "$(dirname "$SECRET_FILE")"
    APP_SECRET=$(php -r 'echo bin2hex(random_bytes(32));')
    ( umask 077 && echo "$APP_SECRET" > "$SECRET_FILE" )
    echo "APP_SECRET: generated a new unique secret, persisted to var/data volume"
  fi
  export APP_SECRET
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
ensureAppSecret
prepareKimai
runServer
