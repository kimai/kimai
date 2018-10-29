# Docker

We bundle a simple docker that can be used for test purposes.  Either pull it from docker hub or build it.

## Build the docker:

    docker build -t kimai/kimai2 .

## Run the docker

    docker run -ti -p 8001:8001 --name kimai2 --rm kimai/kimai2

The you can then access the site on http://127.0.0.1:8001 or on Mac you may need to use the docker IP:

    docker inspect -f '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' kimai2

You can hit kimai at that ip on port 8001.

## Running commands in the docker

You can run any command in the container in this fashion once it is started.  Add -ti to attach a terminal.

    docker exec -ti kimai2 bash

## Create a user and dummy data

See the docs [here](installation.md) for full instructions, but this creates a user admin/admin with all privileges.

    docker exec kimai2 bin/console kimai:create-user admin admin@example.com ROLE_SUPER_ADMIN admin

To install the fixtures:

    docker exec kimai2 bin/console kimai:reset-dev

## Developing against the docker

The following installs assume you have cloned the repo and opened a terminal in the root of the project.

### Set up a sqlite database

If you already have a sqlite DB just copy it to var/data/kimai.sqlite relative to the project root.

Else if you want a fresh database that will persist your changes then copy it out of the container.

    docker run -v /tmp:/tmp kimai/kimai2 cp /opt/kimai/var/data/kimai.sqlite /tmp
    cp /tmp/kimai.sqlite var/data/

This will mount a directory in the container and copy the initialised database to it.  Then copy it into your project tree to provided a working sqlite database.

### Install using composer

First chown the file tree so the www-data user can run composer:

    sudo chown -R www-data:www-data .

Then set up kimai2 using composer:

    docker run -v $(pwd):/opt/kimai kimai/kimai2 composer install

### Check permissions and environment

And set us into dev mode and fix permissions:

    sudo chown -R $(id -u):$(id -g) .
    sed "s/prod/dev/g" .env.dist > .env
    sudo chown -R www-data:www-data var

### Run the container

    docker run -ti -p 8001:8001 --name kimai2 -v $(pwd):/opt/kimai --rm kimai/kimai2
