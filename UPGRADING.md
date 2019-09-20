# Upgrading Kimai 2

_Make sure to create a backup before you start!_ 

Read the [updates documentation](https://www.kimai.org/documentation/updates.html) to find out how 
you can upgrade your Kimai installation to the latest stable release.

Check below if there are more version specific steps required, which need to be executed after the normal update process.
Perform EACH version specific task between your version and the new one, otherwise you risk data inconsistency or a broken installation.

## [1.4](https://github.com/kevinpapst/kimai2/releases/tag/1.4)

[Update as usual](https://www.kimai.org/documentation/updates.html), nothing special for this release if you upgrade from 1.0 / 1.0.1.

## [1.3](https://github.com/kevinpapst/kimai2/releases/tag/1.3)

### Possible BC breaks

- Refactored toolbars and search, plugins needs to be checked 
- Added `manage_tag` permission for new tag features
- Invoices now supports multiple repositories, some method signatures had to be changed (eg. `calculateSumIdentifier()`)  

## [1.2](https://github.com/kevinpapst/kimai2/releases/tag/1.2)

### Possible BC breaks

- Deleted timezone conversion command. If you are still using 0.7 or below, you need to upgrade to 1.1 before upgrading to this version.
- Minimum password length raised from 5 to 8 character (applies only for password changes and new users)
- Maximum customer name length lowered to 150 character
- Maximum project name length lowered to 150 character
- Maximum activity name length lowered to 150 character
- Added new permission: `manage_invoice_template`
  - Removed permissions: `view_invoice_template`, `create_invoice_template`, `edit_invoice_template`, `delete_invoice_template`
- Removed permission: `create_export` (using `create_export` only)
- Custom export renderer need to check for usage of `Timesheet::getEnd()` as running entries can now be exported as well

## [1.1](https://github.com/kevinpapst/kimai2/releases/tag/1.1)

[Update as usual](https://www.kimai.org/documentation/updates.html), nothing special for this release if you upgrade from 1.0 / 1.0.1.

## [1.0.1](https://github.com/kevinpapst/kimai2/releases/tag/1.0.1)

[Update as usual](https://www.kimai.org/documentation/updates.html), nothing special for this release if you upgrade from 1.0.

## [1.0](https://github.com/kevinpapst/kimai2/releases/tag/1.0)

This release contains several changes, as I still have the goal to stabilize the code base to prevent 
such "challenges" after 1.0 for a while.

### Changes for your local.yaml
 
New permissions are available. You have to add them to your `local.yaml` ONLY if you use a custom permission structure, 
otherwise you can't use the new features: 
- `view_tag` - view all tags
- `delete_tag` - delete tags
- `edit_exported_timesheet` - allows to edit records which were exported
- `role_permissions` - view calculated permissions for user roles
- `budget_activity` - view and edit budgets for activities
- `budget_project` - view and edit budgets for projects
- `budget_customer` - view and edit budgets for customers

Removed permission:
- `system_actions` - removed experimental feature to flush app cache from the about screen

### BC BREAKS

- API: Format for queries including a datetime object fixed to use HTML5 format (previously `2019-03-02 14:23` - now `2019-03-02T14:23:00`)
- **Permission config**: the `permissions` definition in your `local.yaml` needs to be verified/changed, as the internal structure was highly optimized to simplify the definition. 
Thanks to the new structure, you should be able to remove almost everything from your `local.yaml` (tip: start over from scratch!). Please read [the updated permission docu](https://www.kimai.org/documentation/permissions.html). 
- default widgets were removed from `kimai.yaml`, that shouldn't cause any issues ... but if something is odd: [look here for help](https://www.kimai.org/documentation/dashboard.html)

## [0.9](https://github.com/kevinpapst/kimai2/releases/tag/0.9)

Remember to execute the necessary timezone conversion script, if you haven't updated to 0.8 before (see below)!

### BC BREAKS

This release contains some BC breaks which were necessary before 1.0 will be released (_now or never_), to prevent those BC breaks after 1.0. 

- **Kimai requires PHP 7.2 now => [PHP 7.1 expired 4 month ago](https://www.php.net/supported-versions.php)**
- The `.env` variable `DATABASE_PREFIX` was removed and the table prefix is now hardcoded to `kimai2_`. If you used another prefix, 
you have to rename your tables manually before starting the update process. You can delete the row `DATABASE_PREFIX` from your `.env` file.
- API: Format for DateTime objects changed, now including timezone identifier (previously `2019-03-02 14:23` - now `2019-03-02T14:23:00+00:00`), see [#718](https://github.com/kevinpapst/kimai2/pull/718)
- API: changed from snake_case to camelCase (affected fields: hourlyRate vs hourly_rate / fixedRate vs fixed_rate / orderNumber vs order_number / i18n config object)
- Plugin mechanism changed: existing Plugins have to be deleted or updated

### Apply necessary changes to your `local.yaml`: 

New permissions are available: 
- `system_configuration` - for accessing the new system configuration screen
- `system_actions` - for the experimental feature to flush your cache from the about screen
- `plugins` - for accessing the new plugins screen

The setting `kimai.timesheet.mode` replaces the setting `kimai.timesheet.duration_only`. If you used the duration_only mode, you need to change your config:
```yaml
# Before
kimai:
    timesheet:
        duration_only: true
        
# After
kimai:
    timesheet:
        mode: duration_only
```
Or switch the mode directly in the new System configuration screen within Kimai.  

## [0.8.1](https://github.com/kevinpapst/kimai2/releases/tag/0.8.1)

A bug fixing release. Remember to execute the necessary timezone conversion script, if you haven't updated to 0.8 before (see below)!

## [0.8](https://github.com/kevinpapst/kimai2/releases/tag/0.8)

After you followed the normal update and database migration process (see above), you need to execute a bash command to convert your timesheet data for timezone support:

- Read this [pull request](https://github.com/kevinpapst/kimai2/pull/372) BEFORE you follow the instructions to convert the 
timezones in your existing time records with `bin/console kimai:convert-timezone`. Without that, you will end up with wrong times in your database.

### Apply necessary changes to your `local.yaml`: 

- A new boolean setting `kimai.timesheet.rules.allow_future_times` was introduced
- New permissions are available: 
  - `view_export` - for the new export feature
  - `create_export` - for the new export feature
  - `edit_export_own_timesheet` - for the new export feature
  - `edit_export_other_timesheet` - for the new export feature
  - `system_information` - to see the new about screen

## [0.7](https://github.com/kevinpapst/kimai2/releases/tag/0.7)

The configuration `kimai.theme.active_warning` was deprecated and should be replaced in your local.yaml, 
[read config docs for more information](https://www.kimai.org/documentation/timesheet.html#limit-active-entries).

## [0.6.1](https://github.com/kevinpapst/kimai2/releases/tag/0.6.1)

A bugfix release to address database compatibility issues with older MySQL/MariaDB versions.

## [0.6](https://github.com/kevinpapst/kimai2/releases/tag/0.6)

The API has some minor BC breaks: some fields were renamed and entities have a larger attribute set than collections. 
Be aware that the API is still is development mode and shouldn't be considered stable for now.

## [0.5](https://github.com/kevinpapst/kimai2/releases/tag/0.5)

Some configuration nodes were removed, if you have one of them in your `local.yaml` you need to delete them before you start the update:
- `kimai.invoice.calculator`
- `kimai.invoice.renderer`
- `kimai.invoice.number_generator`

The new config `kimai.invoice.documents` was introduced, holding a list of directories ([read more](https://www.kimai.org/documentation/invoices.html)).

**BC break:** InvoiceTemplate name was changed from 255 characters to 60. If you used longer invoice-template names, they will be truncated when upgrading the database.
Please make sure that they are unique in the first 60 character before you upgrade your database with `doctrine:migrations:migrate`. 

## [0.4](https://github.com/kevinpapst/kimai2/releases/tag/0.4)

In the time between 0.3 and 0.4 there was a release of composer that introduced a BC break, 
which leads to problems between Composer and Symfony Flex, resulting in an error like this when running it:

```
  [ErrorException]
  Declaration of Symfony\Flex\ParallelDownloader::getRemoteContents($originUrl, $fileUrl, $context) should be compatible with Composer\Util\RemoteFilesystem::getRemoteContents($originUrl, $fileUrl, $context, ?array &$responseHeaders = NULL)
```

This can be fixed by updating Composer and Flex before executing the Kimai update:
```
sudo composer self-update
sudo -u www-data composer update symfony/flex --no-plugins --no-scripts
```

## [0.3](https://github.com/kevinpapst/kimai2/releases/tag/0.3)

You need to adjust your `.env` file and add your `from` address for [all emails](https://www.kimai.org/documentation/emails.html) generated by Kimai 2:
```
MAILER_FROM=kimai@example.com
```

Create a file and database backup before executing the following steps: 

```bash
git pull origin master
sudo -u www-data composer install --no-dev --optimize-autoloader
sudo -u www-data bin/console cache:clear --env=prod
sudo -u www-data bin/console cache:warmup --env=prod
bin/console doctrine:migrations:version --add 20180701120000
bin/console doctrine:migrations:migrate
```
