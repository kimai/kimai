#!/bin/bash

# ----------------------------------------------------------------------
# Helper script to analyze plugins with PHP Coding Standards Fixer
# ----------------------------------------------------------------------
# Usage:
#
# ./php-cs-fixer.sh [plugin] [params]
# [plugin] - name of the plugin to analyze (without "Bundle")
# [params] - additional parameters for PHPCsFixer, e.g. --dry-run
# ----------------------------------------------------------------------

phpcsfixer() {
	vendor/bin/php-cs-fixer fix --config var/plugins/$1Bundle/.php-cs-fixer.dist.php ${ARGS}
}

if [[ -n $2 ]]; then
	export ARGS=$2
else
	export ARGS=""
fi

if [[ -n $1 ]]; then
	if [ -d "var/plugins/$1Bundle/" ]; then
		phpcsfixer $1
		exit
	elif [ "$1" == 'core' ]; then
		vendor/bin/php-cs-fixer fix --config .php-cs-fixer.dist.php ${ARGS}
		exit
	else
		echo "Plugin $1 not found"
		exit 1
	fi
fi

vendor/bin/php-cs-fixer fix --config .php-cs-fixer.dist.php ${ARGS}

for dir in var/plugins/*Bundle/
do
	echo ""
	vendor/bin/php-cs-fixer fix --config ${dir}.php-cs-fixer.dist.php ${ARGS}
done
