#!/bin/bash -x

KIMAI=$(cat /opt/kimai/version.txt)
echo $KIMAI


function config() {
  # set mem limits and copy in custom logger config
  if [ -z "$memory_limit" ]; then
    memory_limit=256M
  fi


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
  # set mem limits and copy in custom logger config
  sed -i "s/memory_limit.*/memory_limit=$memory_limit/g" /usr/local/etc/php/php.ini
  cp /assets/monolog.yaml /opt/kimai/config/packages/monolog.yaml

  tar -zx -C /opt/kimai -f /var/tmp/public.tgz

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

config
handleStartup

exec /service.sh
