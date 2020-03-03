# Kimai 2 - online time-tracker

[![CI Status](https://github.com/kevinpapst/kimai2/workflows/CI/badge.svg)](https://github.com/kevinpapst/kimai2/actions)
[![Code Coverage](https://codecov.io/gh/kevinpapst/kimai2/branch/master/graph/badge.svg)](https://codecov.io/gh/kevinpapst/kimai2)
[![Latest Stable Version](https://poser.pugx.org/kevinpapst/kimai2/v/stable)](https://packagist.org/packages/kevinpapst/kimai2)
[![License](https://poser.pugx.org/kevinpapst/kimai2/license)](https://packagist.org/packages/kevinpapst/kimai2)
[![Gitter](https://badges.gitter.im/kimai2/support.svg)](https://gitter.im/kimai2/support)
[![Bountysource](https://img.shields.io/bountysource/team/kimai2/activity)](https://www.bountysource.com/teams/kimai2)

Kimai is a free, open source and online time-tracking software designed for small businesses and freelancers. 
It is built with modern technologies such as Symfony, Bootstrap, RESTful API, Doctrine, AdminLTE, Webpack, ES6 etc.

## Introduction

- [Home](https://www.kimai.org) - The house of Kimai
- [Blog](https://www.kimai.org/blog/) - Get the latest news
- [Documentation](https://www.kimai.org/documentation/) - Learn how to use
- [Translations](https://www.kimai.org/documentation/translations.html) - Kimai in your language
- [Migration](https://www.kimai.org/documentation/migration-v1.html) - Import data from v1 

### Requirements

- PHP 7.2.9 or higher
- Database (MySQL/MariaDB, SQLite for development)
- Webserver (nginx, Apache)
- A modern browser
- [Other libraries](https://www.kimai.org/download/)

### About

This is the new version of the open source timetracker Kimai. It is stable and production ready, ships
with most advanced features from Kimai 1 and many new ones, including but not limited to: 

JSON API, invoicing, data exports, multi-timer and punch-in punch-out mode, tagging, multi-user and multi-timezones, 
authentication via SAML/LDAP/Database, customizable role permissions, responsive and ready for your mobile device, 
user specific rates, advanced search & filtering, money and time budgets with report, support for plugins and many more.

## Installation

- [Recommended setup](https://www.kimai.org/documentation/installation.html#recommended-setup) - with Git and Composer
- [Docker](https://www.kimai.org/documentation/docker.html) - containerized
- [Development](https://www.kimai.org/documentation/installation.html#development-installation) - on your local machine 
- [1-click installer](https://www.kimai.org/documentation/installation.html#hosting-and-1-click-installations) - hosted environments 
- [FTP](https://www.kimai.org/documentation/installation.html#ftp-installation) - unfortunately still widely used ;-)

### Updating Kimai

- [Update Kimai](https://www.kimai.org/documentation/updates.html) - the documentation
- [UPGRADING guide](UPGRADING.md) - version specific steps

### Plugins

- [Plugin marketplace](https://www.kimai.org/store/) - find existing plugins here
- [Developer documentation](https://www.kimai.org/documentation/developers.html) - how to create a plugin

## Roadmap and releases

You can see a rough development roadmap in the [Milestones](https://github.com/kevinpapst/kimai2/milestones) sections.
It is open for changes and input from the community, your [ideas and questions](https://github.com/kevinpapst/kimai2/issues) are welcome.

> Kimai 2 uses a rolling release concept for delivering updates.
> You can upgrade Kimai at any time , you don't need to wait for the next official release.

Release versions will be created on a regular base (approx. one release per month) and you should use these tags if you are not familiar with git.
Every code change, whether it's a new feature or a bug fix, will be done on the master branch. 
I develop Kimai in my free time and want to put my effort into the software instead of backporting changes for old versions.
The only exception is a critical security issue, which I would fix in the last version as well. 

## Credits

Kimai 2 is developed with modern frameworks like 
[Symfony v4](https://github.com/symfony/symfony), 
[Doctrine](https://github.com/doctrine/),
[AdminLTEBundle](https://github.com/kevinpapst/AdminLTEBundle/) (based on [AdminLTE theme](https://github.com/almasaeed2010/AdminLTE)) and 
[many](composer.json) [more](package.json).
