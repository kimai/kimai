# Developer Collaboration & Reuse Evidence

Part 3.2 of the project: evidence that the development team reviewed the
automation and agreed to reuse a majority of the pipeline components, plus a
log of security-community participation.

> **Redaction rule:** before submitting any screenshot or transcript, redact
> usernames (where requested), email addresses, tokens, internal hostnames and
> any confidential project data.

## 1. Collaboration activities (log)

| Date | Channel | Activity | Evidence (attach) |
|---|---|---|---|
| 2026-07-23 | GitHub Security Advisories | Filed GHSA-pjrx-mwv9-j9vf privately; discussed ROLE_SUPER_ADMIN delegation with the maintainer; accepted the "documented behavior" outcome | advisory thread (redacted screenshot) |
| 2026-07-26 | GitHub Security Advisories | Filed GHSA-pvc4-crg3-gj44 (missing authorization on project view export) with full PoC and fix suggestion; maintainer triaged | advisory thread (redacted screenshot) |
| 2026-07-30 | Fork PR #1 | Published the fix + regression tests for maintainer review | PR link |
| `<date>` | GitHub Discussions / Discord | `<shared the pipeline approach, asked for feedback on reusing composer scripts / test base classes>` | `<screenshot>` |
| `<date>` | `<channel>` | `<advocated secure defaults: HTTPS enforcement, secure headers, login rate limiting>` | `<screenshot>` |

## 2. Reuse evidence (what the maintainers can adopt unchanged)

The pipeline was designed for **maximum reuse by the development team** -
this is the majority-of-automation argument for the review discussion:

| Component | Reuse story |
|---|---|
| `tests/SecurityTesting/` (32 tests) | Uses the project's own PHPUnit base classes, fixtures and conventions; runs via the existing `composer tests` with zero workflow changes (`--group security` filter is optional) |
| SAST (PHPStan level 9, PhpCsFixer) | Identical invocations to the upstream `linting.yaml` workflow - the team already runs them |
| `composer audit` | Already present in upstream `linting.yaml`; the pipeline only adds reporting |
| MariaDB service + test bootstrap | Mirrors upstream `testing.yaml` (same env vars, same `kimai:reset:test` bootstrap) |
| Docker build | Reuses the repository `Dockerfile` unchanged (`BASE=apache`) |
| Regression tests for GHSA-pvc4-crg3-gj44 | Live inside the existing `ProjectViewControllerTest`, following the file's own patterns |
| Commit policy hook | Wraps the repo's own `composer codestyle` + the Semgrep ruleset |

Components that are pipeline-environment specific (self-hosted runner labels,
report directory, compose stack) are isolated under `devsecops/` so the team
can adopt them à la carte.

## 3. Developer agreement record

> To be completed from the maintainer conversation. Suggested evidence forms:
> a PR review comment approving the approach, a discussion thread agreeing to
> merge/reuse components, or a short signed statement.

- Reviewer: `<maintainer name/handle>`
- Date: `<date>`
- Statement / link: `<quote + permalink, redacted>`
- Components the team agreed to reuse: `<list>`
- Components they asked to change: `<list + how the feedback was addressed>`

## 4. How to collect the evidence

1. Share the fork PR and the `devsecops/` docs link in the project's
   discussion channel (GitHub Discussions or Discord).
2. Ask specifically: "Which of these components would you reuse as-is?" -
   a direct answer is the strongest evidence.
3. Screenshot the thread; redact per the rule above; file the image under
   `devsecops/docs/evidence/` locally (do **not** commit personal data).
4. Summarize the outcome in the table in section 3 and in the final report.
