#!/bin/bash

# --------------------------------------------------------------------------------------------------
# This script builds a docker image with all commited changes from the given repository and branch.
# --------------------------------------------------------------------------------------------------

# gets repository url from local git config and changes it to the website url. Example: git@github.com:kimai/kimai.git -> https://github.com/kimai/kimai
repository_url=$(git config --get remote.origin.url | sed -En "s/com:/com\//p" | sed -En "s/git@/https:\/\//p" | sed -En "s/\.git//p")
# gets the current branchname
branchname=$(git rev-parse --abbrev-ref HEAD)
# image base
base="apache"


docker login

# build images from current repository branch
docker build \
--no-cache \
-t kimai-${base} \
--build-arg REPOSITORY=$repository_url \
--build-arg KIMAI=$branchname \
--build-arg BASE=$base \
--build-arg TIMEZONE="Europe/Berlin" .


# --------------------------------------------------------------------------------------------------
# To run the built image uncomment the following line.
# docker run -d --name kimai-${base}-app kimai-${base}
#
# To start the cli of the started container uncomment the following line.
# docker exec -ti kimai-${base}-app /bin/bash
# --------------------------------------------------------------------------------------------------