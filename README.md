# Kimai v2 - Time Tracking

Kimai v2 - the open source time-tracking application with a mobile-first approach.

[![Travis Status](https://travis-ci.org/kevinpapst/kimai2.svg?branch=master)](https://travis-ci.org/kevinpapst/kimai2)
[![Code Quality](https://scrutinizer-ci.com/g/kevinpapst/kimai2/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/kevinpapst/kimai2/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/kevinpapst/kimai2/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/kevinpapst/kimai2/?branch=master)
[![Scrutinizer Status](https://scrutinizer-ci.com/g/kevinpapst/kimai2/badges/build.png?b=master)](https://scrutinizer-ci.com/g/kevinpapst/kimai2/build-status/master)

## Introduction

This is the reloaded version of the open source timetracker [Kimai](http://www.kimai.org).
Right now its in an early development phase, its usable but some advanced features from Kimai v1 are missing by now.

Kimai is based on a lot of great frameworks. Special thanks to: 
- [Symfony v4](https://github.com/symfony/symfony) 
- [Doctrine](https://github.com/doctrine/)
- [AdminThemeBundle](https://github.com/avanzu/AdminThemeBundle/) (based on [AdminLTE](https://github.com/almasaeed2010/AdminLTE/))

## Requirements

- PHP 7.1 or higher
- One PHP extension of PDO-SQLite or PDO-MySQL enabled (it might work with PostgreSQL and Oracle as well, but that wasn't tested and is not officially supported)
- the [usual Symfony application requirements](http://symfony.com/doc/current/reference/requirements.html)
- Kimai needs to be installed in the root directory of a domain or you need to [recompile the frontend assets](var/docs/developers.md)

## Installation

First, install Git and [Composer](https://getcomposer.org/doc/00-intro.md)
if you haven't already. Then clone this repo and execute this command in the cloned directory:

```bash
git clone https://github.com/kevinpapst/kimai2.git
cd kimai2/
```

Lets prepare the environment by installing all dependencies:

```bash
composer install
```

The next steps depend in which environment you want to use Kimai, you can choose between development or production mode.

### Installation (development)

The default installation uses a SQLite database, so there is no need to create a database for your first tests.
Our default settings will work out-of-the-box, but you might want to adjust the `.env` values to your needs.
You can configure your database through your environment (e.g. Webserver, Cloud-Provider) or in your `.env` file:
```
DATABASE_PREFIX=kimai2_
DATABASE_URL=sqlite:///%kernel.project_dir%/var/data/kimai.sqlite
APP_ENV=dev
APP_SECRET=some_random_secret_string_for_your_installation
```

The next command will create the database and the schema:
```bash
bin/console doctrine:database:create
bin/console doctrine:schema:create
```

Lets bootstrap your environment by executing this commands (which is only available in dev environment):
```bash
bin/console kimai:reset-dev
```

You just imported demo data, to test the application in its full beauty and with several different user accounts and permission sets.

You can now login with these accounts:

| Username | Password | Role |
|---|:---:|---|
| clara_customer | kitten | Customer |
| john_user | kitten | User |
| chris_user | kitten | User (deactivated) |
| tony_teamlead | kitten | Teamlead |
| anna_admin | kitten | Administrator |
| susan_super | kitten | Super-Administrator |

Demo data can always be deleted by dropping the schema and re-creating it.
ATTENTION - this will erase all your data:

```bash
bin/console doctrine:schema:drop --force
bin/console doctrine:schema:create
```

The `kimai:reset-dev` command can always be executed later on to reset your dev database and cache.

There is no need to configure a virtual host in your web server to access the application for testing.
Just use the built-in web server for your first tests:

```bash
bin/console server:run
```

This command will start a web server for Kimai. Now you can access the application in your browser at <http://127.0.0.1:8000/>.
You can stop the built-in web server by pressing `Ctrl + C` while you're in the terminal.

To re-generate the frontend assets ([more information here](var/docs/developers.md)), execute:
```bash
yarn install
npm run prod
```

### Installation (production)

Make sure the [directories are read and writable by your webserver](https://symfony.com/doc/current/setup/file_permissions.html):
```bash
chown -R www-data var/
chmod -R 777 var/
```

The database to use is up to you, but we would not recommend using the default SQLite database for production usage.
Please create your database and configure the connection string in your environment, e.g. with the `.env` file:
```
APP_ENV=prod
DATABASE_URL=mysql://db_user:db_password@127.0.0.1:3306/db_name
APP_SECRET=insert_a_random_secret_string_for_production
```

Create the database schemas and warm up the cache (as webserver user):
```bash
bin/console doctrine:schema:create
sudo -u www-data bin/console cache:warmup --env=prod
```

Create your first user with the following command. You will be asked to enter a password afterwards.

```bash
bin/console kimai:create-user username admin@example.com ROLE_SUPER_ADMIN
```
_Tip: You can skip the "create user" step, if you are going to import data from Kimai v1._

For available roles, please refer to the [user documentation](var/docs/users.md).

> **NOTE**
>
> If you want to use a fully-featured web server (like Nginx or Apache) to run
> Kimai, configure it to point at the `public/` directory of the project.
> For more details, see:
> http://symfony.com/doc/current/cookbook/configuration/web_server_configuration.html

That's it, you can start time-tracking :-)

### Importing data from Kimai v1

Before importing your data from a Kimai v1 installation, please read the following carefully:

- Data from the existing v1 installation is only read and will never be changed
- Data can only be imported from a Kimai installation with at least `v1.0.1` and database revision `1388` (check your `configuration` table)
- Kimai v1 has support for activities without project assignment, which Kimai v2 doesn't support. Unattached activities will be created for every project that has a linked activity in any of the imported timesheet records
- Rates and fixed-rates are handled in a completely different way and for now only the timesheet record total amounts are imported
- Customers cannot login and no user accounts will be created for them
- The customers country has to be manually assigned afterwards, as there is no field in Kimai v1 for that
- You have to supply the default password that is used for every imported user, as their password will be resetted
- Data that was deleted in Kimai v1 (user, customer, projects, activities) will be imported and set to `invisible` (if you don't want that, you have to delete all entries that have the value `1` in the `trash` column before importing)

A possible full command for import:
```bash
bin/console kimai:import-v1 "mysql://user:password@127.0.0.1:3306/database?charset=utf8" "db_prefix" "password" "country"
```

It is recommended to test the import in a fresh database. You can test your import as often as you like and fix possible problems in your installation.
A sample command could look like that:
```bash
bin/console doctrine:schema:drop --force && bin/console doctrine:schema:create && bin/console kimai:import-v1 "mysql://kimai:test@127.0.0.1:3306/kimai?charset=latin1" "kimai_" "test123" "de"
```
That will drop the configured Kimai v2 database schema and re-create it, before importing the data from the `mysql` database at `127.0.0.1` on port `3306` authenticating the user `kimai` with the password `test` for import.
The connection will use the charset `latin1` and the default table prefix `kimai_` for reading data. Imported users can login with the password `test123` and all customer will have the country `de` assigned.

## Extensions for Kimai 2

As Kimai 2 was built on top of Symfony, it can be extended like every other Symfony application.
We call these extensions bundles, but you might also know them as add-ons, extensions or plugins.

All available Kimai 2 bundles can be found at the [Kimai recipes](https://github.com/kimai/recipes) repository.

## Developer

If you want to develop for Kimai 2 please read the following documentation:

- an example on how to extend Kimai 2 can be found in this [GitHub repository](https://github.com/kevinpapst/kimai2-invoice)
- the [developer documentation](var/docs/developers.md) is available both on GitHub and your local installation
