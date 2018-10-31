# Docker

We bundle a simple docker that can be used for test purposes.  Either pull it from docker hub or build it.

## Build the docker:

    docker build -t kimai/kimai2:dev .

## Run the docker

    docker run -ti -p 8001:8001 --name kimai2 --rm kimai/kimai:dev2

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

It is possible to mount your source tree and sqlite DB into the container at run time.

    docker run --rm -d -p 8001:8001 \
        -v $(pwd)/src:/opt/kimai/src \
        -v $(pwd)/var/data/kimai.sqlite:/opt/kimai/var/data/kimai.sqlite \
        --name kimai2 kimai/kimai2:dev

Now edits in the local file tree will be served by the container and database changes will persist.

See [The official docker documentation](https://docs.docker.com/) for more options on running the container.
