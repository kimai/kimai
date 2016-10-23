# Kimai v2 - Time Tracking

Kimai v2 - the reloaded open source Time-Tracking application.

[![Build Status](https://travis-ci.org/kevinpapst/kimai2.svg?branch=master)](https://travis-ci.org/kevinpapst/kimai2)

## Introduction

This is the reloaded version of the open source time-tracking application [Kimai](http://www.kimai.org).

It is based on the following components:
 - Symfony Framework 2.x
 - AdminLTE Control Panel Template (through the AdminThemeBundle)

## Requirements

  * PHP 5.5.9 or higher;
  * One PHP extension of PDO-SQLite and/or PDO-MySQL enabled;
  * and the [usual Symfony application requirements](http://symfony.com/doc/current/reference/requirements.html).
  * bower (through npm)

If unsure about meeting these requirements, download the demo application and
browse the `http://localhost:8000/config.php` script to get more detailed
information.

## Installation

[![Deploy](https://www.herokucdn.com/deploy/button.png)](https://heroku.com/deploy)

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
$ php bin/console doctrine:fixtures:load
```
If you have imported the example data, you can login with two accounts:

- Username: *clara_customer* / Password: *kitten* / Role: Customer
- Username: *john_user* / Password: *kitten* / Role: User
- Username: *tony_teamlead* / Password: *kitten* / Role: Teamlead
- Username: *anna_admin* / Password: *kitten* / Role: Administrator

## Usage

There is no need to configure a virtual host in your web server to access the application.
Just use the built-in web server:

```bash
$ php bin/console server:run
```

This command will start a web server for Kimai. Now you can
access the application in your browser at <http://localhost:8000>. You can
stop the built-in web server by pressing `Ctrl + C` while you're in the
terminal.

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