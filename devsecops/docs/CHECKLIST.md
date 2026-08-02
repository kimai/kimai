# Assignment Checklist

Legend: **Done** = automated/artifact complete in this repository ·
**Incomplete** = started, needs a human step · **Not Done** = not started

## Part 1: DevSecOps Pipeline Design

| Task | Status | Artifact |
|---|---|---|
| Technology selection (CI/CD, on-premise) | Done | GitHub Actions on a self-hosted runner (`devsecops.yml`, `runner/setup-runner.sh`) |
| Security test cases (functionality, API, lab threat scenarios) | Done | `tests/SecurityTesting/` (32 tests), TEST_CASES.md |
| Static code analysis (SonarQube, Semgrep, ...) | Done | Semgrep gating + audit rulesets, PHPStan, PhpCsFixer, optional SonarQube |
| Open source security scanners | Done | composer audit, pnpm audit, Trivy, OWASP Dependency-Check |
| Developer notification | Done | `notify` job: GitHub Issues (de-duplicated) + optional Slack/Discord webhook |
| Optional: code review automation | Done | zizmor scans the workflow; checkstyle/JSON reports for PR review |
| Optional: commit policy enforcement | Done | `devsecops/git-hooks/pre-commit` |
| Optional: secure coding checklist enforcement | Done | same hook + MAINTENANCE.md checklist steps |
| Test case justification (written) | Done | TEST_CASES.md (WSTG IDs, threat model, risk reduction) |

## Part 2: Automated Secure Deployment

| Task | Status | Artifact |
|---|---|---|
| Deployment automation (OS/runtime, containers, config mgmt, network controls) | Done | `deploy/deploy.sh`, `docker-compose.yml`, migrations, segmented networks |
| Disable debug mode | Done | `APP_ENV=prod`, `APP_DEBUG=0` + audit check |
| Enforce HTTPS | Done | nginx TLS 1.2/1.3, HTTP->HTTPS redirect + audit check |
| Secure headers | Done | HSTS, XCTO, XFO, Referrer-Policy, Permissions-Policy, CSP-Report-Only |
| Restrict sensitive endpoints | Done | nginx deny rules + rate-limited login + internal db network |
| Post-deployment: configuration audit | Done | `validation/security-audit.sh` - 22/22 PASS live |
| Post-deployment: vulnerability scan | Done | Trivy image scan + ZAP baseline (green CI run 30768622416+) |
| Post-deployment: availability/integrity | Done | `validation/smoke-test.sh` - 4/4 PASS live |

## Part 3: Collaboration & Bug Reporting

| Task | Status | Artifact |
|---|---|---|
| Valid security bug found & reported | Done | GHSA-pvc4-crg3-gj44 (triaged) |
| Reproducible steps documented | Done | BUG_REPORT.md §1 |
| Responsible disclosure | Done | private advisory per bughunter policy |
| Maintainer approval of the bug | Incomplete | advisory triaged; fix PR open for maintainer review (fork PR #1) |
| Developer reviewed the automation | Incomplete | COLLABORATION.md §3 (record from maintainer conversation) |
| Agreement to reuse majority | Incomplete | COLLABORATION.md §2-§3 |
| Individual bug report per student | Incomplete | template ready (BUG_REPORT.md §3) - each student writes their own |

## Part 4: Maintenance Documentation

| Task | Status | Artifact |
|---|---|---|
| Add new test cases | Done | MAINTENANCE.md §1 |
| Update tools/configs | Done | MAINTENANCE.md §2-§3 |
| Interpret and act on reports | Done | MAINTENANCE.md §4 |
| Future compatibility | Done | MAINTENANCE.md §5 |

## Part 5: Final Report & Demonstration

| Task | Status | Artifact |
|---|---|---|
| Final report (PDF, Springer, AODA) | Incomplete | `devsecops/deliverables/DevSecOps_Final_Report.docx` generated (full content, 12 screenshot placeholders) - fill captures, export tagged PDF |
| Video 10-15 min (pipeline, tests, detection, notification, deployment, validation) | Incomplete | VIDEO_STORYBOARD.md + speaker notes in the pptx - record individually |
| Slides | Done | `devsecops/deliverables/DevSecOps_Slides.pptx` (9 slides with demo speaker notes) |
| Submissions (report, pipeline configs, feedback evidence, bug report, maintenance guide, video) | Incomplete | configs + guides in repo; PDFs/video to produce |

## Lab assessment (in-person OWASP WSTG demonstration)

| Task | Status | Artifact |
|---|---|---|
| Hands-on WSTG demonstration in person | Incomplete | prepare from TEST_CASES.md + live environment (`deploy.sh`) |
