#!/usr/bin/env bats

# This file is part of the Kimai time-tracking app.
#
# For the full copyright and license information, please view the LICENSE
# file that was distributed with this source code.

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
