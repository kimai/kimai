# Upgrading Kimai 2

_Database upgrades are currently ONLY provided for MySQL/MariaDB and SQLite._ 

Upgrading to the latest available version can be achieved with these commands: 

```bash
git fetch --tags
git checkout 0.8
sudo -u www-data composer install --no-dev --optimize-autoloader
sudo -u www-data bin/console cache:clear --env=prod
sudo -u www-data bin/console cache:warmup --env=prod
bin/console doctrine:migrations:migrate
```

There might be more steps required which are version specific and need to be executed after following the above list of commands.
Read and follow each version info below, otherwise you risk data inconsistency or a broken installation!

And make sure to **create a backup before you start**.

## [0.9](https://github.com/kevinpapst/kimai2/releases/tag/0.9)

Follow the normal update and database migration process (see above).

Remember to execute the necessary timezone conversion script, if you haven't updated to 0.8 before (see below)!

**BC BREAKS**
- in an ongoing effort to simplify future installation and upgrade processes the `.env` variable `DATABASE_PREFIX` was removed.
The table prefix is now hardcoded to `kimai2_`. If you used another prefix, you have to rename your tables manually 
before starting the update process. You can also delete the row `DATABASE_PREFIX` from your `.env` file.
- API: DateTime objects will be returned including timezone identifier (previously 2019-03-02 14:23 - now 2019-03-02T14:23:00+00:00)

**Check if you want to apply changes to your `local.yaml`:** 

- New permission is available available: `system_configuration`

## [0.8.1](https://github.com/kevinpapst/kimai2/releases/tag/0.8.1)

A bug fixing release. Remember to execute the necessary timezone conversion script, if you haven't updated to 0.8 before (see below)!

## [0.8](https://github.com/kevinpapst/kimai2/releases/tag/0.8)

After you followed the normal update and database migration process (see above), you need to execute a bash command to convert your timesheet data for timezone support:

- Read this [pull request](https://github.com/kevinpapst/kimai2/pull/372) BEFORE you follow the instructions to convert the 
timezones in your existing time records with `bin/console kimai:convert-timezone`. Without that, you will end up with wrong times in your database.

Check if you want to apply changes to your `local.yaml`: 

- A new boolean setting `kimai.timesheet.rules.allow_future_times` was introduced
- New permissions are available: `view_export, create_export, edit_export_own_timesheet, edit_export_other_timesheet, system_information`

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
