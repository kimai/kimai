# Docker

## Developing in a docker

The docker file in the root of the source tree can be used to taer up development server.  It needs to mount the files from the host ar run time so edits on the host will be reflected in the docker.

## Building the development docker

You can change the docker tag if you want. Remeber the dot at the end of the command.

    docker build --rm -t tobybatch/kimai:dev .

## Run the development docker.

The docker needs to write out it's cache and log files.  This can cause permissions issues between the host and the docker process.  To alleviate this start the docker with a UID/GID and the whole file tree will be chown to that user.  In the example below it chowns to the current user.

You can then hit the server on http://localhost:8080

    docker run \
        -ti \
        --name kimai \
        -p 8080:8080 \
        -v $(pwd):/opt/kimai \
        -e _UID=$(id -u) \
        -e _GID=$(id -g) \
        tobybatch/kimai:dev

    * ```docker run```
      Run the docker
    * ```-ti```
      Keep an interactive shell attached to the container
    * ```--name kimai```
      Give the running container a name
    * ```-p 8080:8080```
      Foward port 8080 into the container
    * ```-v $(pwd):/opt/kimai```
      Mount kimai into the container
    * ```-e _UID=$(id -u)```
      Set the UID in the conatiner to be the current user
    * ```-e _GID=$(id -g)```
      Set the GID in the conatiner to be the current user
    * ```tobybatch/kimai:dev```
      Run the container that was built inthe previous section

## Running commands in the container

We can run commands in the container.  Just docker know which container you want to affect.

    docker exec -ti kimai SOME COMMAND

e.g. Clear the caches

    docker exec -ti kimai bin/console cache:clear

### Get a shell

Open a bash shell in the running container.

    docker exec -ti kimai bash

### Install developer fixtures

We can reset and populate the DB with fixture data.  See [Development installation](https://github.com/kevinpapst/kimai2/blob/master/var/docs/installation.md#development-installation)

    docker exec -ti kimai bin/console kimai:reset-dev

## Tests

We use Bats (Bash Automated Testing System) to test this image:

> https://github.com/sstephenson/bats

Install Bats, and in the .docker sub folder of this project directory run the tests:

    cd .docker
    make test
