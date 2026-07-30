# Bug Report & Responsible Disclosure

Part 3 of the project. The bug report is an **individual** submission - every
student documents their own finding, impact analysis, reproduction steps and
disclosure process.

## 1. Confirmed finding: GHSA-pvc4-crg3-gj44

> Missing authorization on the project view reporting export allows any
> authenticated user to download project overview data.

| Field | Value |
|---|---|
| Reporter | devzephyr (student) |
| Status | Triaged by the kimai/kimai security team (private advisory) |
| Severity | Low (CWE-200, CWE-862) |
| Affected | `kimai/kimai <= 2.61.0` |
| Disclosed | 2026-07-26 via GitHub private security advisory |
| Fix | Pull request on the fork: move the `#[IsGranted]` attributes from `__invoke()` to class level in `ProjectViewController`, plus regression tests |

### Summary

`ProjectViewController::export` (route `report_project_view_export`) carried no
authorization attribute while the sibling `__invoke` carried
`#[IsGranted('report:project')]` and
`#[IsGranted(new Expression("is_granted('budget_any', 'project')"))]`.
Every other reporting controller places its guards at class level - this one
was the only method-level exception, so the export route inherited nothing.
Any authenticated user (stock `ROLE_USER` with `view_reporting` only) could
download the project overview XLSX even though the report page itself returned
403.

### Reproduction (stock permissions, no configuration change)

```
GET /en/reporting/project_view         -> 403
GET /en/reporting/project_view/export  -> 200  (XLSX download)   <-- bug
```

The export contains one row per project across all customers (customer name,
project name, currency, budget type, aggregates). Financial amounts stay
protected by template-level checks.

### Root cause

Guard placement: attributes on `__invoke()` do not apply to other methods of
the same controller class. The export method shares the same private
`getData()` as the report, so it returned the identical dataset.

### Fix

Move both attributes to class level (matching every other reporting
controller) and add regression tests that fail without the fix
(`testExportIsSecure`, `testReportIsSecureForUserRole`,
`testExportIsSecureForUserRole`). The fix was verified by temporarily
reverting it and watching the regression test fail.

### Disclosure process followed

1. Verified on a local instance with stock permissions.
2. Reported **privately** through GitHub Security Advisories (no public issue,
   no public PoC), per Kimai's [bughunter policy](https://www.kimai.org/documentation/bughunter.html).
3. Waited for maintainer triage before any public reference.
4. Prepared the fix + regression tests on the fork for the maintainers to review.

## 2. Second finding (rejected as designed): GHSA-pjrx-mwv9-j9vf

> Administrators with delegated role management can grant ROLE_SUPER_ADMIN.

The maintainer (kevinpapst) reviewed the advisory and closed it as **documented
behavior**: the `roles_other_profile` / `roles_own_profile` permissions are
labeled SECURITY ALERT in the official documentation and are intentionally
super-admin-only by default. Lesson learned: verify the documented threat
model of the project before filing; a "bug" that matches the documented design
is not actionable. The HTTP 500 side-effect mentioned in the report could not
be reproduced on the current codebase (the API call returns 200 with a clean
JSON payload), so no follow-up was filed.

## 3. Bug report template (for future findings)

```markdown
## Summary
<one paragraph: what is vulnerable, who can exploit it, what is the impact>

## Affected versions / environment
<version, configuration, preconditions>

## Steps to reproduce
1. <exact request/action>
2. <expected vs actual>

## Impact
<CIA impact, business context, exploit preconditions>

## Root cause
<file/line, code pattern>

## Suggested fix
<minimal change, defense in depth alternatives>

## Disclosure timeline
- found: <date>
- reported privately: <date, channel>
- maintainer response: <date, outcome>
- fix proposed/merged: <date, PR>
- public disclosure: <date, advisory id>
```

## 4. Rules of engagement

- Always report privately first (GitHub Security Advisory or the channel in
  `SECURITY.md`); never file public issues for unpatched vulnerabilities.
- Test only against your own instance.
- Redact credentials, user data and internal hostnames from every artifact.
- Do not run destructive payloads against shared or production data.
