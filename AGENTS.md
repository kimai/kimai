# Kimai Core Agent Guide

Use this file when working in the Kimai core repository.

## Stack

- Kimai is a professional open source time-tracking application
- PHP versions: 8.2, 8.3, 8.4, 8.5
- Main framework: Symfony 6.4
- Core libraries: Doctrine, Twig
- API libraries: FOSRestBundle, NelmioApiDocBundle
- Frontend: Bootstrap with Tabler.io
- Frontend build: Webpack Encore via `symfony/webpack-encore`
- Package managers: Composer and Yarn
- Tests: PHPUnit
- Code styles: PhpCsFixer
- Static analysis: PHPStan
- Project information in README.md
- Translations managed with Weblate online service

## Scope

- This guide applies to Kimai core only
- Work in `var/plugins/` is out of scope unless explicitly requested
- Each subdirectory in `var/plugins/` is a separate Kimai plugin and its own Git repository
- In fresh installations, `var/data/` and `var/plugins/` are empty

## Repository Map

- `.docker/` Docker image build files
- `.github/` GitHub Actions and repository metadata
- `assets/` JavaScript and Sass sources
- `bin/` executable entry points, especially `bin/console`
- `config/` Symfony configuration and bundle setup
- `migrations/` Doctrine migrations for installs and upgrades
- `public/` web root with `index.php`
- `public/build/` generated frontend assets
- `public/bundles/` generated public bundle assets
- `src/` core PHP source code
- `src/API/` the JSON API
- `templates/` Twig templates
- `tests/` PHPUnit tests
- `translations/` XLIFF files named `<component>.<locale>.xlf`
- `var/` runtime storage and generated content
- `vendor/` Composer dependencies

## Never Touch

- Do not read from or write to `var/cache/`, it is Symfony-managed internal state
- Do not modify `vendor/`
- Do not modify `var/data/`
- Do not modify `var/log/`
- Do not modify `public/build/`, generated frontend assets
- Do not modify `public/bundles/`, frintend assets from plugins
- Do not modify plugins in `var/plugins/` unless explicitly asked

## Agent Workflow

- Read the surrounding code before editing
- Follow existing local patterns before introducing new abstractions
- Keep changes small and targeted
- Keep code, identifiers, comments, branches, commit text, and documentation in English
- Ask before touching security-sensitive areas such as authentication, authorization, or permissions

## Architecture Rules

- Do not introduce new composer packages without prior discussion
- Prefer services over static helper classes
- Keep business logic out of controllers
- Use Twig templates for HTML output
- Preserve backward compatibility for upgrades

## Database Rules

- Doctrine entity changes affecting the schema require a migration file
- Generate migration with `bin/console doctrine:migrations:diff`
- Prefer `Doctrine\DBAL\Schema` in migrations over inline SQL

## Frontend and Translation Rules

- Build on existing Bootstrap and Tabler patterns
- Do not introduce new frontend frameworks without prior discussion
- Keep English translations updated whenever translations change
- English is the Weblate default language and Kimai fallback language
- Use Twig `|trans` for user-facing text instead of hardcoded strings
- Use FontAwesome 6 names for icons

## Testing Rules

- Every PHP class in `src/`, except interfaces, should have a matching PHPUnit test
- Map `src/<directory>/<ClassName>.php` to `tests/<directory>/<ClassName>Test.php`
- Cover all public methods with tests
- Follow the existing test style in the target area such as controller, event, voter, or service tests

## Validation

- Always run `./php-cs-fixer.sh core`
- Run `./phpstan.sh core` for changes in `src/`
- Run `./phpstan.sh test` for changes in `tests/`
- For focused checks, run `vendor/bin/phpunit tests/<directory>/<TestClassName>.php`
- Use `composer tests-unit` for broader validation without expensive end-to-end coverage
- Use `composer tests` when the change justifies running the full suite
- If tests fail, remove stale cache with `rm -r ./var/cache/test/` to cause a rebuild 

## Git Rules

- Avoid working directly on `main`
- Small fixes should target the active `release-x.y.z` branch
- Larger changes should go to descriptive `snake_case` feature branches
- Agents may create branches when needed
- Agents must not create commits unless explicitly asked
- Commits are normally created by the maintainer

## Coding Conventions

- Use strict comparisons such as `===` and `!==`
- Prefer constructor promotion for dependency injection
- Use PHP attributes for routing, mapping, and configuration where established
- Use `camelCase` for variables and methods
- Use 4-space indentation
- Use single quotes for strings in PHP, JavaScript, and CSS unless the local code style requires otherwise
- Use modern HTML5, Twig, and ES6+ syntax

## Security Focus

- Prevent XSS
- Prevent CSRF issues
- Prevent SQL or command injection patterns
- Prevent auth bypasses
- Prevent open redirects
- Apply Rate-Limiting in authentication flows
