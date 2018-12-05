# Kimai 2 - Time Tracking made easy

Kimai - the open source time-tracking application with a mobile-first approach (read more at the [official website](http://v2.kimai.org)).

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
- The PHP extensions [intl](https://php.net/manual/en/book.intl.php), [zip](https://php.net/manual/en/book.zip.php) and [PDO](https://php.net/manual/en/book.pdo.php) with either [pdo_sqlite](https://php.net/manual/en/ref.pdo-sqlite.php) or [pdo_mysql](https://php.net/manual/en/ref.pdo-mysql.php) enabled
- If you use MariaDB, make sure its at least v10.2.7 (see [FAQ](var/docs/faq.md))
- Kimai needs to be installed in the root directory of a domain or you need to [recompile the frontend assets](var/docs/developers.md)
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
> You can upgrade Kimai at any time, you don't need to wait for the next official release.

Release versions will be created on a regular base and you can use these tags if you are familiar with Git, 
but we will not provide support for any specific version.
Every code change, whether it's a new feature or a bug fix, will be done on the master branch and 
intensively tested before merging. We have to do it this way, as we develop Kimai in our free time and want to put our 
effort into the software instead of backporting changes for old versions. 

## Extensions for Kimai 2

As Kimai 2 was built on top of Symfony, it can be extended like every other Symfony application.
We call these extensions bundles, but you might also know them as add-ons, extensions or plugins.

All available Kimai 2 bundles can be found at the [Kimai recipes](https://github.com/kimai/recipes) repository.

## Developer

Kimai 2 is developed with modern frameworks like [Symfony v4](https://github.com/symfony/symfony), [Doctrine](https://github.com/doctrine/),
[AdminLTE](https://github.com/kevinpapst/AdminLTEBundle/) and [many](composer.json) [more](package.json).

If you want to start developing for Kimai 2, please read the following documentation:

- an example on how to extend Kimai 2 can be found in this [GitHub repository](https://github.com/kevinpapst/kimai2-invoice)
- the [developer documentation](var/docs/developers.md) is available both on GitHub and your local installation
