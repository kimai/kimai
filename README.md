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

Kimai is a professional grade time-tracking application, free and open-source. 
It handles use-cases of freelancers as well as companies with dozens or hundreds of users. 
Kimai was build to track your project times and ships with many advanced features, including but not limited to:

JSON API, invoicing, data exports, multi-timer and punch-in punch-out mode, tagging, multi-user - multi-timezones - multi-language ([over 30 translations existing](https://hosted.weblate.org/projects/kimai/)!),
authentication via SAML/LDAP/Database, two-factor authentication (2FA) with TOTP, customizable role and team permissions, responsive design,
user/customer/project specific rates, advanced search & filtering, money and time budgets, advanced reporting, support for [plugins](https://www.kimai.org/store/)
and so much more.

### Versions

There are two versions of Kimai existing:

- [Version 1](https://github.com/kimai/kimai/tree/1.x) — compatible with PHP 7.4, which is in maintenance mode since 2023 
- [Version 2](https://github.com/kimai/kimai) — stable and "almost released" (waiting for some major plugins, which are not yet migrated) 

### Links

- [Home](https://www.kimai.org) — Kimai project homepage
- [Blog](https://www.kimai.org/blog/) — Read the latest news
- [Documentation](https://www.kimai.org/documentation/) — Learn how to use Kimai

### Requirements

- PHP 8.1 minimum
- MariaDB or MySQL
- A webserver and subdomain (subdirectory is not supported)
- PHP extensions: `gd`, `intl`, `json`, `mbstring`, `pdo`, `tokenizer`, `xml`, `xsl`, `zip`

## Installation

- [Recommended setup](https://www.kimai.org/documentation/installation.html#recommended-setup) — with Git and Composer
- [Docker](https://hub.docker.com/r/kimai/kimai2) — containerized by [@tobybatch](https://github.com/tobybatch/kimai2)

There are also documentations for:
- [developer setups](https://www.kimai.org/documentation/developers.html) — on your local machine
- [shared hostings](https://www.kimai.org/documentation/installation.html#shared-hosting) — the least favorable option
- [Synology](https://www.kimai.org/documentation/synology.html) — you could try to host the Docker version instead 
- [1-click installer](https://www.kimai.org/documentation/installation.html#hosting-and-1-click-installations) — hosted environments 

And if you don't want to host Kimai, you can use [the Cloud version](https://www.kimai.cloud/) of it.

### Updating Kimai

- [Update Kimai](https://www.kimai.org/documentation/updates.html) — get the latest version
- [UPGRADING guide](UPGRADING.md) — version specific steps

### Plugins

- [Plugin marketplace](https://www.kimai.org/store/) — find existing plugins here
- [Developer documentation](https://www.kimai.org/documentation/developers.html) — how to create a plugin

## Roadmap and releases

You can see a rough development roadmap in the [Milestones](https://github.com/kimai/kimai/milestones) sections.
It is open for changes and input from the community, your [ideas and questions](https://github.com/kimai/kimai/issues) are welcome.

Release versions will be created on a regular basis, every couple of weeks latest.
Every code change, whether it's a new feature or a bugfix, will be done on the `main` branch.

For the time being and until 2.0 is widely adopted, the [1.x branch](https://github.com/kimai/kimai/tree/1.x) will receive bug fixes. 

## Contributing

You want to contribute to this repository? This is so great!
The best way to start is to [open a new issue](https://github.com/kimai/kimai/issues) for bugs or feature requests or a [discussion](https://github.com/kimai/kimai/discussions) for questions, support and such.

In case you want to contribute, but you wouldn't know how, here are some suggestions:

- Spread the word: More user means more people testing and contributing to Kimai, which in turn means better stability and more and better features. Please vote for Kimai on any software platform, you can toot or tweet about it, share it on LinkedIn, Reddit or any of your favorite social media platforms. Every bit helps!
- Answer questions: You know the answer to another user's problem? Share your knowledge.
- Something can be done better? An essential feature is missing? Create a feature request.
- Report bugs makes Kimai better for everyone.
- You don't have to be programmer, the documentation and translation could always use some attention.
- Sponsor the project: free software costs money to create!

There is one simple rule in our "Code of conduct": Don't be an ass!

### Credits

Kimai is based on modern technologies and frameworks such as [PHP](https://www.php.net/),
[Symfony](https://github.com/symfony/symfony) and [Doctrine](https://github.com/doctrine/),
[Bootstrap](https://github.com/twbs/bootstrap) and [Tabler](https://tabler.io/),
and [countless](composer.json) [others](package.json).

