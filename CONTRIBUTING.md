# Contributing

Kimai is an open source project, contributions made by the community are welcome. 
Send us your ideas, code reviews, pull requests and feature requests to help us improve this project.

## Pull request rules

- We use PSR-2 with some additional code-style checks (see our [php-cs-fixer config](.php_cs.dist)). You can run `bin/console kimai:codestyle` to check and `bin/console kimai:codestyle --fix` to fix violations.
- Add PHPUnit tests for your changes, verify everything still works and execute our test-suites `bin/console kimai:test-unit` and `bin/console kimai:test-integration`.
- If you contribute new files, please add them with the file-header template from below (our chode-style fixer can do that for you).
- With sending in a PR, you accept that your contributions/code will be published under MIT license (see the [LICENSE](LICENSE) file as well).
- If one of the PR checks fails, please fix them before asking us for a review.

Further documentation can be found in the [developer documentation](https://www.kimai.org/documentation/developers.html).

### File-header template 
```
/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
```

