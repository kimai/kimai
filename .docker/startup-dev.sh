#!/bin/bash

# Is this a first run situation?
if [ ! -e /opt/kimai/var/data/kimai.sqlite ]; then

    if [ ! -e /opt/kimai/.env ]; then
        sed 's/prod/dev/g' /opt/kimai/.env.dist > /opt/kimai/.env
    fi
    composer install

    /opt/kimai/bin/console doctrine:database:create
    /opt/kimai/bin/console doctrine:schema:create
    /opt/kimai/bin/console -n doctrine:migrations:version --add --all
    /opt/kimai/bin/console cache:warmup --env=prod
    /opt/kimai/bin/console kimai:create-user admin admin@example.com ROLE_SUPER_ADMIN admin

    composer require symfony/web-server-bundle --dev
fi

# Do we have a UID/GID to run as?
if [ ! -z "$_GID" ]; then
    # If the group doesn't exist create it.
    if ! egrep -q "^(.+):x:${_GID}" /etc/group; then
        echo Group does not exists, creating GID $_GID
        groupadd --gid ${_GID} kimai
    fi
else
    # Drop privs to run as www-data
    _UID=33
fi

if [ ! -z "$_UID" ]; then
    # If the user doesn't exist create it.
    if ! egrep -q "^(.+):x:${_UID}" /etc/passwd; then
        echo User does not exists, creating UID $_UID
        useradd --gid ${_GID} --uid ${_UID} kimai
    fi
else
    # Drop privs to run as www-data
    _GID=33
fi

chown -R ${_UID}:${_GID} /opt/kimai
_USER=$(id -un $_UID)

su - ${_USER} -c "/opt/kimai/bin/console server:run 0.0.0.0:${PORT}"
