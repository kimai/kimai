# Docker

 * [Developer docker](#developing-in-a-docker) (apache, sqlite)
 * [Production docker](#production-docker-compose) (docker-compose, mariadb, nginx, php-fpm)
 * [Tests](#tests)

## Developing in a docker

The developer docker manages the resources required to run a checkout of Kimai against locally hosted files.  The DB data is stored in sqlite database.  File edits against the local files are reflected in the running docker.

### Requirements

#### Docker

Follow the follwing links to install docker on your OS.

 * Debian: https://www.digitalocean.com/community/tutorials/how-to-install-and-use-docker-on-debian-9
 * Fedora: https://www.techrepublic.com/article/how-to-install-docker-on-fedora-25/
 * Ubuntu: https://www.digitalocean.com/community/tutorials/how-to-install-and-use-docker-on-ubuntu-18-04
 * Windows: https://docs.docker.com/docker-for-windows/install/

### Building the development docker

You can change the docker tag if you want. Remeber the dot at the end of the command.

    docker build --rm -t tobybatch/kimai:dev .

### Run the development docker.

The docker needs to write out it's cache and log files.  This can cause permissions issues between the host and the docker process.  To alleviate this start the docker with a UID/GID and the whole file tree will be chown to that user.  In the example below it chowns to the current user.

You can then hit the server on http://localhost:8080

    docker run \
        -ti \
        --name kimai \
        --rm \
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
    * ```--rm```
      Clean up the dev container when it exists
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

### Running commands in the container

We can run commands in the container.  Just docker know which container you want to affect.

    docker exec -ti kimai SOME COMMAND

e.g. Clear the caches

    docker exec -ti kimai bin/console cache:clear

#### Get a shell

Open a bash shell in the running container.

    docker exec -ti kimai bash

#### Install developer fixtures

We can reset and populate the DB with fixture data.  See [Development installation](https://github.com/kevinpapst/kimai2/blob/master/var/docs/installation.md#development-installation)

    docker exec -ti kimai bin/console kimai:reset-dev

## Production docker compose

### Requirements

#### Docker-compose

You will need to install docker compose: https://docs.docker.com/compose/install/

### Running the cluster

Make sure you are in the ```.docker``` sub-folder of the install root:

    cd .docker
    docker-compose up --build

If you add the -d flag you'll run in the background:

    docker-compose up --build -d

### Create an admin user

In a seperate terminal (unless you started into the background) run:

    docker-compose exec php bin/console kimai:create-user username admin@example.com ROLE_SUPER_ADMIN

### Running commands

The php image has the kimai installation.  You can run any shell command against that instance:

    docker-compose exec php WHATEVER COMMAND YOU WANT

### Data persitance

The mysql instance persists it's data to a docker volume.  You can either back up that volume (FOLDERNAME_mysql) or follow the instructions here: https://hub.docker.com/_/mysql/

#### tldr;


    docker-compose exec db sh -c 'exec mysqldump -ulamp -plamp lamp' > /some/path/on/your/host/all-databases.sql

## Tests

We use Bats (Bash Automated Testing System) to test this image:

> https://github.com/sstephenson/bats

Install Bats, and in the .docker sub folder of this project directory run the tests:

    cd .docker
    make test
