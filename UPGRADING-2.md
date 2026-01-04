# Upgrading Kimai - Version 2.x

_Make sure to create a backup before you start!_ 

Read the [updates documentation](https://www.kimai.org/documentation/updates.html) to find out how 
you can upgrade your Kimai installation to the latest stable release.

Check below if there are more version specific steps required, which need to be executed after the normal update process.
Perform EACH version specific task between your version and the new one, otherwise you risk data inconsistency or a broken installation.

## [2.0.30](https://github.com/kimai/kimai/releases/tag/2.0.30)

The `DATABASE_URL` in your environment settings (e.g. [.env](https://github.com/kimai/kimai/issues/4246), [docker-compose.yaml](https://github.com/tobybatch/kimai2/issues/531) or webserver config)
now requires the `charset` and `serverVersion` params, e.g.: `DATABASE_URL=mysql://user:password@127.0.0.1:3306/database?charset=utf8mb4&serverVersion=10.5.8-MariaDB` (examples in `.env`).

## [2.0](https://github.com/kimai/kimai/releases/tag/2.0)

**!! This release requires minimum PHP version to 8.1 !!**

### Breaking changes

- All plugins need to be updated: delete all previous version from your installation (`rm -r var/plugins/*`) before updating!
- The `local.yaml` is not compatible with old version, remove it before the update and then re-create it after everything works
  - removed: configuring the `dashboard` is not supported any longer
  - removed: custom translation files via `theme.branding.translation`
  - removed: changing the plugin directory via `kimai.plugin_dir`

### Developer

Developer read the full documentation at [https://www.kimai.org/documentation/migration-v2.html](https://www.kimai.org/documentation/migration-v2.html).

- Invoice renderer and templates for XML, JSON and TEXT were moved to the [Extended invoicing plugin](https://www.kimai.org/store/invoice-bundle.html) (install if you use one of those)
- Moved `company.docx` to [external repo](https://github.com/kimai/invoice-templates/tree/main/docx-company) (needs to be re-uploaded if you want to keep on using it!)
- Role names are forced to be uppercase 
- Removed unused `public/avatars/` directory 
- Time-tracking mode `duration_only` was removed, existing installations will be switched to `duration_fixed_begin`
- Removed Twig filters. You might have to replace them in your custom export/invoice templates:
  - `date_full` => `date_time`
  - `duration_decimal` => `duration(true)`
  - `currency` => `currency_name`
  - `country` => `country_name`
  - `language` => `language_name`
- Removed support for custom translation files (use [TranslationBundle](https://www.kimai.org/store/translation-bundle.html) instead or write your own plugin)
- Removed all 3rd party mailer packages, you need to install them manually (ONLY if you used a short syntax to configure the `MAILER_URL` in `.env`): 
  - `composer require symfony/amazon-mailer`
  - `composer require symfony/google-mailer`
  - `composer require symfony/mailchimp-mailer`
  - `composer require symfony/mailgun-mailer`
  - `composer require symfony/postmark-mailer`
  - `composer require symfony/sendgrid-mailer`
