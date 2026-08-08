# Security Test Cases & Justification

Every test case below is automated in the pipeline (`security_tests` job) and
mapped to the OWASP Web Security Testing Guide (WSTG) v4.2, Section 4. The
suite lives in `tests/SecurityTesting/` and runs with:

```bash
vendor/bin/phpunit --group security tests/SecurityTesting/
```

The `security_tests` job additionally runs the pre-existing suites that carry the
object-level authorization guarantees these tests depend on:

```bash
vendor/bin/phpunit tests/Security/ tests/Voter/ tests/API/AuthenticationTest.php
vendor/bin/phpunit tests/API/TimesheetControllerTest.php \
                   tests/API/TeamControllerTest.php \
                   tests/API/InvoiceControllerTest.php
```

The second command was added after a review found that the Team and Invoice API
controllers enforce authorization through `#[IsGranted]` attributes and voters
whose controller-level tests were never executed by this pipeline - a dropped
object scope there passed every gate.

## Threat model (summary)

Kimai is a multi-user time-tracking application with a Symfony HTML frontend
and a token-authenticated JSON API. Relevant threats:

| Threat | Assets at risk | WSTG category |
|---|---|---|
| Credential guessing / token theft | user accounts, API tokens | 4.4 Authentication (WSTG-ATHN) |
| Session hijacking via XSS/CSRF | sessions | 4.6 Session Management (WSTG-SESS) |
| SQL injection via API filters & fields | database (timesheets, invoices, users) | 4.7 Input Validation (WSTG-INPV-05) |
| Stored XSS via free-text fields | other users' browsers | 4.7 Input Validation (WSTG-INPV-01) |
| IDOR / horizontal escalation | other users' timesheets & profiles | 4.5 Authorization (WSTG-ATHZ-03/04) |
| Vertical privilege escalation | system administration | 4.5 Authorization (WSTG-ATHZ-02) |
| Information disclosure (stack traces, debug tools, headers) | internals, credentials | 4.8 Error Handling (WSTG-ERRH), 4.2 Configuration (WSTG-CONF) |
| Weakened security configuration | whole application | 4.2 Configuration (WSTG-CONF) |

## Test case justification

### 4.4 Authentication Testing - `AuthenticationSecurityTest`

| Test | WSTG ID | Why it was selected | How it reduces risk |
|---|---|---|---|
| API rejects requests without credentials (6 endpoints) | WSTG-ATHN-01/02 | The API is the largest attack surface; every collection endpoint must enforce authentication | A regression that opens an endpoint anonymously fails the build immediately |
| API rejects invalid bearer token without info leakage | WSTG-ATHN-03 | Guessed tokens must not be distinguishable; error responses must not reveal valid users/tokens | Prevents user enumeration and token validation oracles |
| Disabled account cannot authenticate with a valid token | WSTG-ATHN-06 | Off-boarded employees (`chris_user` fixture) must lose access even if their token leaked | Guarantees deprovisioning actually blocks API access |
| Disabled fixture account exists and is disabled | WSTG-ATHN-06 (guard) | The two tests above answer 401 whether the account is disabled *or* absent; `chris_user` is seeded as a bare literal in `ResetTestCommand` with no constant linking it to `UserFixtures` | Fails loudly on fixture drift instead of letting the deprovisioning tests pass vacuously |

### 4.5 Authorization Testing - `AuthorizationSecurityTest`

| Test | WSTG ID | Why it was selected | How it reduces risk |
|---|---|---|---|
| User cannot read another user's timesheet by ID | WSTG-ATHZ-03 | Timesheets are the core business object; IDs are sequential and guessable (classic IDOR) | Detects broken object-level authorization (BOLA) regressions |
| User cannot modify another user's timesheet by ID | WSTG-ATHZ-03 | Write-IDOR is worse than read-IDOR (data tampering) | Verifies voters deny edit operations on foreign objects |
| User cannot view another user's profile | WSTG-ATHZ-04 | Profiles expose PII (email, rates) | Prevents horizontal PII exposure |
| User cannot create accounts (with `ROLE_SUPER_ADMIN`) | WSTG-ATHZ-02 | Account creation with an injected super-admin role is full instance takeover | Confirms `create_user` is enforced before the form is processed |
| User cannot read an unaffiliated customer by ID | WSTG-ATHZ-03 | Customers are scoped by team membership, not ownership; expected denial pinned by `CustomerVoterTest::testVote` | Detects a dropped team scope on the customer endpoints |
| User cannot read an unaffiliated project by ID | WSTG-ATHZ-03 | Projects are scoped through their own teams *and* their customer's | Detects a dropped team scope on the project endpoints |
| User cannot read an unaffiliated invoice by ID | WSTG-ATHZ-03 | Invoices carry billing totals and customer data; the suite previously had no invoice reference at all | Closes the last uncovered object type - a dropped customer scope on invoices used to pass the whole run |
| Teamlead cannot edit a foreign team | WSTG-ATHZ-02/03 | Team administration is admin-only; `TeamVoter` additionally requires `isTeamleadOf()` for non-admins | Catches a role-config change granting the TEAMS group to ROLE_TEAMLEAD. Measured limit: it does *not* detect removal of the object argument from `#[IsGranted('edit','team')]`, because no shipped role holds `edit_team` without also being admin |
| Restricted endpoints reject lower roles (provider, 9 cases) | WSTG-ATHZ-02 | User administration, master-data creation and team administration are all administrative; each case checked against the permission maps in `config/packages/kimai.yaml` | Guards the vertical boundary for user & teamlead roles across 9 endpoint/role pairs |

### 4.6 Session Management Testing - `SessionSecurityTest`

