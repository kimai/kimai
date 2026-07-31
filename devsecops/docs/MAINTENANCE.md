# Pipeline Maintenance Guide

How to keep the DevSecOps pipeline healthy: adding tests, updating tools,
interpreting reports and staying compatible with future Kimai versions.

## 1. Adding a new security test case

1. Pick the OWASP WSTG v4.2 category and ID the test covers
   (see [TEST_CASES.md](TEST_CASES.md)).
2. Add a method to the matching class in `tests/SecurityTesting/` (or create a
   new class with `#[Group('integration')]` + `#[Group('security')]`).
   Follow the existing patterns:
   - API tests extend `App\Tests\API\APIControllerBaseTestCase`
   - web tests extend `App\Tests\Controller\AbstractControllerBaseTestCase`
   - create the HTTP client **before** importing fixtures (the kernel can only
     be booted once per test)
   - never call `createClient()` twice without `self::ensureKernelShutdown()`
3. Run it locally:

   ```bash
   DATABASE_URL="mysql://kimai2_test:kimai2_test@127.0.0.1:3306/kimai2_test?charset=utf8mb4&serverVersion=10.11.0-MariaDB" \
       vendor/bin/phpunit tests/SecurityTesting/
   ```
4. Validate style and static analysis before pushing:

   ```bash
   ./php-cs-fixer.sh core
   ./phpstan.sh test
   ```
5. Register the test in [TEST_CASES.md](TEST_CASES.md) with its WSTG ID,
   selection rationale and risk-reduction note.

The `security_tests` pipeline job picks the test up automatically
(`--group security`).

## 2. Adding or tuning Semgrep rules

- Gating rules live in `devsecops/semgrep/rules/kimai-taint.yml`. A finding
  there fails the build, so rules must stay near-zero false positives.
- Test a rule against the whole codebase before enabling it:

  ```bash
  semgrep scan --config devsecops/semgrep/rules/ --metrics=off src/
  ```
- Suppress a verified false positive inline, with justification:

  ```php
  // nosemgrep: devsecops.semgrep.rules.<rule-id> - <why this is safe>
  ```
- Prefer refining the rule over suppressing. Re-run the scan afterwards.

## 3. Updating tools and pinned versions

| What | Where | How |
|---|---|---|
| GitHub actions | `.github/workflows/devsecops.yml` | Update the pinned SHA **and** the version comment; verify against the action's release page; let the `zizmor` workflow scan the change |
| Scanner images | env block at the top of the workflow | For production, replace the floating tags (`latest`, `stable`) with digests (`image@sha256:...`) after pulling and testing locally |
| MariaDB service image | `security_tests` job | Keep in sync with `phpunit.xml.dist` (`serverVersion` hint) and production |
| SonarQube properties | `devsecops/sonar/sonar-project.properties` | Adjust exclusions when top-level directories change |
| Dependency-Check suppressions | `devsecops/dependency-check/suppressions.xml` | Only after triage, always with justification and an expiry date (`until="YYYY-MM-DD"`) |

After any tool update, run the pipeline once manually (`workflow_dispatch`)
and review every report before trusting the results.

## 4. Interpreting and acting on reports

Reports land in `/opt/kimai/security-reports/<run-id>/` on the runner host:

| Report | Produced by | Act on |
|---|---|---|
| `composer-audit.txt`, `pnpm-audit.txt` | SCA job | Any advisory: upgrade the package or document why it does not apply |
| `trivy-fs.json` | SCA job | HIGH/CRITICAL findings gate the build: fix, upgrade or suppress with reason |
| `semgrep-kimai.json` | SAST job | Any finding gates the build: fix the code or suppress with justification |
| `semgrep-community.json` | SAST job | Audit-mode: review weekly; promote true positives to the gating ruleset |
| `dependency-check-report.html/json` | SCA job | Informational for PHP; triage and add suppressions with expiry |
| `trivy-image.json` | deploy job | Base-image CVEs: rebuild on the updated base or accept with reason |
| `security-audit.txt` | deploy job | Any FAIL line means hardening regressed: fix nginx/compose config |
| `zap-baseline.html/json` | deploy job | FAIL alerts gate the build; review WARN alerts monthly |

Notification issues labeled `devsecops` are de-duplicated - close them only
after the underlying finding is fixed or formally accepted.

## 5. Compatibility with future Kimai versions

- The security suite only uses public behavior (HTTP status codes, JSON
  shapes, cookie flags) and the project's own test base classes, so it should
  survive refactors. If upstream changes a status code or message, adjust the
  assertion - and verify the change was intentional.
- After pulling upstream changes, re-run the full local validation:

  ```bash
  rm -r ./var/cache/test/   # force a fresh test cache when tests fail oddly
  composer tests-unit
  vendor/bin/phpunit --group security tests/SecurityTesting/
  ```
- The deployment reuses the repository `Dockerfile`; upstream changes to the
  image build flow automatically into the pipeline. If the apache port
  (currently 8001) or env variables change, update
  `devsecops/deploy/docker-compose.yml` and `nginx/default.conf` accordingly.
- Keep this documentation in sync - the checklist in
  [CHECKLIST.md](CHECKLIST.md) tracks the state of every deliverable.
