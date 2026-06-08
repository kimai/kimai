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

  export APACHE_RUN_USER=$(id -nu "$USER_ID")
  # This doesn't _exactly_ run as the specified GID, it runs as the GID of the specified user but WTF
  export APACHE_RUN_GROUP=$(id -ng "$USER_ID")
  export APACHE_PID_FILE=/var/run/apache2/apache2.pid
  export APACHE_RUN_DIR=/var/run/apache2
  export APACHE_LOCK_DIR=/var/lock/apache2
  export APACHE_LOG_DIR=/var/log/apache2
  export LANG=C
}

function prepareKimai() {
  # These are idempotent, so we can run them on every start-up
  /opt/kimai/bin/console -n kimai:install
  if [ ! -z "$ADMINPASS" ] && [ ! -a "$ADMINMAIL" ]; then
    /opt/kimai/bin/console kimai:user:create admin "$ADMINMAIL" ROLE_SUPER_ADMIN "$ADMINPASS"
    echo "Created Super-Admin account"
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
  #
  # Disable xtrace around all reads/writes of APP_SECRET so the secret never appears
  # in container logs. The braces around `set +x` keep the disable command itself
  # from being traced.
  { set +x; } 2>/dev/null

  local SECRET_FILE=/opt/kimai/var/data/.appsecret
  local ENV_LOCAL=/opt/kimai/.env.local

  # Always remove any prior .env.local before deciding which secret applies.
  # This prevents a stale auto-generated value from lingering after a user
  # later sets APP_SECRET via docker env / compose. It is regenerated below
  # in the auto-secret path; in the user-provided path it stays absent so
  # the real env var remains the single source of truth.
  rm -f "$ENV_LOCAL"

  if [ -n "$APP_SECRET" ] && [ "$APP_SECRET" != "change_this_to_something_unique" ]; then
    set -x
    return
  fi

  if [ -s "$SECRET_FILE" ]; then
    APP_SECRET=$(cat "$SECRET_FILE")
    echo "APP_SECRET: using persisted auto-generated secret"
  else
    mkdir -p "$(dirname "$SECRET_FILE")"
    APP_SECRET=$(php -r 'echo bin2hex(random_bytes(32));')
    ( umask 077 && echo "$APP_SECRET" > "$SECRET_FILE" )
    chown "$USER_ID:$GROUP_ID" "$SECRET_FILE"
    echo "APP_SECRET: generated a new unique secret, persisted to var/data volume"
  fi
  export APP_SECRET

  # Mirror the resolved secret into .env.local so Symfony's Dotenv picks up
  # the right value when commands are run via `docker exec` (which does not
  # inherit the entrypoint's exported env). .env.local is Symfony's official
  # override file and is loaded before .env. Rewritten on every container
  # start; the source of truth is the persisted SECRET_FILE above.
  ( umask 077 && echo "APP_SECRET=$APP_SECRET" > "$ENV_LOCAL" )
  # The PHP runtime runs as $USER_ID:$GROUP_ID and must be
  # able to read .env.local; the entrypoint itself runs as root, so the file
  # would otherwise be 0600 root:root and unreadable to the web user, causing
  # Symfony's Dotenv to throw PathException at boot.
  chown "$USER_ID:$GROUP_ID" "$ENV_LOCAL"

  set -x
}

function runServer() {
    # changing the permissions here can be problematic for host mounts
    # chown -R $USER_ID:$GROUP_ID /opt/kimai/var
  exec /usr/sbin/apache2 -D FOREGROUND
}

waitForDB
handleStartup
ensureAppSecret
prepareKimai
runServer
