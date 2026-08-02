#!/usr/bin/env bash
#
# OWASP ZAP baseline scan (passive DAST) against the deployed environment.
# Usage: devsecops/validation/zap-baseline.sh [base-url] [report-dir]
#
# Exit codes of zap-baseline.py: 0 = pass, 1 = FAIL alerts, 2 = warnings.
# The -I flag makes warnings non-blocking; FAIL alerts fail the pipeline.
set -uo pipefail

BASE_URL="${1:-https://localhost:8443}"
REPORT_DIR="${2:-/tmp/zap-reports}"
ZAP_IMAGE="${ZAP_IMAGE:-ghcr.io/zaproxy/zaproxy:stable}"
COMPOSE_NETWORK="${COMPOSE_NETWORK:-kimai-devsecops_frontend}"

mkdir -p "$REPORT_DIR"

# Inside a container, "localhost" is the container - and with Docker Desktop,
# --network host lands in the Desktop VM, not the WSL distro. Scan through the
# compose network instead, where nginx answers on the standard HTTPS port.
# ZAP's baseline plan also mishandles non-default ports (8443 -> 443 fallback).
if [[ "$BASE_URL" =~ ^https://localhost:8443 ]]; then
    ZAP_TARGET="https://nginx"
    NETWORK_ARGS="--network $COMPOSE_NETWORK"
else
    ZAP_TARGET="$BASE_URL"
    NETWORK_ARGS="--network host"
fi

echo "== OWASP ZAP baseline scan against $BASE_URL (target: $ZAP_TARGET) =="
docker run --rm \
    $NETWORK_ARGS \
    -v "$REPORT_DIR:/zap/wrk:rw" \
    -t "$ZAP_IMAGE" \
    zap-baseline.py \
    -t "$ZAP_TARGET" \
    -r zap-baseline.html \
    -J zap-baseline.json \
    -I

EXIT_CODE=$?

# A pass is only real if a report exists - an unreachable target must not
# produce a vacuous "passed"
if [[ ! -s "$REPORT_DIR/zap-baseline.json" && ! -s "$REPORT_DIR/zap-baseline.html" ]]; then
    echo "== ZAP produced no report - the target was not reachable ==" >&2
    exit 1
fi

echo "ZAP baseline report written to $REPORT_DIR (zap-baseline.html / zap-baseline.json)"

if [[ $EXIT_CODE -eq 1 ]]; then
    echo "== ZAP baseline found FAIL level alerts =="
    exit 1
fi
echo "== ZAP baseline passed =="
