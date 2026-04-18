#!/bin/bash

# --------------------------------------------------------------------------------
# This script was added with 2.24.0 and is in BETA status.
#
# To improve this script across platforms I need your feedback!
# --------------------------------------------------------------------------------

function verbose() {
    if [[ "${IS_VERBOSE}" == "1" ]]; then
        echo "$@"
    fi
}

function flush_cache() {
    rm -rf var/cache/* 2>&1
}

function reload_cache() {
    flush_cache
    bin/console cache:warmup
}

function composer_is_phar() {
    [[ "${KIMAI_COMPOSER}" == *.phar ]]
}

function find_command_path() {
    which "$1" 2>/dev/null
}

function resolve_executable() {
    local executable="$1"

    if [[ -z "${executable}" || "${executable}" == */* || "${executable}" == *.phar ]]; then
        echo "${executable}"
        return
    fi

    local resolved
    resolved="$(find_command_path "${executable}")"
    if [[ -n "${resolved}" ]]; then
        echo "${resolved}"
    else
        echo "${executable}"
    fi
}

function composer_exists() {
    if composer_is_phar; then
        [[ -f "${KIMAI_COMPOSER}" ]]
    elif [[ "${KIMAI_COMPOSER}" == */* ]]; then
        [[ -x "${KIMAI_COMPOSER}" ]]
    else
        command -v "${KIMAI_COMPOSER}" >/dev/null 2>&1
    fi
}

function run_composer() {
    #export COMPOSER_ALLOW_SUPERUSER=1
    if composer_is_phar; then
        "$KIMAI_PHP" "$KIMAI_COMPOSER" "$@"
    else
        "$KIMAI_COMPOSER" "$@"
    fi
}

function confirm_update() {
    local version="$1"
    local answer

    echo "About to update Kimai to version ${version}."
    read -r -p "Continue? [y/N] " answer
    if [[ ! "${answer}" =~ ^[Yy]([Ee][Ss])?$ ]]; then
        echo "Update cancelled."
        exit 1
    fi
}

function update_kimai() {
    if [[ "$1" == "latest" ]]; then
        if ! command -v sort >/dev/null 2>&1 || ! command -v tail >/dev/null 2>&1; then
            echo "we could not detect the latest kimai version due to missing commands: sort, tail"
            exit 1
        fi

        git fetch --tags
        export VERSION="$(git tag --list | sort -V | tail -n 1)"
        if [ -z "$VERSION" ]; then
            echo "Failed loading Kimai version"
            exit 1
        fi
    elif [[ "$1" =~ ^([0-9]+\.){2,3}[0-9]+$ ]]; then
        export VERSION=$1
    else
        echo "You need to supply a full Kimai version like: \"2.24.0\""
        exit 1
    fi

    git fetch --tags

    if ! git rev-parse --verify --quiet "refs/tags/$VERSION" >/dev/null; then
        echo "Requested Kimai version does not exist: $VERSION"
        exit 1
    fi

    confirm_update "$VERSION"

    git checkout -- composer.json
    git checkout -- composer.lock
    git checkout -- symfony.lock

    if [[ -n $(git status --porcelain) ]]; then
        echo "Cannot update: file changes detected. Run \"git status\" for details."
        exit 1
    fi

    rm -rf var/sessions/ 2>&1
    flush_cache

    git checkout "$VERSION"
    run_composer install --no-dev --optimize-autoloader || exit 1

    $KIMAI_PHP bin/console kimai:install

    install_plugins

    if [[ -z "${KIMAI_NO_PERMS}" ]]; then
        set_permission
    fi
}

function install_plugins() {
    # detect if there are additional plugins that we need to install
    local packages_output
    local -a packages

    if ! packages_output="$($KIMAI_PHP bin/console kimai:plugin --composer)"; then
        echo "Failed loading plugin list from kimai:plugin --composer"
        exit 1
    fi

    read -r -a packages <<< "$packages_output"

    if [[ ${#packages[@]} -gt 0 ]]; then
        verbose "Installing Composer plugins: ${packages[*]}"
        run_composer require "${packages[@]}" || exit 1
        $KIMAI_PHP bin/console kimai:plugins --install || exit 1
    else
        verbose "No Composer plugins detected."
    fi
}

function set_permission() {
    chown -R "$KIMAI_USER":"$KIMAI_GROUP" .
    chmod -R g+r .
    chmod -R g+rw var/
}

# ---------------------------------------------------------
# runtime logic below

export IS_VERBOSE=0

POSITIONAL_ARGS=()
for arg in "$@"; do
    if [[ "${arg}" == "-v" ]]; then
        export IS_VERBOSE=1
    else
        POSITIONAL_ARGS+=("${arg}")
    fi
done
set -- "${POSITIONAL_ARGS[@]}"

if [[ -z "${KIMAI_USER}" ]]; then
    export KIMAI_USER=""
fi

if [[ -z "${KIMAI_GROUP}" ]]; then
    export KIMAI_GROUP="www-data"
fi

if [[ -z "${KIMAI_PHP}" ]]; then
    php_path="$(find_command_path php)"
    if [[ -n "${php_path}" ]]; then
        export KIMAI_PHP="${php_path}"
    else
        export KIMAI_PHP="php"
    fi
else
    export KIMAI_PHP="$(resolve_executable "${KIMAI_PHP}")"
fi

if [[ -z "${KIMAI_COMPOSER}" ]]; then
    composer_path="$(find_command_path composer)"
    if [[ -n "${composer_path}" ]]; then
        export KIMAI_COMPOSER="${composer_path}"
    else
        export KIMAI_COMPOSER="composer"
    fi
else
    export KIMAI_COMPOSER="$(resolve_executable "${KIMAI_COMPOSER}")"
fi

cd "$(dirname "$0")" || { echo "Cannot change working directory."; exit 1; }

# we need a few commands installed in order for this script to complete
composer_exists || { echo >&2 "Update requires 'composer' but it's not installed or not executable."; exit 1; }
command -v git >/dev/null 2>&1 || { echo >&2 "Update requires 'git' but it's not installed."; exit 1; }
command -v "$KIMAI_PHP" >/dev/null 2>&1 || { echo >&2 "Update requires 'php' but it's not installed."; exit 1; }

verbose "Using PHP: $KIMAI_PHP"
verbose "Using Composer: $KIMAI_COMPOSER"

if [[ -n $1 ]]; then
    if [ "$1" == 'update' ]; then
        update_kimai "$2"
        exit
    elif [ "$1" == 'permission' ]; then
        set_permission
        exit
    elif [ "$1" == 'plugins' ] || [ "$1" == 'plugin' ]; then
        install_plugins
        exit
    elif [ "$1" == 'cache' ]; then
        reload_cache
        exit
    else
        echo ""
        echo ">> Unknown command: $1"
    fi
fi

echo ""
echo "This script has the following sub-commands:"
echo ""
echo "$0 update            - Update Kimai to the latest version"
echo "$0 update <version>  - Update Kimai to the given version <version>"
echo "$0 permission        - Fix file permissions"
echo "$0 plugin[s]         - Install available plugins from var/packages/*.zip"
echo "$0 cache             - Clear application cache"
echo ""
echo "Append -v anywhere in the command to enable verbose output"
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
echo "$0 -v update"
echo "$0 update 2.54.0 -v"
echo "KIMAI_PHP=/usr/bin/php8.3 $0 2.54.0"
echo "KIMAI_PHP=/usr/bin/php8.3 KIMAI_COMPOSER=/tmp/composer.phar $0 2.24.0"
echo "KIMAI_PHP=php8.3 KIMAI_GROUP=httpd $0 2.24.0"
echo "KIMAI_NO_PERMS=1 KIMAI_PHP=/usr/bin/php8.3 $0 2.24.0"
echo ""
