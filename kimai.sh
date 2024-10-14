#!/bin/bash

# --------------------------------------------------------------------------------
# This script was added with 2.24.0 and is in BETA status.
#
# To improve this script across platforms I need your feedback!
# --------------------------------------------------------------------------------

function update_kimai() {
    if [[ "$1" =~ ^([0-9]+\.){2,3}[0-9]+$ ]]; then
        export VERSION=$1
    else
        echo "You need to supply a full Kimai version like: \"2.24.0\""
        exit 1
    fi
    git checkout -- composer.json
    git checkout -- composer.lock
    git checkout -- symfony.lock

    if [[ -n $(git status --porcelain) ]]; then
        echo "Cannot update: file changes detected. Run \"git status\" for details."
        exit 1
    fi

    rm -rf var/cache/* 2>&1
    git fetch --tags
    git checkout "$VERSION"
    $KIMAI_PHP "$KIMAI_COMPOSER" install --no-dev --optimize-autoloader

    $KIMAI_PHP bin/console kimai:install

    install_plugins

    if [[ -z "${KIMAI_NO_PERMS}" ]]; then
        set_permission
    fi
}

function install_plugins() {
    # detect if there are additional plugins that we need to install
    packages="$($PHP bin/console kimai:plugin --composer)"
    export PACKAGES=$packages
    if [ -n "$PACKAGES" ]; then
        $KIMAI_PHP "$KIMAI_COMPOSER" require "$PACKAGES"
        $KIMAI_PHP bin/console kimai:plugins --install
    fi
}

function set_permission() {
    chown -R "$KIMAI_USER":"$KIMAI_GROUP" .
    chmod -R g+r .
    chmod -R g+rw var/
}

if [[ -z "${KIMAI_USER}" ]]; then
    export KIMAI_USER=""
fi

if [[ -z "${KIMAI_GROUP}" ]]; then
    export KIMAI_GROUP="www-data"
fi

if [[ -z "${KIMAI_PHP}" ]]; then
    export KIMAI_PHP="php"
fi

if [[ -z "${KIMAI_COMPOSER}" ]]; then
    export KIMAI_COMPOSER="composer"
fi

cd "$(dirname "$0")" || { echo "Cannot change working directory."; exit 1; }

# we need a few commands installed in order for this script to complete
command -v $KIMAI_COMPOSER >/dev/null 2>&1 || { echo >&2 "Update requires 'composer' but it's not installed."; exit 1; }
command -v git >/dev/null 2>&1 || { echo >&2 "Update requires 'git' but it's not installed."; exit 1; }
command -v $KIMAI_PHP >/dev/null 2>&1 || { echo >&2 "Update requires 'php' but it's not installed."; exit 1; }

if [[ -n $1 ]]; then
    if [ "$1" == 'update' ]; then
        update_kimai "$2"
        exit
    elif [ "$1" == 'permission' ]; then
        set_permission
        exit
    elif [ "$1" == 'plugins' ]; then
        install_plugins
        exit
    else
        echo ""
        echo ">> Unknown command: $1"
    fi
fi

echo ""
echo "This script has the following sub-commands:"
echo ""
echo "$0 update <version>  - Install Kimai version <version>"
echo "$0 permission        - Fix file permissions"
echo "$0 plugins           - Install plugins from var/packages/*.zip"
echo ""
echo "Use the following environment variables to customize the runtime:"
echo ""
echo "KIMAI_USER                   - Username of the webserver/php process that needs write access"
echo "KIMAI_GROUP                  - Group of the webserver/php process that needs write access"
echo "KIMAI_PHP                    - Full path to PHP executable in the correct version"
echo "KIMAI_COMPOSER               - Path to composer executable or .phar file"
echo "KIMAI_NO_PERMS               - Skip changing permissions"
echo ""
echo "Examples:"
echo ""
echo "$0 2.24.0"
echo "KIMAI_PHP=/usr/bin/php8.3 $0 2.24.0"
echo "KIMAI_PHP=/usr/bin/php8.3 KIMAI_COMPOSER=/tmp/composer.phar $0 2.24.0"
echo "KIMAI_PHP=php8.3 KIMAI_GROUP=httpd $0 2.24.0"
echo "KIMAI_NO_PERMS=1 KIMAI_PHP=/usr/bin/php8.3 $0 2.24.0"
echo ""
