# Security Policy

Please read out [Reporting security issues](https://www.kimai.org/documentation/bughunter.html) documentation. 
It covers everything you need to know.

## Supported versions

As announced in the [README](README.md) security fixes will only be added to the `main` branch.

| Version              | Supported          |
|----------------------|--------------------|
| main branch          | :white_check_mark: |
| older releases       | :x:                |

There are no backports to older releases.
Updating to the current release is the only way to receive a security fix.

## Reporting a vulnerability

**Please do not open a public issue, pull request or discussion for a security problem.**

A public report makes the issue known to everyone while all installations are still
unpatched, so it puts users at risk before they can protect themselves.

Report it privately instead, using one of these:

- [Report a vulnerability](https://github.com/kimai/kimai/security/advisories/new) through GitHub. 
  Only the maintainers can see it, and you can attach a suggested patch.
- Email us, you find the address [here](https://www.kimai.org/documentation/bughunter.html).

You can expect a first reply within a few days. Once a fixed release is available, 
the advisory is published and you are credited, unless you prefer not to be named.

Please check the [latest release](https://github.com/kimai/kimai/releases) before reporting:
the issue you found may already be fixed.

## AI-assisted reports

Using AI tools to find or write up a vulnerability is fine. Submitting their raw output is not.

Every report must be verified, edited and understood by a human before it is submitted.
A report is treated as unreviewed AI output when it shows signs like:

- unfilled template placeholders, internal pipeline notes or file references we cannot see
- claims that were not checked against the actual code or a running Kimai
- severity ratings which ignore the preconditions of the attack
- boilerplate and filler text instead of a focused description
- nobody being able to answer follow-up questions about the report

Such reports are closed without further triage. This is not a judgement about the
underlying finding: the report can be re-opened once it was revised by a human who takes
responsibility for its content and is available for questions.

Maintainer time is the scarcest resource in this project. A report that respects it,
by being short, verified and honest about its impact, gets a faster and friendlier response.

### Reply template for unreviewed reports

Maintainers can use this text when closing such a report:

```
Thank you for your interest in the security of Kimai.

This report was closed, because it shows clear signs of unreviewed AI output. Our [security policy](https://github.com/kimai/kimai/blob/main/SECURITY.md) allows the use of AI tools, but requires that every report is verified and edited by a human before it is submitted. Raw LLM output shifts the verification work to the maintainers, and we cannot afford that.

If you believe the finding is valid: review every claim against the current `main` branch, reproduce it yourself, remove everything you did not verify, rate the severity based on the real preconditions and rewrite the report in your own words. Then ask for this advisory to be re-opened, or submit a new one. Please make sure you are available for follow-up questions.

Reports which were revised this way are welcome and will be credited like any other.
```