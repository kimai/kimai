# Contributing

The Kimai application is an open source project. Contributions made by the community are welcome. 

Send us your ideas, code reviews, pull requests and feature requests to help us improve this project.

## Pull request rules

- We use PSR-2 code styles, please run `bin/console kimai:phpcs` before sending in a pull-request
- Please add PHPUnit tests for your changes 
- Verify everything still works by executing our tests `bin/console kimai:test-unit` and `bin/console kimai:test-integration`
- If you want to contribute new files, please add them with the file-header template from below
- With sending in a PR, you accept that your contributions/code will be published under MIT license (see the LICENSE file as well)

### File-header template 
```
/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
```
