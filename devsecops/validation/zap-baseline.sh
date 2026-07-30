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

mkdir -p "$REPORT_DIR"

echo "== OWASP ZAP baseline scan against $BASE_URL =="
docker run --rm \
    --network host \
    -v "$REPORT_DIR:/zap/wrk:rw" \
    -t "$ZAP_IMAGE" \
    zap-baseline.py \
    -t "$BASE_URL" \
    -r zap-baseline.html \
    -J zap-baseline.json \
    -I

EXIT_CODE=$?
echo "ZAP baseline report written to $REPORT_DIR (zap-baseline.html / zap-baseline.json)"

if [[ $EXIT_CODE -eq 1 ]]; then
    echo "== ZAP baseline found FAIL level alerts =="
    exit 1
fi
echo "== ZAP baseline passed =="
