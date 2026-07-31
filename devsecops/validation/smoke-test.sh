#!/usr/bin/env bash
#
# Post-deployment smoke test: service availability and basic integrity checks.
# Usage: devsecops/validation/smoke-test.sh [base-url]
set -euo pipefail

BASE_URL="${1:-https://localhost:8443}"
FAILED=0

check() {
    local name="$1"
    shift
    if "$@" >/dev/null 2>&1; then
        echo "PASS: $name"
    else
        echo "FAIL: $name"
        FAILED=1
    fi
}

echo "== Smoke test against $BASE_URL =="

# 1. login page is served over HTTPS
check "HTTPS login page returns HTTP 200" \
    curl -ksf -o /dev/null -w '%{http_code}' "$BASE_URL/en/login"

# 2. the page is actually Kimai (integrity: expected content marker)
curl -ksf "$BASE_URL/en/login" | grep -qi "kimai" && echo "PASS: login page contains Kimai marker" || { echo "FAIL: login page does not look like Kimai"; FAILED=1; }

# 3. plain HTTP redirects to HTTPS
HTTP_PORT="${HTTP_PORT:-8080}"
REDIRECT=$(curl -s -o /dev/null -w '%{http_code}' "http://localhost:$HTTP_PORT/en/login" || true)
if [[ "$REDIRECT" == "301" || "$REDIRECT" == "302" ]]; then
    echo "PASS: HTTP redirects to HTTPS ($REDIRECT)"
else
    echo "FAIL: HTTP did not redirect to HTTPS (got $REDIRECT)"
    FAILED=1
fi

# 4. API rejects unauthenticated requests
API_STATUS=$(curl -ks -o /dev/null -w '%{http_code}' "$BASE_URL/api/ping" || true)
if [[ "$API_STATUS" == "401" ]]; then
    echo "PASS: API rejects unauthenticated requests (401)"
else
    echo "FAIL: API ping without credentials returned $API_STATUS (expected 401)"
    FAILED=1
fi

if [[ $FAILED -ne 0 ]]; then
    echo "== Smoke test FAILED =="
    exit 1
fi
echo "== Smoke test passed =="
