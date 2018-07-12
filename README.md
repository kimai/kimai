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
But we already support to [import your timesheets](migration_v1.md) from Kimai v1.

It is developed with modern frameworks like [Symfony v4](https://github.com/symfony/symfony), [Doctrine](https://github.com/doctrine/),
[AdminLTE](https://github.com/kevinpapst/AdminLTEBundle/) and [many](composer.json) [more](package.json)...

### Requirements

- PHP 7.1.3 or higher
- One PHP extension of [PDO-SQLite](https://php.net/manual/en/ref.pdo-sqlite.php) or [PDO-MySQL](https://php.net/manual/en/ref.pdo-mysql.php) enabled (it might work with PostgreSQL and Oracle as well, but that wasn't tested and is not officially supported)
- The PHP extension [intl](https://php.net/manual/en/book.intl.php)
- The [usual Symfony application requirements](http://symfony.com/doc/current/reference/requirements.html)
- If you use MariaDB, make sure its at least v10.7.2 (see [FAQ](var/docs/faq.md))
- Kimai needs to be installed in the root directory of a domain or you need to [recompile the frontend assets](var/docs/developers.md)
- A modern browser, Kimai v2 might be broken on old browsers like IE 9

## Documentation & Roadmap

Looking for more information about using Kimai? Check out our more detailed [documentation](var/docs/).

You can see our development roadmap for the future in the [Milestones](milestones/) sections,
current work is organized in the [Project](projects/) planning boards.
Our roadmap is open for changes and input from the community, please [sent us](issues/) your ideas and questions.

## Installation

There are [further infos about installation](var/docs/installation.md) if you have to use FTP or want to develop with Kimai. 

If you want to install Kimai v2 in your production environment, then SSH into your server and change to your webserevr root.
You need to install Git and [Composer](https://getcomposer.org/doc/00-intro.md) if you haven't already. 

First clone this repo:

```bash
git clone https://github.com/kevinpapst/kimai2.git
cd kimai2/
```

Make sure the [file permissions are correct](https://symfony.com/doc/current/setup/file_permissions.html):
```bash
chown -R www-data var/
chmod -R 777 var/
```

It's up to you which database server you want to use, Kimai v2 supports MySQL/MariaDB and SQLite.
Create your database and configure the connection string in your environment, e.g. with the `.env` file (more examples in `.env.dist`):
```
APP_ENV=prod
APP_SECRET=insert_a_random_secret_string_for_production
DATABASE_URL=mysql://db_user:db_password@127.0.0.1:3306/db_name
```

After activating `prod` environment you can prepare the environment by installing all dependencies:

```bash
composer install --no-dev
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
_Tip: You can skip the "create user" step, if you are going to [import data from Kimai v1](migration_v1.md)._

For available roles, please refer to the [user documentation](var/docs/users.md).

> **NOTE**
>
> If you want to use a fully-featured web server (like Nginx or Apache) to run
> Kimai, configure it to point at the `public/` directory of the project.
> For more details, see:
> http://symfony.com/doc/current/cookbook/configuration/web_server_configuration.html

That's it, you can start time-tracking :-)

## Extensions for Kimai 2

As Kimai 2 was built on top of Symfony, it can be extended like every other Symfony application.
We call these extensions bundles, but you might also know them as add-ons, extensions or plugins.

All available Kimai 2 bundles can be found at the [Kimai recipes](https://github.com/kimai/recipes) repository.

## Developer

If you want to develop for Kimai 2 please read the following documentation:

- an example on how to extend Kimai 2 can be found in this [GitHub repository](https://github.com/kevinpapst/kimai2-invoice)
- the [developer documentation](var/docs/developers.md) is available both on GitHub and your local installation
