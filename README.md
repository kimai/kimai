# Kimai - Time Tracking Application

Kimai v2 - the reloaded open source Time-Tracking application, based on Symfony and powered by PHP.

[![Build Status](https://travis-ci.org/kevinpapst/kimai2.svg?branch=master)](https://travis-ci.org/kevinpapst/kimai2)

Requirements
------------

  * PHP 5.5.9 or higher;
  * One PHP extension of PDO-SQLite and/or PDO-MySQL enabled;
  * and the [usual Symfony application requirements](http://symfony.com/doc/current/reference/requirements.html).

If unsure about meeting these requirements, download the demo application and
browse the `http://localhost:8000/config.php` script to get more detailed
information.

Installation
------------

[![Deploy](https://www.herokucdn.com/deploy/button.png)](https://heroku.com/deploy)

First, install Git and [Composer](https://getcomposer.org/doc/00-intro.md)
if you haven't already. Then, clone this repo and execute this command in the cloned directory:

```bash
$ git clone https://github.com/kevinpapst/kimai2.git
$ cd kimai2/
$ composer install
```

Usage
-----

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
