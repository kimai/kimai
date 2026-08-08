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

# This script runs immediately after deploy.sh, while the container may still be
# warming up (cache warmup, opcache, first DB connection). Judging availability
# and page content on a single cold request produced spurious failures - most
# often "login page does not look like Kimai" while the app was still starting.
# Retry those two checks instead; genuine breakage still fails after the budget.
RETRIES="${SMOKE_RETRIES:-10}"
RETRY_DELAY="${SMOKE_RETRY_DELAY:-3}"

retry() {
    local attempt=1
    until "$@" >/dev/null 2>&1; do
        if (( attempt >= RETRIES )); then
            return 1
        fi
        attempt=$(( attempt + 1 ))
        sleep "$RETRY_DELAY"
    done
    return 0
}

login_page_is_kimai() {
    curl -ksf "$BASE_URL/en/login" | grep -qi "kimai"
}

echo "== Smoke test against $BASE_URL =="
echo "   (availability and content checks retry up to ${RETRIES}x every ${RETRY_DELAY}s)"

# 1. login page is served over HTTPS
check "HTTPS login page returns HTTP 200" \
    retry curl -ksf -o /dev/null -w '%{http_code}' "$BASE_URL/en/login"

# 2. the page is actually Kimai (integrity: expected content marker)
check "login page contains Kimai marker" \
    retry login_page_is_kimai

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
