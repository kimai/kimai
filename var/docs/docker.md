# Docker

There is an official docker repo for the kimai app, https://github.com/tobybatch/kimai2 and a dockerhub repo https://hub.docker.com/r/kimai/kimai2.  Please raise any container issues [there](https://github.com/tobybatch/kimai2/issues).

We bundle a simple docker that can be used for test purposes.  Either pull it from docker hub or build it.

## Build the docker

    docker build -t kimai/kimai2:dev .

## Run the docker

    docker run -ti -p 8001:8001 --name kimai2 --rm kimai/kimai2:dev

You can then access the site on http://127.0.0.1:8001. If that doesn't work check the IP of your docker:

    docker inspect -f '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' kimai2

You can find Kimai at that IP on port 8001.

### Mac using docker-machine

When using dock-machine on your Mac, you need to use the IP of your machine. 
Considering you started the machine named `default`, you find the IP with:

    docker-machine ip default

## Running commands in the docker

You can run any command in the container in this fashion once it is started.  Add `-ti` to attach a terminal.

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

## Further docker solutions

As there are several approaches to docker images, you have multiple docker images to choose from. 
They were built by the community, we do not support them - for question please ask in their repositories:

- [dysonspherelab](https://hub.docker.com/r/dysonsphere/kimai2/) (for more infos [read this issue](https://github.com/kevinpapst/kimai2/issues/284))
- [felixhummel](https://github.com/felixhummel/kimai-in-docker/tree/kimai2)
- [Haidy777](https://github.com/Haidy777/kimai2/blob/docker/Dockerfile) (for more infos [read this issue](https://github.com/kevinpapst/kimai2/pull/311))
