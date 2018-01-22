# Kimai v2 - Time Tracking

Kimai v2 - the reloaded open source Time-Tracking application.

[![Build Status](https://travis-ci.org/kevinpapst/kimai2.svg?branch=master)](https://travis-ci.org/kevinpapst/kimai2)

## Introduction

This is (or will be in the future, currently a lot of features are still missing) the reloaded version of the open source time-tracking application [Kimai](http://www.kimai.org).

It is based on a lot of great PHP components. Special thanks to:
- [Symfony Framework 4](https://github.com/symfony/symfony)
- [Doctrine](https://github.com/doctrine/)
- [AdminThemeBundle](https://github.com/avanzu/AdminThemeBundle/) (based on [AdminLTE](https://github.com/almasaeed2010/AdminLTE/))

## Requirements

- PHP 7.1 or higher
- One PHP extension of PDO-SQLite and/or PDO-MySQL enabled (it might work with PostgreSQL and Oracle as well, but that wasn't tested and is not officially supported)
- and the [usual Symfony application requirements](http://symfony.com/doc/current/reference/requirements.html)

If unsure about meeting these requirements, download the demo application and
browse to the <http://localhost:8000/config.php> script to get more detailed information.

## Installation

First, install Git and [Composer](https://getcomposer.org/doc/00-intro.md)
if you haven't already. Then, clone this repo and execute this command in the cloned directory:

```bash
$ git clone https://github.com/kevinpapst/kimai2.git
$ cd kimai2/
```

Lets prepare the environment by installing all dependencies. You will be asked for your application parameter,
like the database connection afterwards (if you don't have a [app/config/parameters.yml](blob/master/app/config/parameters.yml.dist) yet):

```bash
$ composer install
```

The next command will create the database, the schema and install all web assets:
```bash
$ bin/console kimai:install --relative
```

### Installation (development / demo)

Lets boostrap your environment by executing this commands (which is only available in dev environment): 
```bash
$ bin/console kimai:reset-dev
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
$ bin/console doctrine:schema:drop --force
$ bin/console doctrine:schema:create
```

The `kimai:reset-dev` command can always be executed later on to reset your dev database and cache.

### Installation (live)

The default installation uses a SQLite database, which is not recommended for production usage.
You can configure your database through your environment (e.g. Webserver, Cloud-Provider) or in your `.env file:
```bash
$ cp .env.dist .env
```

You can adjust the following ENV values to your needs:
```
DATABASE_PREFIX=kimai2_
DATABASE_URL=sqlite:///%kernel.project_dir%/var/data/kimai.sqlite
APP_ENV=dev
APP_SECRET=some_random_secret_string_for_your_installation
```

Now create the database schemas and warm up the cache:
```bash
$ bin/console doctrine:schema:create
$ bin/console cache:warmup --env=prod
```

Finally create your first user:

```bash
$ bin/console kimai:create-user username password admin@example.com ROLE_SUPER_ADMIN
```

For available roles, please refer to [the user documentation](var/docs/users.md).

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

The full command for import:
```bash
$ bin/console kimai:import-v1 "mysql://user:password@127.0.0.1:3306/database?charset=utf8" "db_prefix" "password" "country"
```

It is recommended to test the import in a fresh database without any required data! Then you can test your import as often as you like and fix possible problems in your installation.
A sample command could look like that:
```bash
$ bin/console doctrine:schema:drop --force && bin/console doctrine:schema:create && bin/console kimai:import-v1 "mysql://kimai:test@127.0.0.1:3306/kimai?charset=latin1" "kimai_" "test123" "de"
```
That will drop the configured Kimai v2 database schema and re-create it, before importing the data from the `mysql` database at `127.0.0.1` authenticating the user `kimai` with the password `test` for import.
The connection will use the charset `latin1` and the default table prefix `kimai_` for reading data. Imported users can login with the password `test123` and all customer will have the country `de` assigned.
  

## Usage

There is no need to configure a virtual host in your web server to access the application for testing.
Just use the built-in web server for your first tests:

```bash
$ bin/console server:run
```

This command will start a web server for Kimai. Now you can access the application in your browser at <http://127.0.0.1:8000/>. 
You can stop the built-in web server by pressing `Ctrl + C` while you're in the terminal.

> **NOTE**
>
> If you want to use a fully-featured web server (like Nginx or Apache) to run
> Kimai, configure it to point at the `web/` directory of the project.
> For more details, see:
> http://symfony.com/doc/current/cookbook/configuration/web_server_configuration.html

## Troubleshooting

Cannot see any assets (like images) and/or missing styles? Try executing:
```bash
$ php bin/console assets:install --symlink
```

