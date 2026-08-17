# Security Policy


Please read out [Reporting security issues](https://www.kimai.org/documentation/bughunter.html) documentation. 
It hopefully covers everything you need to know.

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

## Supported versions

As announced in the [README](README.md) security fixes will only be added to the `main` branch.

| Version              | Supported          |
|----------------------|--------------------|
| main branch          | :white_check_mark: |
| older releases       | :x:                |

There are no backports to older releases. 
Updating to the current release is the only way to receive a security fix.
