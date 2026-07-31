# DevSecOps Pipeline Design

## 1. Overview

The pipeline (`.github/workflows/devsecops.yml`) automates security testing for
Kimai on every push to `main`, every pull request, weekly (cron) and on demand.
It runs **on-premise** on a self-hosted GitHub Actions runner
(`runs-on: [self-hosted, linux, kimai-devsecops]`) - no workload leaves the
local network except registry lookups and the GitHub control plane.

```
 pull_request / push(main) / schedule / manual
                |
                v
  +-----------------------------+     +------------------------------+
  | sast                        |     | sca                          |
  |  - PhpCsFixer (code style)  |     |  - composer audit (PHP)      |
  |  - PHPStan level 9 (src)    |     |  - pnpm audit (JS)           |
  |  - PHPStan level 9 (tests)  |     |  - Trivy fs (vuln/secret/    |
  |  - Semgrep Kimai ruleset    |     |    misconfig)                |
  |    (GATING)                 |     |  - OWASP Dependency-Check    |
  |  - Semgrep p/php +          |     |    (informational)           |
  |    p/security-audit (audit) |     +------------------------------+
  |  - SonarQube (optional)     |                  |
  +-----------------------------+                  |
                |                                  |
                v                                  v
  +-----------------------------+                  |
  | security_tests              |                  |
  |  - PHPUnit --group security |                  |
  |    (OWASP WSTG suite)       |                  |
  |  - tests/Security, Voter,   |                  |
  |    API authentication       |                  |
  +-----------------------------+                  |
                |                                  |
                v                                  v
  +-----------------------------+                  |
  | deploy                      |                  |
  |  - docker build (hardened)  |                  |
  |  - compose up (TLS nginx,   |                  |
  |    internal db network)     |                  |
  |  - Trivy image scan         |                  |
  |  - smoke test               |                  |
  |  - configuration audit      |                  |
  |  - OWASP ZAP baseline       |                  |
  +-----------------------------+                  |
                |                                  |
                v                                  v
  +-----------------------------------------------------------+
  | notify (on failure / on semgrep findings)                 |
  |  - GitHub Issue (create or comment, de-duplicated)        |
  |  - optional Slack/Discord webhook                         |
  +-----------------------------------------------------------+
```

## 2. Technology selection and justification

| Requirement | Tool | Justification |
|---|---|---|
| CI/CD platform | GitHub Actions, self-hosted runner | The project already lives on GitHub; a self-hosted runner satisfies the on-premise requirement while reusing the existing workflow ecosystem |
| Static analysis | PHPStan level 9, PhpCsFixer | Already the project's quality gate (`composer phpstan`, `composer codestyle`) - reusing them keeps the pipeline aligned with maintainer standards |
| Static analysis (security) | Semgrep | Custom taint-tracking ruleset (`devsecops/semgrep/rules/`) tailored to Symfony/Doctrine patterns; community packs `p/php` and `p/security-audit` add broad coverage |
| Static analysis (optional) | SonarQube (on-premise server) | Security hotspot review, quality gates, trend tracking; only enabled when `SONAR_HOST_URL` is configured |
| Open source scanners | `composer audit`, `pnpm audit` | Native lockfile auditing for both ecosystems, fast and precise |
| Open source scanners | Trivy | Filesystem scan (vulnerabilities, secrets, misconfigurations) and image scan of the built container in one tool |
| Open source scanners | OWASP Dependency-Check | Assignment requirement; kept informational for PHP because its Composer analyzer is experimental |
| Security test cases | PHPUnit (`tests/SecurityTesting/`) | Kimai's own test stack; the WSTG suite runs against a real MariaDB like the upstream CI |
| DAST | OWASP ZAP baseline | Passive scan of the deployed instance; warnings do not fail the build (`-I`), FAIL alerts do |
| Notification | GitHub Issues (via `gh` CLI) + optional webhook | Issues are the project's native channel; de-duplication prevents spam; webhook covers chat-based teams |

All GitHub actions are pinned by SHA (matching the repository's existing
convention and its zizmor workflow-scan). Scanner container images are
parameterized at the top of the workflow so they can be pinned by digest in
production (see [MAINTENANCE.md](MAINTENANCE.md)).

## 3. Security test cases

See [TEST_CASES.md](TEST_CASES.md) for the full OWASP WSTG v4.2 mapping,
threat-model rationale and risk-reduction notes. The suite covers:

- application functionality (session cookies, error handling, configuration)
- API endpoints (authentication, IDOR, privilege escalation, injection)
- threat scenarios from the labs (SQLi via search filters, stored XSS,
  IDOR on timesheets, privilege escalation via user management, debug exposure)

## 4. Developer notification

The `notify` job runs when any stage fails or when the audit-mode Semgrep scan
produced findings:

1. It builds a summary (failed stages, run link, commit, report location).
2. It looks for an open issue labeled `devsecops` - if one exists it comments
   instead of creating a duplicate (notification hygiene).
3. Otherwise it creates a new issue labeled `devsecops` + `security`.
4. If `SECURITY_NOTIFY_WEBHOOK_URL` is configured, the same summary is posted
   to the chat webhook (Slack/Discord compatible).

## 5. Optional enhancements

- **Commit policy enforcement / secure coding checklist**:
  `devsecops/git-hooks/pre-commit` blocks commits of secret files, private keys
  and credential-like diff content, and runs PhpCsFixer plus the gating Semgrep
  ruleset on staged PHP files. Activate with
  `git config core.hooksPath devsecops/git-hooks`.
- **Code review automation**: the pipeline results (checkstyle/SARIF-friendly
  JSON reports) can be surfaced in PR reviews; the zizmor workflow already
  scans all workflow changes including this pipeline.
- **Workflow security**: `zizmor` scans this workflow file itself on change.

## 6. Configuration reference

| Secret / variable | Required | Purpose |
|---|---|---|
| `GITHUB_TOKEN` (automatic) | yes | Create/comment notification issues (job permission `issues: write`) |
| `SONAR_HOST_URL` (variable) | no | Enables the SonarQube step |
| `SONAR_TOKEN` (secret) | with SonarQube | SonarQube authentication |
| `NVD_API_KEY` (secret) | no | Faster NVD downloads for Dependency-Check |
| `SECURITY_NOTIFY_WEBHOOK_URL` (secret) | no | Slack/Discord notifications |

Runner host prerequisites are automated by `devsecops/runner/setup-runner.sh`.
