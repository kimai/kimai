<p align="center">
    <img src="https://raw.githubusercontent.com/kimai/images/master/repository-header.png" alt="Kimai logo">
</p>

<p align="center">
    <a href="https://github.com/kevinpapst/kimai2/actions"><img alt="CI Status" src="https://github.com/kevinpapst/kimai2/workflows/CI/badge.svg"></a>
    <a href="https://codecov.io/gh/kevinpapst/kimai2"><img alt="Code Coverage" src="https://codecov.io/gh/kevinpapst/kimai2/branch/master/graph/badge.svg"></a>
    <a href="https://packagist.org/packages/kevinpapst/kimai2"><img alt="Latest stable version" src="https://poser.pugx.org/kevinpapst/kimai2/v/stable"></a>
    <a href="https://packagist.org/packages/kevinpapst/kimai2"><img alt="License" src="https://poser.pugx.org/kevinpapst/kimai2/license"></a>
    <a href="https://twitter.com/kimai_org"><img alt="Twitter" src="https://img.shields.io/badge/follow-%40kimai__org-00acee"></a>
</p>

<h1 align="center">Kimai - time-tracker</h1>

Kimai is a free, open source and online time-tracking software designed for small businesses and freelancers. 
It is built with modern technologies such as [Symfony](https://github.com/symfony/symfony), [Bootstrap](https://github.com/twbs/bootstrap), 
[RESTful API](https://github.com/FriendsOfSymfony/FOSRestBundle), [Doctrine](https://github.com/doctrine/),
[AdminLTE](https://github.com/kevinpapst/AdminLTEBundle/), [Webpack](https://github.com/webpack/webpack), ES6 and [many](composer.json) [more](package.json).

## Introduction

- [Home](https://www.kimai.org) - Kimai project homepage
- [Blog](https://www.kimai.org/blog/) - Read the latest news
- [Documentation](https://www.kimai.org/documentation/) - Learn how to use Kimai
- [Translations](https://hosted.weblate.org/projects/kimai/#languages) - Kimai in your language
- [Migration](https://www.kimai.org/documentation/migration-v1.html) - Import data from Kimai 1 

### Requirements

- PHP 7.4 or higher (PHP 8 supported, PHP 8.1 does not support LDAP yet)
- MariaDB or MySQL
- A webserver and subdomain
- PHP extensions: `gd`, `intl`, `json`, `mbstring`, `pdo`, `xsl`, `zip`

### About

This is the new version of the open source time tracker Kimai. It is stable and production ready, ships
with many advanced features, including but not limited to: 

JSON API, invoicing, data exports, multi-timer and punch-in punch-out mode, tagging, multi-user and multi-timezones, 
authentication via SAML/LDAP/Database, customizable role and team permissions, responsive and ready for your mobile device, 
user specific rates, advanced search & filtering, money and time budgets, reporting, support for plugins and many more.

## Installation

- [Recommended setup](https://www.kimai.org/documentation/installation.html#recommended-setup) - with Git and Composer
- [Docker](https://www.kimai.org/documentation/docker.html) - containerized
- [Development](https://www.kimai.org/documentation/installation.html#development-installation) - on your local machine 
- [1-click installer](https://www.kimai.org/documentation/installation.html#hosting-and-1-click-installations) - hosted environments 

### Updating Kimai

- [Update Kimai](https://www.kimai.org/documentation/updates.html) - get the latest version
- [UPGRADING guide](UPGRADING.md) - version specific steps

### Plugins

- [Plugin marketplace](https://www.kimai.org/store/) - find existing plugins here
- [Developer documentation](https://www.kimai.org/documentation/developers.html) - how to create a plugin

## Roadmap and releases

You can see a rough development roadmap in the [Milestones](https://github.com/kevinpapst/kimai2/milestones) sections.
It is open for changes and input from the community, your [ideas and questions](https://github.com/kevinpapst/kimai2/issues) are welcome.

> Kimai 2 uses a rolling release concept for delivering updates.
> You can upgrade Kimai at any time, you don't need to wait for the next official release.
> The master branch is always deployable, release tags are only snapshots of the current development version.

Release versions will be created on a regular base (approx. one release every 2 months).
Every code change, whether it's a new feature or a bugfix, will be done on the master branch. 
Kimai is actively developed in my spare time, I put my effort into the software instead of backporting changes.

## Contributing

You want to contribute to this repository? This is so great!
The best way to start is to [open a new issue](https://github.com/kevinpapst/kimai2/issues) for bugs or feature requests or a [discussion](https://github.com/kevinpapst/kimai2/discussions) for questions, support and such.

In case you want to contribute, but you wouldn't know how, here are some suggestions:

- Spread the word: More user means more people testing and contributing to Kimai - which in turn means better stability and more and better features. Please vote for Kimai on platforms lie Slant, Product Hunt, Softpedia or AlternativeTo, you can tweet about it, share it on LinkedIn, reddit or any of your favorite social media platforms. Every little bit helps!
- Answer questions: You know the answer to another user's problem? Share your knowledge!
- Make a feature request: Something can be done better? Something essential missing? Let us know!
- Report bugs
- Contribute: You don't have to be programmer to help. The documentation and translation could use some love as well.
- Sponsor the project

There is one simple rule in our "Code of conduct": Don't be an ass! 
