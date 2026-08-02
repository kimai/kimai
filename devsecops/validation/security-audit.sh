#!/usr/bin/env bash
#
# Post-deployment configuration audit (WSTG-CONF / OWASP Secure Headers).
# Verifies the hardening applied by devsecops/deploy (nginx + app config).
# Usage: devsecops/validation/security-audit.sh [base-url]
set -uo pipefail

BASE_URL="${1:-https://localhost:8443}"
FAILED=0

pass() { echo "PASS: $1"; }
fail() { echo "FAIL: $1"; FAILED=1; }

header_check() {
    local description="$1" header="$2" pattern="$3"
    if echo "$HEADERS" | grep -qiP "^$header:\s*.*$pattern"; then
        pass "$description"
    else
        fail "$description (missing or unexpected '$header' header)"
    fi
}

echo "== Configuration audit against $BASE_URL =="

HEADERS=$(curl -ksI "$BASE_URL/en/login")

# --- security headers -------------------------------------------------------
header_check "HSTS is enforced"                        Strict-Transport-Security "max-age=[0-9]+"
header_check "X-Content-Type-Options: nosniff"         X-Content-Type-Options    "nosniff"
header_check "X-Frame-Options is set"                  X-Frame-Options           "(SAMEORIGIN|DENY)"
header_check "Referrer-Policy is set"                  Referrer-Policy           ".+"
header_check "Permissions-Policy is set"               Permissions-Policy        ".+"
# the deployment emits CSP in Report-Only mode on purpose (see DEPLOYMENT.md) -
# accept either the enforcing or the report-only variant
header_check "Content-Security-Policy is present (enforcing or report-only)" "Content-Security-Policy(-Report-Only)?" ".+"

# --- information disclosure -------------------------------------------------
if echo "$HEADERS" | grep -qiP '^Server:\s*nginx/[0-9]'; then
    fail "Web server leaks its version in the Server header"
else
    pass "Web server does not leak its version"
fi

if echo "$HEADERS" | grep -qi '^X-Powered-By:'; then
    fail "X-Powered-By header leaks the runtime"
else
    pass "No X-Powered-By header"
fi

# --- session cookie flags ----------------------------------------------------
COOKIE=$(curl -ksI "$BASE_URL/en/login" | grep -i '^Set-Cookie:' || true)
if echo "$COOKIE" | grep -qi 'httponly'; then pass "Session cookie has HttpOnly"; else fail "Session cookie misses HttpOnly"; fi
if echo "$COOKIE" | grep -qi 'samesite='; then pass "Session cookie has SameSite"; else fail "Session cookie misses SameSite"; fi
if echo "$COOKIE" | grep -qi 'secure';   then pass "Session cookie has Secure flag (over HTTPS)"; else fail "Session cookie misses Secure flag (over HTTPS)"; fi

# --- HTTP methods -------------------------------------------------------------
TRACE_STATUS=$(curl -ks -o /dev/null -w '%{http_code}' -X TRACE "$BASE_URL/" || true)
if [[ "$TRACE_STATUS" == "405" || "$TRACE_STATUS" == "403" || "$TRACE_STATUS" == "404" ]]; then
    pass "TRACE method is disabled ($TRACE_STATUS)"
else
    fail "TRACE method is answered with $TRACE_STATUS"
fi

# --- sensitive files / debug tooling ------------------------------------------
for path in /.env /.git/config /composer.json /app_dev.php /_profiler /_wdt/123; do
    STATUS=$(curl -ks -o /dev/null -w '%{http_code}' "$BASE_URL$path" || true)
    if [[ "$STATUS" == "403" || "$STATUS" == "404" ]]; then
        pass "Sensitive path $path is not accessible ($STATUS)"
    else
        fail "Sensitive path $path returned $STATUS"
    fi
done

# --- debug mode ---------------------------------------------------------------
# the Symfony debug toolbar/profiler must not be reachable; error pages must not leak traces
BODY=$(curl -ks "$BASE_URL/this-page-does-not-exist")
if echo "$BODY" | grep -qiE 'exception|stack trace|\.php on line|Symfony Profiler'; then
    fail "Error pages leak internal information"
else
    pass "Error pages do not leak internals (debug mode off)"
fi

# --- TLS version floor ----------------------------------------------------------
if curl -ks --tlsv1.0 --tls-max 1.0 -o /dev/null "$BASE_URL/en/login" 2>/dev/null; then
    fail "TLS 1.0 is accepted"
else
    pass "TLS 1.0 is rejected"
fi
if curl -ks --tlsv1.1 --tls-max 1.1 -o /dev/null "$BASE_URL/en/login" 2>/dev/null; then
    fail "TLS 1.1 is accepted"
else
    pass "TLS 1.1 is rejected"
fi

if [[ $FAILED -ne 0 ]]; then
    echo "== Configuration audit FAILED =="
    exit 1
fi
echo "== Configuration audit passed =="
