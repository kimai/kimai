# Kimai v2 - Time Tracking

Kimai v2 - the reloaded open source Time-Tracking application.

[![Build Status](https://travis-ci.org/kevinpapst/kimai2.svg?branch=master)](https://travis-ci.org/kevinpapst/kimai2)

## Introduction

This is (or will be in the future, currently a lot of features are still missing) the reloaded version of the open source time-tracking application [Kimai](http://www.kimai.org).

It is based on the following PHP components:
- [Symfony Framework 3.4](https://github.com/symfony/symfony)
- [AdminThemeBundle](https://github.com/avanzu/AdminThemeBundle/) (based on [AdminLTE](https://github.com/almasaeed2010/AdminLTE/))
- [Doctrine](https://github.com/doctrine/)

## Requirements

- PHP 7 or higher;
- One PHP extension of PDO-SQLite and/or PDO-MySQL enabled;
- and the [usual Symfony application requirements](http://symfony.com/doc/current/reference/requirements.html)

If unsure about meeting these requirements, download the demo application and
browse the `http://localhost:8000/config.php` script to get more detailed
information.

## Installation

First, install Git and [Composer](https://getcomposer.org/doc/00-intro.md)
if you haven't already. Then, clone this repo and execute this command in the cloned directory:

```bash
$ git clone https://github.com/kevinpapst/kimai2.git
$ cd kimai2/
$ composer install
```

Lets prepare the environment 
```bash
$ php bin/console assets:install --symlink --relative
$ php bin/console avanzu:admin:initialize --symlink --relative
```

This was the basic task of the installation. If you have not yet setup a database, you 
can create it and import example data by executing these commands: 
```bash
$ cd kimai2/
$ php bin/console doctrine:database:create
$ php bin/console doctrine:schema:create
```

### Creating your first user

As there is currently no full-fledged installer, you need to create the first user via command-line:

```bash
$ php bin/console kimai:create-user admin admin@example.com password en ROLE_SUPER_ADMIN
```

For available roles, please refer to [the user documentation](app/Resources/docs/users.md).


### Demo data

You can also import demo data, to test the application in its full beauty and with several different user accounts and permission sets.

```bash
$ php bin/console doctrine:fixtures:load
```

If you have imported the example data, you can login with these accounts:

- Username: *clara_customer* / Password: *kitten* / Role: Customer
- Username: *john_user* / Password: *kitten* / Role: User
- Username: *tony_teamlead* / Password: *kitten* / Role: Teamlead
- Username: *anna_admin* / Password: *kitten* / Role: Administrator
- Username: *susan_super* / Password: *kitten* / Role: Super-Administrator

Demo data can always be deleted by dropping the schema and re-creating it (ATTENTION: this will erase all your data!):

```bash
$ php bin/console doctrine:schema:drop --force
$ php bin/console doctrine:schema:create
```

## Usage

There is no need to configure a virtual host in your web server to access the application.
Just use the built-in web server for your first tests:

```bash
$ php bin/console server:run
```

This command will start a web server for Kimai. Now you can
access the application in your browser at <http://127.0.0.1:8000/en> and <http://127.0.0.1:8000/de>. 
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

