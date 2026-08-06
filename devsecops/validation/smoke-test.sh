#!/usr/bin/env bash
set -euo pipefail

BASE_URL="${1:-https://localhost:8443}"
TRIES=12
SLEEP=5
# More robust markers: check for Kimai (case-insensitive), or for login form fields
EXPECTED_REGEX="Kimai|<title[^>]*>.*Kimai|name=\"_username\"|name=\"_password\"|id=\"login\"|class=\"login\""

echo "== Smoke test against $BASE_URL =="
for i in $(seq 1 $TRIES); do
  echo "--- Attempt $i/$TRIES ---"
  HTML=$(curl -k -sS "$BASE_URL/en/login" || true)
  HTTP_STATUS=$(curl -k -s -o /dev/null -w "%{http_code}" "$BASE_URL/en/login" || echo "000")
  echo "HTTP status: $HTTP_STATUS"
  if echo "$HTML" | grep -qiE "$EXPECTED_REGEX"; then
    echo "PASS: login page looks like Kimai"
    break
  fi
  echo "login page does not look like Kimai; sleeping $SLEEP seconds"
  sleep "$SLEEP"
  if [[ $i -eq $TRIES ]]; then
    echo "== Final captured page (truncated) =="
    echo "$HTML" | sed -n '1,400p'
    echo ""
    echo "== Container states and recent logs =="
    docker compose ps -a || true
    docker compose logs --tail=200 || true
    echo "== Smoke test FAILED =="
    exit 1
  fi
done

# HTTP -> HTTPS redirect check (same as before)
HTTP_PORT="${HTTP_PORT:-8080}"
REDIRECT=$(curl -s -o /dev/null -w '%{http_code}' "http://localhost:$HTTP_PORT/en/login" || true)
if [[ "$REDIRECT" == "301" || "$REDIRECT" == "302" ]]; then
  echo "PASS: HTTP redirects to HTTPS ($REDIRECT)"
else
  echo "FAIL: HTTP did not redirect to HTTPS (got $REDIRECT)"
  exit 1
fi

# API check (same as before)
API_STATUS=$(curl -ks -o /dev/null -w '%{http_code}' "$BASE_URL/api/ping" || true)
if [[ "$API_STATUS" == "401" ]]; then
  echo "PASS: API rejects unauthenticated requests (401)"
else
  echo "FAIL: API ping without credentials returned $API_STATUS (expected 401)"
  exit 1
fi

echo "== Smoke test passed =="
