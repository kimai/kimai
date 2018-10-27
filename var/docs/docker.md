# Docker

We bundle a simple docker that can be used for test purposes.  Either pull it from docker hub or build it.

## Build the docker:

    docker build -t $USER/kimai2 .

## Run the docker

    docker run -ti -p 8001:8001 --name kimai2 --rm $USER/kimai2

There is an admin user with the password admin but no fixtures installed. To install the fixtures:

    docker exec kimai2 bin/console kimai:reset-dev

You can run any command in the container in this fashion.  Add -ti to attach a terminal.

    docker exec -ti kimai2 bash

## Developing against the docker

The following installs assume you have cloned the repo and opened a terminal in the root of the project.

### Grab an initialized sqlite database

If you already have a sqlite DB set up you can skip this step.

    docker run -v /tmp:/tmp $USER/kimai2 cp /opt/kimai/var/data/kimai.sqlite /tmp
    cp /tmp/kimai.sqlite var/data/

### Install using composer

If you have already set up kimai2 using composer you can skip this step too.

First chown the file tree so the www-data user can run composer:

    sudo chown -R www-data:www-data .

Then set up kimai2 using composer:

    docker run -v $(pwd):/opt/kimai composer install

And set us into dev mode and fix permissions:

    sudo chown -R $(id -u):$(id -g) .
    sed "s/prod/dev/g" .env.dist > .env
    sudo chown -R www-data:www-data var

### Run the container

    docker run -ti -p 8001:8001 --name kimai2 -v $(pwd):/opt/kimai --rm $USER/kimai2
