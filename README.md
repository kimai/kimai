<p align="center">
    <img src="https://raw.githubusercontent.com/kimai/images/main/repository-header.png" alt="Kimai logo">
</p>

<p align="center">
    <a href="https://github.com/kimai/kimai/actions"><img alt="CI Status" src="https://github.com/kimai/kimai/workflows/CI/badge.svg"></a>
    <a href="https://codecov.io/gh/kimai/kimai"><img alt="Code Coverage" src="https://codecov.io/gh/kimai/kimai/branch/main/graph/badge.svg"></a>
    <a href="https://packagist.org/packages/kimai/kimai"><img alt="Latest stable version" src="https://poser.pugx.org/kimai/kimai/v/stable"></a>
    <a href="https://www.gnu.org/licenses/agpl-3.0.en.html"><img alt="License" src="https://poser.pugx.org/kimai/kimai/license"></a>
    <a href="https://phpc.social/@kimai" rel="me"><img alt="Mastodon" src="https://img.shields.io/badge/toot-%40kimai-8c8dff"></a>
</p>

<h1 align="center">Kimai - time-tracker</h1>

Kimai is a free, open source and online time-tracking software designed for small businesses and freelancers. 
It is built with modern technologies such as [Symfony](https://github.com/symfony/symfony), [Bootstrap](https://github.com/twbs/bootstrap), 
[JSON API](https://github.com/FriendsOfSymfony/FOSRestBundle), [Doctrine](https://github.com/doctrine/),
[Tabler](https://github.com/kevinpapst/TablerBundle/), ES6 and [many](composer.json) [more](package.json).

## Introduction

- [Home](https://www.kimai.org) - Kimai project homepage
- [Blog](https://www.kimai.org/blog/) - Read the latest news
- [Documentation](https://www.kimai.org/documentation/) - Learn how to use Kimai
- [Translations](https://hosted.weblate.org/projects/kimai/#languages) - Kimai is translated at Weblate

### Requirements

- PHP 8.1 minimum
- MariaDB or MySQL
- A webserver and subdomain (subdirectory does not work)
- PHP extensions: `gd`, `intl`, `json`, `mbstring`, `pdo`, `xsl`, `zip`

### About

Kimai is a professional grade time-tracking application, build to track your project times.
It ships with many advanced features, including but not limited to: 

JSON API, invoicing, data exports, multi-timer and punch-in punch-out mode, tagging, multi-user - multi-timezones - multi-language, 
authentication via SAML/LDAP/Database, support for 2FA with TOTP, customizable role and team permissions, responsive and ready for your mobile device, 
user/customer/project specific rates, advanced search & filtering, money and time budgets, advanced reporting, support for plugins 
and so many more.

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

You can see a rough development roadmap in the [Milestones](https://github.com/kimai/kimai/milestones) sections.
It is open for changes and input from the community, your [ideas and questions](https://github.com/kimai/kimai/issues) are welcome.

Release versions will be created on a regular basis, every couple of weeks latest.
Every code change, whether it's a new feature or a bugfix, will be done on the `main` branch.

For the time being and until 2.0 landed everywhere, the [1.x branch](https://github.com/kimai/kimai/tree/1.x) will receive bug fixes. 

## Contributing

You want to contribute to this repository? This is so great!
The best way to start is to [open a new issue](https://github.com/kimai/kimai/issues) for bugs or feature requests or a [discussion](https://github.com/kimai/kimai/discussions) for questions, support and such.

In case you want to contribute, but you wouldn't know how, here are some suggestions:

- Spread the word: More user means more people testing and contributing to Kimai - which in turn means better stability and more and better features. Please vote for Kimai on any software platform, you can toot or tweet about it, share it on LinkedIn, Reddit or any of your favorite social media platforms. Every bit helps!
- Answer questions: You know the answer to another user's problem? Share your knowledge.
- Something can be done better? An essential feature is missing? Create a feature request.
- Report bugs makes Kimai better for everyone.
- You don't have to be programmer, the documentation and translation could always use some attention.
- Sponsor the project: free software costs money to create!

There is one simple rule in our "Code of conduct": Don't be an ass!
