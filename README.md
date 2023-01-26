<p align="center">
    <img src="https://raw.githubusercontent.com/kimai/images/main/repository-header.png" alt="Kimai logo">
</p>

<p align="center">
    <a href="https://github.com/kimai/kimai/actions"><img alt="CI Status" src="https://github.com/kimai/kimai/workflows/CI/badge.svg"></a>
    <a href="https://codecov.io/gh/kimai/kimai"><img alt="Code Coverage" src="https://codecov.io/gh/kimai/kimai/branch/main/graph/badge.svg"></a>
    <a href="https://packagist.org/packages/kimai/kimai"><img alt="Latest stable version" src="https://poser.pugx.org/kimai/kimai/v/stable"></a>
    <a href="https://www.gnu.org/licenses/agpl-3.0.en.html"><img alt="License" src="https://poser.pugx.org/kimai/kimai/license"></a>
    <a href="https://phpc.social/@kimai" rel="me"><img alt="Mastodon" src="https://img.shields.io/badge/toot-%40kimai-8c8dff"></a>
    <a href="https://hosted.weblate.org/engage/kimai/"><img src="https://hosted.weblate.org/widgets/kimai/-/svg-badge.svg" alt="Translation status" /></a>
</p>

<h1 align="center">Kimai — time-tracker</h1>

Copylefted libre (and also gratis) online time-tracking software designed for small businesses and freelancers \
built with modern technologies such as [Symfony](https://github.com/symfony/symfony), [Bootstrap](https://github.com/twbs/bootstrap), 
[RESTful API](https://github.com/FriendsOfSymfony/FOSRestBundle), [Doctrine](https://github.com/doctrine/),
[Tabler](https://github.com/kevinpapst/TablerBundle/), [Webpack](https://github.com/webpack/webpack), ES6 and [many](composer.json) [more](package.json).

## Introduction

- [Home](https://www.kimai.org) — Kimai project homepage
- [Blog](https://www.kimai.org/blog/) — Read the latest news
- [Documentation](https://www.kimai.org/documentation/) — Learn how to use Kimai
- [Translations](https://hosted.weblate.org/projects/kimai/#languages) — Kimai in your language
- [Migration](https://www.kimai.org/documentation/migration-v1.html) — Import data from Kimai 1 

### Requirements

- PHP 8.1 minimum.
- MariaDB or MySQL.
- A webserver and subdomain.
- PHP extensions: `GD`, `intl`, `JSON`, `PDO`, `XSL`, `Zip`, and (not enabled by default) `mbstring`.

### About

The evolution of the most known(?) open source project time-tracker Kimai. \
Stable, production-ready and ships with many advanced features, including but not limited to: 

JSON API, invoicing, data exports, multi-timer and punch-in punch-out mode, tagging, multi-user and multi-timezones, 
authentication via SAML/LDAP/database, customizable role and team permissions, responsive and ready for your mobile device, 
user/customer/project specific rates, advanced search and filtering, money- and time budgets, reporting, support for plugins 
and more.

## Installation

- [Recommended setup](https://www.kimai.org/documentation/installation.html#recommended-setup) — with Git and Composer.
- [Docker](https://www.kimai.org/documentation/docker.html) — containerized.
- [Development](https://www.kimai.org/documentation/installation.html#development-installation) — on your local machine.
- [1-click installer](https://www.kimai.org/documentation/installation.html#hosting-and-1-click-installations) — hosted environments.

### Updating Kimai

- [Update Kimai](https://www.kimai.org/documentation/updates.html) — get the latest version.
- [UPGRADING guide](UPGRADING.md) — version-specific steps.

### Plugins

- [Plugin marketplace](https://www.kimai.org/store/) — find existing plugins.
- [Developer documentation](https://www.kimai.org/documentation/developers.html) — how to create a plugin.

## Roadmap and releases

You can see a rough development roadmap in the [milestone](https://github.com/kimai/kimai/milestones) sections. \
Changes and input from the community, [ideas and questions](https://github.com/kimai/kimai/issues) is welcomed.

Release versions will be created on a regular basis, every couple of weeks. \
Every code change (whether a new feature or a bugfix) is done on the `main` branch. 

## Contributing

Start contributing to this repository with a [pull request](https://github.com/kimai/kimai/pulls) for direct changes, [open a new issue](https://github.com/kimai/kimai/issues) to file bug- or feature requests, or start a [discussion](https://github.com/kimai/kimai/discussions) for questions, support and such.

In case you want to contribute, but you wouldn't know how, here are some suggestions:

- Spread the word — More users means more people testing and contributions to Kimai — in turn granting better stability and more and better features. \
Please vote for Kimai on platforms listing software alternatives or solutions to problems. You can also microblog or share it on any of your favorite social-media platforms. Every bit helps.
- **Answer questions** — Know the answer to another user's problem? Share your knowledge.
- **Ideas** — for something that can be done better? Essential feature missing? Create a feature request.
- **Report bugs** — Report even small things and inconveniences.
- **Sponsor** — Help the symbiotic relationship by giving some of the value the project gives you.
- **[Document](https://www.kimai.org/documentation/), and [translate](https://hosted.weblate.org/projects/kimai/)** — The documentation and translation is a continuous effort.

Weblate is also copylefted libre software. \
<a href="https://hosted.weblate.org/engage/kimai/"><img src="https://hosted.weblate.org/widgets/kimai/-/horizontal-green.svg" alt="Translation status" /></a>

There is one simple rule: Don't be an ass.
