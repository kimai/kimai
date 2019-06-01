# Kimai 2 - Time-tracking made easy

Kimai - the open source time-tracker application with a mobile-first approach (read more at the [official website](https://www.kimai.org)).

[![Latest Stable Version](https://poser.pugx.org/kevinpapst/kimai2/v/stable)](https://packagist.org/packages/kevinpapst/kimai2)
[![License](https://poser.pugx.org/kevinpapst/kimai2/license)](https://packagist.org/packages/kevinpapst/kimai2)
[![Travis Status](https://travis-ci.org/kevinpapst/kimai2.svg?branch=master)](https://travis-ci.org/kevinpapst/kimai2)
[![Code Coverage](https://codecov.io/gh/kevinpapst/kimai2/branch/master/graph/badge.svg)](https://codecov.io/gh/kevinpapst/kimai2)
[![Gitter](https://badges.gitter.im/kimai2/support.svg)](https://gitter.im/kimai2/support)

## Introduction

This is the new version of your favorite open source timetracker Kimai, which was built from scratch and doesn't share any source code with its [predecessor](http://www.kimai.org). 

It adapts the same ideas about time-tracking and comes with a lot of interesting features like:
JSON API, invoicing, exports, multi-timer, punch-in punch-out modes, tagging, multi-user and multi-timezones, LDAP and built-in authentication,  
customizable role permissions, mobile-ready / responsive, hourly and fixed rates, advanced filtering, plugins and many more.

Kimai is a [multi-language application](https://www.kimai.org/documentation/translations.html) and already translated to english, german, italian, french, spanish, russian, arabic, hungarian, portuguese (brazilian), swedish and japanese.

It is in a stable development phase, usable in production and with most advanced features from Kimai 1. 
You can even [import your data](https://www.kimai.org/documentation/migration-v1.html) and start testing and using it today.

### Requirements

- PHP 7.2 or higher (test your system compatibility with the [requirements-checker](http://symfony.com/doc/current/reference/requirements.html))
- The PHP extensions [xml](http://php.net/manual/en/book.xml.php), [mbstring](http://php.net/manual/en/book.mbstring.php), [gd](http://php.net/manual/en/book.image.php), [intl](https://php.net/manual/en/book.intl.php), [zip](https://php.net/manual/en/book.zip.php) and [PDO](https://php.net/manual/en/book.pdo.php) with either [pdo_sqlite](https://php.net/manual/en/ref.pdo-sqlite.php) or [pdo_mysql](https://php.net/manual/en/ref.pdo-mysql.php) enabled
- If you use MariaDB, make sure its at least v10.2.7 (see [FAQ](https://www.kimai.org/documentation/faq.html))
- A modern browser, Kimai v2 might be broken on old browsers like IE 10

## Documentation

Looking for more information about Kimai 2? Check out our detailed [documentation](https://www.kimai.org/documentation/).

### Installation

There are multiple ways to install Kimai, all of them described in the [installation docu](https://www.kimai.org/documentation/installation.html):

- [Recommended installation with GIT and Composer](https://www.kimai.org/documentation/installation.html#recommended-setup)
- [Development setup](https://www.kimai.org/documentation/installation.html#development-installation) 
- [Docker](https://www.kimai.org/documentation/docker.html)
- [1-click installations](https://www.kimai.org/documentation/installation.html#hosting-and-1-click-installations) 
- [FTP](https://www.kimai.org/documentation/installation.html#ftp-installation)

### Updating Kimai

Read the following documentations before you start your upgrade:

- The [update documentation](https://www.kimai.org/documentation/updates.html)
- The version specific [UPGRADING guide](UPGRADING.md)
- The [release information](https://github.com/kevinpapst/kimai2/releases)

## Roadmap and releases

You can see a rough development roadmap in the [Milestones](https://github.com/kevinpapst/kimai2/milestones) sections.
It is open for changes and input from the community, your [ideas and questions](https://github.com/kevinpapst/kimai2/issues) are welcome!

> Kimai 2 uses a rolling release concept for delivering updates.
> You can upgrade Kimai at any time, you don't need to wait for the next official release. Read the [upgrade docs](UPGRADING.md) first!

Release versions will be created on a regular base (approx. 1 per month) and you can use these tags if you are familiar with Git, 
but we cannot provide support for any specific version (especially older ones).
Every code change, whether it's a new feature or a bug fix, will be done on the master branch. 
I have to do it this way, as I develop Kimai in my free time and want to put most
effort into the software instead of backporting changes for old versions. 

## Plugins for Kimai 2

Kimai 2 was built on top of the Symfony framework and can be extended in various ways:

- Users looking for existing plugins go to our [plugin marketplace](https://www.kimai.org/store/) 
- Developer start with the [developer documentation](https://www.kimai.org/documentation/developers.html)

## Credits

Kimai 2 is developed with modern frameworks like 
[Symfony v4](https://github.com/symfony/symfony), 
[Doctrine](https://github.com/doctrine/),
[AdminLTEBundle](https://github.com/kevinpapst/AdminLTEBundle/) (based on [AdminLTE theme](https://github.com/almasaeed2010/AdminLTE)) and 
[many](composer.json) [more](package.json).
