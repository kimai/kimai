#!/usr/bin/env bash
#
# Automated secure deployment of Kimai to the local on-premise test environment.
#
# Steps (idempotent, safe to re-run):
#   1. verify prerequisites (docker + compose plugin)
#   2. generate a self-signed TLS certificate (if none exists)
#   3. create .env with random secrets (if none exists)
#   4. build the hardened application image from the repository Dockerfile
#   5. start the hardened compose stack (nginx TLS proxy -> app -> MariaDB)
#   6. run the database migrations (configuration management)
#   7. wait until the application answers over HTTPS
#
# Called by the "deploy" job of .github/workflows/devsecops.yml.
set -euo pipefail

DEPLOY_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$DEPLOY_DIR/../.." && pwd)"
BASE_URL="${1:-https://localhost:8443}"

cd "$DEPLOY_DIR"

echo "==> [1/7] Checking prerequisites"
command -v docker >/dev/null 2>&1 || { echo "ERROR: docker is not installed" >&2; exit 1; }
docker compose version >/dev/null 2>&1 || { echo "ERROR: docker compose plugin is not installed" >&2; exit 1; }

echo "==> [2/7] Ensuring TLS certificate"
./nginx/generate-certs.sh

echo "==> [3/7] Ensuring deployment secrets (.env)"
if [[ ! -f .env ]]; then
    sed -e "s|^APP_SECRET=.*|APP_SECRET=$(openssl rand -hex 32)|" \
        -e "s|^DB_PASSWORD=.*|DB_PASSWORD=$(openssl rand -hex 16)|" \
        -e "s|^DB_ROOT_PASSWORD=.*|DB_ROOT_PASSWORD=$(openssl rand -hex 16)|" \
        .env.example > .env
    chmod 600 .env
    echo "    created .env with random secrets"
else
    echo "    .env already exists - keeping it"
fi

echo "==> [4/7] Building application image (hardened apache variant)"
docker build --build-arg BASE=apache --build-arg KIMAI=devsecops -t kimai-devsecops:local "$REPO_ROOT"

echo "==> [5/7] Starting hardened compose stack"
if ! docker compose --env-file .env -f docker-compose.yml up -d; then
    echo "ERROR: compose stack failed to start - container states and logs:" >&2
    docker compose --env-file .env -f docker-compose.yml ps -a >&2 || true
    docker compose --env-file .env -f docker-compose.yml logs --tail=80 >&2 || true
    exit 1
fi

echo "==> [6/7] Running database migrations (configuration management)"
# the Kimai image has no WORKDIR - use absolute paths inside the container
docker compose exec -T app /opt/kimai/bin/console doctrine:database:create --if-not-exists --no-interaction || true
docker compose exec -T app /opt/kimai/bin/console doctrine:migrations:migrate --no-interaction

echo "==> [7/7] Waiting for the application over HTTPS ($BASE_URL)"
ATTEMPTS=0
until curl -ksf -o /dev/null "$BASE_URL/en/login"; do
    ATTEMPTS=$((ATTEMPTS + 1))
    if [[ $ATTEMPTS -ge 40 ]]; then
        echo "ERROR: application did not become ready in time - container states and logs:" >&2
        docker compose ps -a >&2 || true
        docker compose logs --tail=80 >&2 || true
        exit 1
    fi
    sleep 5
done

echo ""
echo "Deployment finished:"
echo "  - HTTPS:  $BASE_URL"
echo "  - HTTP:   http://localhost:8080 (redirects to HTTPS)"
echo "  - Create a demo admin with:"
echo "      docker compose -f $DEPLOY_DIR/docker-compose.yml exec -T app \\"
echo "          /opt/kimai/bin/console kimai:user:create admin admin@example.com ROLE_SUPER_ADMIN"
