
setup() {
  IMAGE_NAME="$NAME:$VERSION"
}


build_image() {
  docker build -t $IMAGE_NAME $BATS_TEST_DIRNAME/.. 
}
