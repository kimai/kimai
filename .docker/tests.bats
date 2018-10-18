#!/usr/bin/env bats

load test_helper

@test "image build" {

  run build_image
  [ "$status" -eq 0 ]

}
