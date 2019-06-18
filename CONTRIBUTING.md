# Contributing

Kimai is an open source project, contributions made by the community are welcome. 
Send your ideas, code reviews, pull requests and feature requests to help improving this project.

## Pull request rules

- Use the [pre-configured codesniffer](.php_cs.dist)) to check for `composer kimai:codestyle` and fix `composer kimai:codestyle-fix` violations
- Add PHPUnit tests for your changes!
- Verify everything still works with `composer kimai:tests-unit` and `composer kimai:tests-integration`
- If you contribute new files, add them with the file-header template from below (the code-style fixer can do that for you)
- When sending in a PR, you must accept that your contributions/code will be published under MIT license (see the [LICENSE](LICENSE) file as well), otherwise your PR will be closed
- If one of the PR checks/builds fails, fix it before asking for a review

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

