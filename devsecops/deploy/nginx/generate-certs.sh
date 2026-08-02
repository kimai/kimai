#!/usr/bin/env bash
#
# Generates a self-signed certificate for the local test environment.
# Real on-premise deployments should replace it with a certificate from the
# internal CA (place kimai.crt / kimai.key into devsecops/deploy/nginx/certs/).
set -euo pipefail

# CERTS_DIR can point outside the checkout (persistent runner state)
CERT_DIR="${CERTS_DIR:-$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/certs}"
mkdir -p "$CERT_DIR"

if [[ -f "$CERT_DIR/kimai.crt" && -f "$CERT_DIR/kimai.key" ]]; then
    echo "TLS certificate already exists in $CERT_DIR - skipping generation"
    exit 0
fi

openssl req -x509 -nodes -newkey rsa:2048 \
    -keyout "$CERT_DIR/kimai.key" \
    -out "$CERT_DIR/kimai.crt" \
    -days 825 \
    -subj "/C=DE/O=Kimai DevSecOps Lab/CN=localhost" \
    -addext "subjectAltName=DNS:localhost,IP:127.0.0.1"

chmod 600 "$CERT_DIR/kimai.key"
chmod 644 "$CERT_DIR/kimai.crt"

echo "Generated self-signed TLS certificate in $CERT_DIR (replace with internal CA certs for production)"
