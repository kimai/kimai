# Contributing

The Kimai application is an open source project. Contributions made by the community are welcome. 

Send us your ideas, code reviews, pull requests and feature requests to help us improve this project.

## Pull request rules

- We use PSR-2 with some addons code styles (check our [php-cs-fixer config](.php_cs.dist)), run `bin/console kimai:phpcs` to verify and `bin/console kimai:phpcs --fix` to fix violations
- Add PHPUnit tests for your changes, verify everything still works and execute our test-suites `bin/console kimai:test-unit` and `bin/console kimai:test-integration`
- If you contribute new files, please add them with the file-header template from below
- With sending in a PR, you accept that your contributions/code will be published under MIT license (see the LICENSE file as well)
- If one of the checks fail, please fix them before asking for a review

### File-header template 
```
/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
```

## Translations

We try to keep the number of language files small to make it easier to identify the location for your new messages.

- If you add a new key, you have to add it in every language file
- Its very likely that you want to edit the file `messages` as it holds 90% of our application translations 

The files in a quick overview:

- `AvanzuAdminTheme` is only meant for translating strings from the original theme
- `exceptions` only holds translations of error pages and exception handlers
- `flashmessages`
- `messages` holds most of the visible application translations
- `pagerfanta` includes the translations for the pagination component
- `sidebar` holds all the translations of the right sidebar
- `validators` only hold translations related to violations/validation of submitted form data (or API calls)

## Documentation

The documentation is in [var/docs/](var/docs/) and its available both at GitHub and in your running Kimai instance.

- Please verify that all links work in your Kimai instance before submitting

