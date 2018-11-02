# Kimai v2 - Time Tracking

Kimai v2 - the open source time-tracking application with a mobile-first approach, read more at the [official website](http://v2.kimai.org).

[![Latest Stable Version](https://poser.pugx.org/kevinpapst/kimai2/v/stable)](https://packagist.org/packages/kevinpapst/kimai2)
[![License](https://poser.pugx.org/kevinpapst/kimai2/license)](https://packagist.org/packages/kevinpapst/kimai2)
[![Travis Status](https://travis-ci.org/kevinpapst/kimai2.svg?branch=master)](https://travis-ci.org/kevinpapst/kimai2)
[![Code Quality](https://scrutinizer-ci.com/g/kevinpapst/kimai2/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/kevinpapst/kimai2/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/kevinpapst/kimai2/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/kevinpapst/kimai2/?branch=master)
[![Scrutinizer Status](https://scrutinizer-ci.com/g/kevinpapst/kimai2/badges/build.png?b=master)](https://scrutinizer-ci.com/g/kevinpapst/kimai2/build-status/master)

## Introduction

This is the reloaded version of the open source timetracker Kimai.
The new version has not much in common with its predecessor [Kimai v1](http://www.kimai.org) besides the basic ideas of time-tracking and the current development team.

Right now its in an early development phase, its usable but some advanced features from Kimai v1 are missing by now (like export and ODT invoices). 
But we already support to [import your timesheets](var/docs/migration_v1.md) from Kimai v1.

It is developed with modern frameworks like [Symfony v4](https://github.com/symfony/symfony), [Doctrine](https://github.com/doctrine/),
[AdminLTE](https://github.com/kevinpapst/AdminLTEBundle/) and [many](composer.json) [more](package.json).

Kimai is a multi-language application and already translated to: English, German, Italian, French, Spanish, Russian, Arabic and Hungarian.
If you want to support us in translating Kimai, please [read this documentation](var/docs/translations.md). 

### Requirements

- PHP 7.1.3 or higher
- The PHP extensions:
  - [PDO](https://php.net/manual/en/book.pdo.php) (with either [pdo_sqlite](https://php.net/manual/en/ref.pdo-sqlite.php) or [pdo_mysql](https://php.net/manual/en/ref.pdo-mysql.php) enabled)
  - [intl](https://php.net/manual/en/book.intl.php)
  - [zip](https://php.net/manual/en/book.zip.php)
- The [usual Symfony application requirements](http://symfony.com/doc/current/reference/requirements.html)
- If you use MariaDB, make sure its at least v10.2.7 (see [FAQ](var/docs/faq.md))
- Kimai needs to be installed in the root directory of a domain or you need to [recompile the frontend assets](var/docs/developers.md)
- A modern browser, Kimai v2 might be broken on old browsers like IE 10

## Documentation & Roadmap

Looking for more information about using Kimai? Check out our more detailed [documentation](var/docs/).

You can see our development roadmap for the future in the [Milestones](milestones/) sections.

Our roadmap is open for changes and input from the community, please [sent us](issues/) your ideas and questions.

## Installation

> **NOTE**
>
> There are [further infos about installation](var/docs/installation.md) if you have to use FTP, want to develop with Kimai, or are setting Kimai up on a personal computer (vs a server). 

If you want to install Kimai 2 in your production environment, then SSH into your server and change to your webserver root.
You need to install Git and [Composer](https://getcomposer.org/doc/00-intro.md) if you haven't already. 

First clone this repo:

```bash
git clone https://github.com/kevinpapst/kimai2.git
cd kimai2/
```

Make sure the [file permissions are correct](https://symfony.com/doc/current/setup/file_permissions.html) and create your `.env` file:
```bash
chown -R :www-data .
chmod -R g+r .
chmod -R g+rw var/
cp .env.dist .env
```

It's up to you which database server you want to use, Kimai v2 supports MySQL/MariaDB and SQLite.
Configure the database connection string in your the `.env` file:
```
# adjust all settings in .env to your needs
APP_ENV=prod
DATABASE_URL=mysql://user:password@127.0.0.1:3306/database
```

Now install all dependencies for Kimai 2:

```bash
sudo -u www-data composer install --no-dev --optimize-autoloader
```

Optionally create the database:
```bash
bin/console doctrine:database:create
```

Create all schema tables:
```bash
bin/console doctrine:schema:create
```

Make sure that upcoming updates can be correctly applied by setting the initial database version:
```bash
bin/console doctrine:migrations:version --add --all
```

Warm up the cache (as webserver user):
```bash
sudo -u www-data bin/console cache:warmup --env=prod
```

Create your first user with the following command. You will be asked to enter a password afterwards:
```bash
bin/console kimai:create-user username admin@example.com ROLE_SUPER_ADMIN
```
_Tip: You can skip the "create user" step, if you are going to [import data from Kimai v1](var/docs/migration_v1.md)._

For available roles, please refer to the [user documentation](var/docs/users.md).

> **NOTE**
>
> If you want to use a fully-featured web server (like Nginx or Apache) to run
> Kimai, configure it to point at the `public/` directory of the project.
> For more details, see:
> http://symfony.com/doc/current/cookbook/configuration/web_server_configuration.html

Installation complete: enjoy time-tracking :-)

## Updating Kimai

> **NOTE**
> 
> Don't forget tweaks that may be necessary to these instructions if you are using FTP, developing, or updating on a personal computer instead of a server. See [further infos about installation](var/docs/installation.md).

**STOP** 

1. It's important that you don't execute the Installation steps before or after your upgrade
2. Make sure that you have a working database backup before you start the update
3. Read the [UPGRADING](UPGRADING.md) guide and [release information](https://github.com/kevinpapst/kimai2/releases) to check if there a further steps required

Get the latest code and install dependencies:
```bash
cd kimai2/
git pull origin master
sudo -u www-data composer install --no-dev --optimize-autoloader
```

Refresh your cache:
```bash
sudo -u www-data bin/console cache:clear --env=prod
sudo -u www-data bin/console cache:warmup --env=prod
```

And upgrade your database:
```bash
bin/console doctrine:migrations:migrate
```

Done! You can use the latest version of Kimai 2. 

## Rolling releases & Git

Please note: Kimai 2 uses a rolling release concept for delivering updates.
Release versions will be created on a regular base and you can use these tags if you are familiar with Git, but we 
will not provide support for any specific version (whether its bugs or installation/update docu).

Every code change, whether it's a new features or bug fixes, will be targeted against the master branch and 
intensively tested before merging. We have to go this way, as we develop Kimai in our free time and want to put our 
effort into the software instead of installation scripts and complicated upgrade processes. 

## Extensions for Kimai 2

As Kimai 2 was built on top of Symfony, it can be extended like every other Symfony application.
We call these extensions bundles, but you might also know them as add-ons, extensions or plugins.

All available Kimai 2 bundles can be found at the [Kimai recipes](https://github.com/kimai/recipes) repository.

## Developer

If you want to develop with and for Kimai 2 please read the following documentation:

- an example on how to extend Kimai 2 can be found in this [GitHub repository](https://github.com/kevinpapst/kimai2-invoice)
- the [developer documentation](var/docs/developers.md) is available both on GitHub and your local installation
