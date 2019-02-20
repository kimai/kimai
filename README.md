# Kimai 2 - Time-tracking made easy

Kimai - the open source time-tracker application with a mobile-first approach (read more at the [official website](http://v2.kimai.org)).

[![Latest Stable Version](https://poser.pugx.org/kevinpapst/kimai2/v/stable)](https://packagist.org/packages/kevinpapst/kimai2)
[![License](https://poser.pugx.org/kevinpapst/kimai2/license)](https://packagist.org/packages/kevinpapst/kimai2)
[![Travis Status](https://travis-ci.org/kevinpapst/kimai2.svg?branch=master)](https://travis-ci.org/kevinpapst/kimai2)
[![Code Quality](https://scrutinizer-ci.com/g/kevinpapst/kimai2/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/kevinpapst/kimai2/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/kevinpapst/kimai2/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/kevinpapst/kimai2/?branch=master)
[![Scrutinizer Status](https://scrutinizer-ci.com/g/kevinpapst/kimai2/badges/build.png?b=master)](https://scrutinizer-ci.com/g/kevinpapst/kimai2/build-status/master)

## Introduction

This is the _reloaded_ version of the open source timetracker Kimai.
It is built from scratch and doesn't share any source code with its [predecessor](http://www.kimai.org). 
But it adapts the same ideas and a clean & simple UI for your time-tracking experience.

By now it is in an pre-stable development phase, usable and with most advanced features from Kimai 1. 
You can even [import your data](var/docs/migration_v1.md) and start testing and using it today.

Kimai is a [multi-language application](var/docs/translations.md) and already translated to english, german, italian, french, spanish, russian, arabic, hungarian and portuguese.

### Requirements

- PHP 7.1.3 or higher (test your system compatibility with the [requirements-checker](http://symfony.com/doc/current/reference/requirements.html))
- The PHP extensions [xml](http://php.net/manual/en/book.xml.php), [mbstring](http://php.net/manual/en/book.mbstring.php), [gd](http://php.net/manual/en/book.image.php), [intl](https://php.net/manual/en/book.intl.php), [zip](https://php.net/manual/en/book.zip.php) and [PDO](https://php.net/manual/en/book.pdo.php) with either [pdo_sqlite](https://php.net/manual/en/ref.pdo-sqlite.php) or [pdo_mysql](https://php.net/manual/en/ref.pdo-mysql.php) enabled
- Kimai needs its own sub-domain or you need to [recompile the frontend assets](var/docs/developers.md) for usage in a sub-directory
- If you use MariaDB, make sure its at least v10.2.7 (see [FAQ](var/docs/faq.md))
- A modern browser, Kimai v2 might be broken on old browsers like IE 10

## Documentation

Looking for more information about Kimai 2? Check out our detailed [documentation](var/docs/).

### Installation

There are multiple ways to install Kimai, all of them described in the [installation docu](var/docs/installation.md):

- [Recommended installation with GIT and Composer](var/docs/installation.md#recommended-setup)
- [Development setup](var/docs/installation.md#development-installation) 
- [Docker](var/docs/docker.md)
- [1-click installations](var/docs/installation.md#hosting-and-1-click-installations) 
- [FTP](var/docs/installation.md#ftp-installation)

### Updating Kimai

Read the following documentations before you start your upgrade:

- The [update documentation](var/docs/updates.md)
- The version specific [UPGRADING guide](UPGRADING.md)
- The [release information](https://github.com/kevinpapst/kimai2/releases)

## Roadmap and releases

You can see our development roadmap in the [Milestones](https://github.com/kevinpapst/kimai2/milestones) sections.
It is open for changes and input from the community, your [ideas and questions](https://github.com/kevinpapst/kimai2/issues) are welcome!

> Kimai 2 uses a rolling release concept for delivering updates.
> You can upgrade Kimai at any time, you don't need to wait for the next official release. Read the [upgrade docs](UPGRADING.md) first!

Release versions will be created on a regular base and you can use these tags if you are familiar with Git, 
but we will not provide support for any specific version.
Every code change, whether it's a new feature or a bug fix, will be done on the master branch. 
I have to do it this way, as I develop Kimai in my free time and want to put most
effort into the software instead of backporting changes for old versions. 

## Plugins for Kimai 2

Kimai 2 was built on top of the Symfony framework and can be extended in various ways:

- Developer start with the [developer documentation](var/docs/developers.md)
- Users looking for existing plugins go to our [plugin marketplace](xxxx FIXME xxxx) 

## Credits

Kimai 2 is developed with modern frameworks like 
[Symfony v4](https://github.com/symfony/symfony), 
[Doctrine](https://github.com/doctrine/),
[AdminLTE](https://github.com/kevinpapst/AdminLTEBundle/) and 
[many](composer.json) [more](package.json).
