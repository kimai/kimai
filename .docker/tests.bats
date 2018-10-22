#!/usr/bin/env bats

load test_helper

@test "image build" {

  run build_image
  [ "$status" -eq 0 ]

}

@test "image compose" {

  run build_compose
  [ "$status" -eq 0 ]

}

@test "run image" {

  run run_image
  [ "$status" -eq 0 ]
  docker stop $CONTAINER_NAME
}
