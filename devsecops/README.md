# Kimai DevSecOps Pipeline

Automated cybersecurity testing and DevSecOps integration for the
[Kimai](https://github.com/kimai/kimai) time-tracking application.

Everything runs **on-premise** on a self-hosted runner - no cloud deployment.

## Contents

| Path | Purpose |
|---|---|
| `.github/workflows/devsecops.yml` | CI/CD pipeline: SAST, dependency scanning, security tests, secure deployment, DAST, developer notification |
| `tests/SecurityTesting/` | OWASP WSTG v4.2 security test suite (PHPUnit, `--group security`) |
| `devsecops/runner/setup-runner.sh` | Provisions an Ubuntu 24.04 host as the on-premise runner |
| `devsecops/semgrep/rules/` | Gating Kimai Semgrep ruleset (taint tracking) |
| `devsecops/sonar/` | SonarQube project configuration (optional) |
| `devsecops/dependency-check/` | OWASP Dependency-Check suppressions |
| `devsecops/deploy/` | Automated hardened deployment (Docker Compose + nginx TLS) |
| `devsecops/validation/` | Post-deployment checks: smoke test, configuration audit, ZAP baseline |
| `devsecops/git-hooks/` | Optional commit policy enforcement (pre-commit hook) |
| `devsecops/docs/` | Pipeline design, test justification, deployment, maintenance, reporting |

## Quick start

```bash
# 1. provision the on-premise runner host (Ubuntu 24.04)
sudo devsecops/runner/setup-runner.sh

# 2. run the OWASP WSTG security tests locally
DATABASE_URL="mysql://kimai2_test:kimai2_test@127.0.0.1:3306/kimai2_test?charset=utf8mb4&serverVersion=10.11.0-MariaDB" \
    vendor/bin/phpunit --group security tests/SecurityTesting/

# 3. deploy the hardened local environment (requires Docker)
devsecops/deploy/deploy.sh

# 4. validate the deployment
devsecops/validation/smoke-test.sh https://localhost:8443
devsecops/validation/security-audit.sh https://localhost:8443
```

## Documentation

- [Pipeline design & tool justification](docs/PIPELINE.md)
- [Security test cases & OWASP WSTG mapping](docs/TEST_CASES.md)
- [Automated secure deployment](docs/DEPLOYMENT.md)
- [Pipeline maintenance guide](docs/MAINTENANCE.md)
- [Bug report & responsible disclosure](docs/BUG_REPORT.md)
- [Developer collaboration & reuse evidence](docs/COLLABORATION.md)
- [Final report structure (Springer)](docs/FINAL_REPORT.md)
- [Video demonstration storyboard](docs/VIDEO_STORYBOARD.md)
- [Assignment checklist](docs/CHECKLIST.md)
