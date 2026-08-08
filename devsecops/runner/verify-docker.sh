#!/usr/bin/env bash
#
# Preflight check for every pipeline job that shells out to docker.
#
# Why this exists: the on-premise runner runs inside WSL2, where Windows PATH
# interop can make "docker" resolve to the Docker Desktop shim at
#   /mnt/c/Program Files/Docker/Docker/resources/bin/docker
# That shim aborts with "The command 'docker' could not be found in this WSL 2
# distro" whenever Docker Desktop is closed or its WSL integration is off. When
# that happened (run #47) the Trivy and Semgrep steps failed with an error that
# said nothing about the real cause, and the deploy job's image scan failed with
# a bare "unable to find the specified image" (run #36).
#
# This script prefers the natively installed Linux Docker Engine, exports it for
# subsequent steps, and fails fast with an actionable message.
set -euo pipefail

DOCKER_BIN=""

# Prefer the native Linux engine installed by setup-runner.sh over anything
# reachable through /mnt/c Windows PATH interop.
for candidate in /usr/bin/docker /usr/local/bin/docker; do
    if [[ -x "$candidate" ]]; then
        DOCKER_BIN="$candidate"
        break
    fi
done

if [[ -z "$DOCKER_BIN" ]]; then
    DOCKER_BIN="$(command -v docker || true)"
fi

if [[ -z "$DOCKER_BIN" ]]; then
    echo "::error::No docker binary found on this runner."
    echo "::error::Install Docker Engine inside the WSL distro - see devsecops/runner/setup-runner.sh"
    exit 1
fi

# Resolve symlinks before classifying: on this runner /usr/bin/docker is itself
# a link to /mnt/wsl/docker-desktop/cli-tools/..., so testing the literal path
# would report a native engine while actually running the Desktop shim.
DOCKER_REAL="$(readlink -f "$DOCKER_BIN" 2>/dev/null || echo "$DOCKER_BIN")"

case "$DOCKER_REAL" in
    /mnt/c/*|/mnt/wsl/docker-desktop/*)
        echo "::warning::'docker' resolves to the Docker Desktop shim (${DOCKER_BIN} -> ${DOCKER_REAL})."
        echo "::warning::This breaks whenever Docker Desktop is closed or WSL integration is off."
        echo "::warning::Install the native Linux engine and make sure /usr/bin precedes /mnt/c in PATH."
        ;;
esac

if ! "$DOCKER_BIN" info >/dev/null 2>&1; then
    echo "::error::Docker daemon is not reachable via ${DOCKER_BIN}."
    echo "::error::On this WSL2 runner that usually means one of:"
    echo "::error::  - the native Docker Engine is stopped  ->  sudo service docker start"
    echo "::error::  - Docker Desktop's WSL integration is disabled for this distro"
    echo "::error::  - the runner user is not in the 'docker' group (log out and back in)"
    echo "--- docker info output ---"
    "$DOCKER_BIN" info 2>&1 | head -n 20
    exit 1
fi

# Make sure later steps in this job resolve the same working binary.
DOCKER_DIR="$(dirname "$DOCKER_BIN")"
if [[ -n "${GITHUB_PATH:-}" ]]; then
    echo "$DOCKER_DIR" >> "$GITHUB_PATH"
fi

echo "Docker OK: ${DOCKER_BIN} (server $("$DOCKER_BIN" version --format '{{.Server.Version}}'))"