| Test | WSTG ID | Why it was selected | How it reduces risk |
|---|---|---|---|
| Session cookie carries HttpOnly + SameSite=Lax | WSTG-SESS-02 | Cookie theft via XSS and cross-site sending via CSRF are the main session threats | A config change dropping the flags fails the build |
| No Secure flag over plain HTTP (cookie_secure=auto) | WSTG-SESS-02 | Verifies the `auto` behavior is not accidentally pinned to insecure | Keeps local development functional while production stays secure; the Secure flag itself is enforced by nginx and audited post-deployment (`security-audit.sh`) |

### 4.7 Input Validation Testing - `InputValidationSecurityTest`

| Test | WSTG ID | Why it was selected | How it reduces risk |
|---|---|---|---|
| SQLi payloads in search `term` filters (6 API endpoints) | WSTG-INPV-05 | Search filters historically reach query builders (see upstream GHSA-9cxw-hp3c-637x DQL injection) | Payloads must yield a normal JSON result, never a 500; proves parameter binding |
| SQLi payload stored inertly via POST | WSTG-INPV-05 | `'); DROP TABLE kimai2_timesheet; --` must be stored as plain text | After storage, the timesheet table must still answer and the payload must be readable verbatim |
| Stored XSS payload is escaped in HTML output | WSTG-INPV-01 | Timesheet descriptions are free text rendered to every viewer | Asserts the raw `<script>` never appears unescaped in the HTML page (Twig escaping / Parsedown safe mode) |
| (all POST-based cases resolve activity/project IDs from the API) | - | Literal fixture IDs silently target a different row - or none - if `ResetTestCommand` seeding order changes, which would make the POST 400 and the injection tests pass without storing anything | Keeps the injection tests meaningful under fixture drift |
| XSS payload returned verbatim by the API | WSTG-INPV-01 | JSON is not HTML - escaping belongs to the rendering context | Documents the contract: API stores verbatim, HTML escapes |
| Malformed JSON body is rejected with 400 | WSTG-INPV (generic) | Fuzzing entry point: parsers must fail closed | No 500, no exception disclosure |

### 4.8 Error Handling & 4.2 Configuration Testing - `ErrorHandlingSecurityTest`

| Test | WSTG ID | Why it was selected | How it reduces risk |
|---|---|---|---|
| Unknown API route leaks no internals | WSTG-ERRH-01 / WSTG-CONF-05 | Error pages are the classic source of stack traces and paths | Asserts exact minimal JSON and absence of `Exception`, `.php`, `/var/`, ... |
| 401 responses are minimal and generic | WSTG-ERRH-02 | Identical errors for all auth failures prevent enumeration | Asserts the exact generic payload |
| Profiler/debug routes are not exposed | WSTG-CONF-05 | Symfony profiler exposes env vars, queries, secrets | `/_profiler`, `/_wdt`, `/app_dev.php` must never return 200, and must not 5xx either. Runtime smoke check only - under `APP_ENV=test` these routes are unregistered, so the production guarantee is asserted in `SecurityConfigurationTest` |
| X-Robots-Tag noindex is set | WSTG-CONF | Kimai instances hold sensitive company data | Prevents indexing of instances by search engines |

### 4.2 Configuration Management Testing - `SecurityConfigurationTest`

Regression guards on the Symfony security configuration (unit level, no DB):

| Test | WSTG ID | Why it was selected | How it reduces risk |
|---|---|---|---|
| Login throttling enabled (max 5 attempts) | WSTG-ATHN-03 | Brute-force protection is config-driven and can be silently weakened | Any weakening of `login_throttling` fails the build |
| Login form keeps CSRF protection | WSTG-CONF-01 | Login CSRF enables session fixation style attacks (see upstream GHSA-r8vr-m544-qh4h) | `enable_csrf: true` is asserted |
| `^/api` requires authenticated users | WSTG-ATHZ-01 | A single access-control line protects the whole API | Removal/misconfiguration is caught |
| Session cookie flags hardened | WSTG-SESS-02 | Same as the runtime cookie test, but at config level | Defense in depth |
| Password reset is rate limited | WSTG-ATHN-03 | Reset abuse enables spam/user harassment | Rate limiter presence asserted |
| Deployed environment disables debug | WSTG-CONF-05 | Debug mode leaks everything. The previous test read `$_ENV['APP_DEBUG']`, which `phpunit.xml.dist` sets with `force="true"` - it asserted the value the harness hardcodes and could never fail | Asserts `APP_ENV=prod` and `APP_DEBUG=0` in `devsecops/deploy/docker-compose.yml`, the artefact a regression lands in |
| Profiler is confined to dev/test | WSTG-CONF-05 | The HTTP-level check cannot prove this: under `APP_ENV=test` the profiler routes are never registered, so it would pass with the profiler enabled in production | Asserts the routes exist only under `config/routes/dev/` and that every key in `web_profiler.yaml` sits behind `when@dev`/`when@test` |

## Complementary automated checks

| Layer | Tool | Threats covered |
|---|---|---|
| SAST | Semgrep Kimai ruleset (gating) | command injection, object injection, open redirect, path traversal/LFI, SSRF, DQL concatenation, weak credential hashing, `eval` |
| SAST | PHPStan level 9 (src + tests) | type confusion and null-safety bugs that often become security bugs |
| SCA | composer audit / pnpm audit / Trivy / Dependency-Check | known CVEs in PHP & JS dependencies, leaked secrets in the tree, Dockerfile misconfigurations |
| DAST | OWASP ZAP baseline | missing headers, cookie flags, exposed files, passive vulnerability patterns against the running app |
| Post-deploy | `security-audit.sh` | TLS floor, secure headers, cookie flags, TRACE, sensitive paths, error leakage |
