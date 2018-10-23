# This file is part of the Kimai time-tracking app.
#
# For the full copyright and license information, please view the LICENSE
# file that was distributed with this source code.

setup() {
  IMAGE_NAME="$NAME:$VERSION"
  CONTAINER_NAME="kimai2_test"
}

build_image() {
  docker build -t $IMAGE_NAME $BATS_TEST_DIRNAME/.. 
}

run_image() {
  CONTAINER_ID=$(docker run -d --name $CONTAINER_NAME --rm $IMAGE_NAME)
}

build_compose() {
  docker-compose build 
}
