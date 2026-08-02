#!/usr/bin/env bash
#
# Provisions an on-premise Ubuntu 22.04/24.04 host (also works on WSL2) as a
# self-hosted GitHub Actions runner for the DevSecOps pipeline
# (.github/workflows/devsecops.yml).
#
# What it installs/configures:
#   - PHP 8.3 + extensions used by Kimai, Composer
#   - MariaDB (for local/manual test runs; CI jobs use a service container)
#   - Docker Engine + compose plugin (deployment, scanner images)
#   - GitHub CLI (developer notifications via GitHub Issues)
#   - the shared report directory /opt/kimai/security-reports
#
# After this script finishes, register the runner manually:
#   https://github.com/<owner>/<repo>/settings/actions/runners/new
#   Required labels: self-hosted, linux, kimai-devsecops
set -euo pipefail

if [[ $EUID -eq 0 ]]; then
    echo "Run this script as a normal user with sudo rights, not as root" >&2
    exit 1
fi

echo "==> Installing base packages"
sudo apt-get update -y

. /etc/os-release

# PHP 8.3: Ubuntu 24.04+ ships it natively; older Ubuntu releases (e.g. 22.04,
# whose default php-cli is 8.1 - too old for Kimai) get it via the ondrej PPA,
# the same source the setup-php GitHub action uses
PHP_PKG_PREFIX="php8.3"
if [[ "${ID:-}" == "ubuntu" && "${VERSION_ID%%.*}" -lt 24 ]]; then
    sudo DEBIAN_FRONTEND=noninteractive apt-get install -y software-properties-common
    sudo add-apt-repository -y ppa:ondrej/php
    sudo apt-get update -y
elif [[ "${ID:-}" != "ubuntu" ]]; then
    # Debian/Kali and others: distribution default PHP (must be >= 8.2 for Kimai)
    PHP_PKG_PREFIX="php"
fi

sudo DEBIAN_FRONTEND=noninteractive apt-get install -y \
    ${PHP_PKG_PREFIX}-cli ${PHP_PKG_PREFIX}-gd ${PHP_PKG_PREFIX}-intl ${PHP_PKG_PREFIX}-mbstring \
    ${PHP_PKG_PREFIX}-xml ${PHP_PKG_PREFIX}-zip ${PHP_PKG_PREFIX}-xsl ${PHP_PKG_PREFIX}-curl \
    ${PHP_PKG_PREFIX}-mysql ${PHP_PKG_PREFIX}-ldap \
    mariadb-server mariadb-client \
    curl git unzip ca-certificates gnupg

php -r 'exit(version_compare(PHP_VERSION, "8.2.0", ">=") ? 0 : 1);' \
    || echo "WARNING: system PHP is older than 8.2 - CI jobs use setup-php (8.3), but manual composer/phpunit runs need PHP >= 8.2"

echo "==> Installing Composer"
if ! command -v composer >/dev/null 2>&1; then
    curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php
    sudo php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer
fi

echo "==> Installing Docker Engine"
if ! command -v docker >/dev/null 2>&1; then
    curl -fsSL https://get.docker.com | sudo sh
    sudo usermod -aG docker "$USER"
    echo "NOTE: log out and back in for docker group membership to take effect"
fi

echo "==> Installing GitHub CLI"
if ! command -v gh >/dev/null 2>&1; then
    sudo mkdir -p -m 755 /etc/apt/keyrings
    curl -fsSL https://cli.github.com/packages/githubcli-archive-keyring.gpg \
        | sudo dd of=/etc/apt/keyrings/githubcli-archive-keyring.gpg
    echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/githubcli-archive-keyring.gpg] https://cli.github.com/packages stable main" \
        | sudo tee /etc/apt/sources.list.d/github-cli.list > /dev/null
    sudo apt-get update -y
    sudo apt-get install -y gh
fi

echo "==> Preparing MariaDB test database (for manual test runs)"
sudo service mariadb start
sudo mysql <<'SQL'
CREATE DATABASE IF NOT EXISTS kimai2_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'kimai2_test'@'127.0.0.1' IDENTIFIED BY 'kimai2_test';
GRANT ALL PRIVILEGES ON kimai2_test.* TO 'kimai2_test'@'127.0.0.1';
FLUSH PRIVILEGES;
SQL

echo "==> Creating shared security report directory"
sudo mkdir -p /opt/kimai/security-reports
sudo chown -R "$USER:$USER" /opt/kimai

echo "==> Pre-pulling scanner images (optional, speeds up first run)"
docker pull semgrep/semgrep:latest || true
docker pull aquasec/trivy:latest || true
docker pull ghcr.io/zaproxy/zaproxy:stable || true
docker pull owasp/dependency-check:latest || true

cat <<'DONE'

Provisioning complete.

Next steps:
  1. Register the self-hosted runner (repo Settings > Actions > Runners > New)
     with the labels: self-hosted, linux, kimai-devsecops
  2. Optional secrets (repo Settings > Secrets and variables > Actions):
       SONAR_TOKEN                  - token of the on-premise SonarQube server
       NVD_API_KEY                  - speeds up OWASP Dependency-Check
       SECURITY_NOTIFY_WEBHOOK_URL  - Slack/Discord incoming webhook
  3. Optional variable:
       SONAR_HOST_URL               - e.g. http://sonarqube.local:9000
  4. Review devsecops/docs/PIPELINE.md and devsecops/docs/MAINTENANCE.md
DONE
