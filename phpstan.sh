#!/bin/bash

# ----------------------------------------------------------------------
# Helper script to analyze plugins with PHPStan
# ----------------------------------------------------------------------
# Usage:
#
# ./phpstan.sh [plugin] [params]
# [plugin] - name of the plugin to analyze (without "Bundle")
# [params] - additional parameters for PHPStan, e.g. --level=7 --pro
# ----------------------------------------------------------------------

phpstan() {
	vendor/bin/phpstan analyse -c var/plugins/$1Bundle/phpstan.neon var/plugins/$1Bundle/ ${ARGS}
}

if [[ -n $2 ]]; then
	export ARGS=$2
else
	export ARGS=""
fi

if [[ -n $1 ]]; then
	if [ -d "var/plugins/$1Bundle/" ]; then
		phpstan $1
		exit
	elif [ "$1" == 'core' ]; then
		vendor/bin/phpstan analyse -c phpstan.neon ${ARGS}
		exit
	elif [ "$1" == 'test' ] || [ "$1" == 'tests' ]; then
		vendor/bin/phpstan analyse -c tests/phpstan.neon ${ARGS}
		exit
	else
		echo "Plugin $1 not found"
		exit 1
	fi
fi

vendor/bin/phpstan analyse -c phpstan.neon ${ARGS}
vendor/bin/phpstan analyse -c tests/phpstan.neon ${ARGS}

for dir in var/plugins/*Bundle/
do
	echo ""
	echo "=======> $dir <======="
	echo ""
	vendor/bin/phpstan analyse -c ${dir}phpstan.neon ${dir} ${ARGS}
done
