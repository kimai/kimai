Contributing
============

The Symfony Demo application is an open source project. Contributions made by
the community are welcome. Send us your ideas, code reviews, pull requests and
feature requests to help us improve this project. All contributions must follow
the [usual Symfony contribution requirements](http://symfony.com/doc/current/contributing/index.html).

Web Assets Management
---------------------

This project manages its web assets in a special way to allow them to work
without configuring any option, installing any tool or executing any command.
If your contribution changes CSS styles or JavaScript code in any way, make
sure to regenerate the `web/css/app.css` and `web/js/app.js` files. To do so,
uncomment the Assetic blocks in the `app/Resources/views/base.html.twig` and
execute the following command:

```bash
$ php bin/console assetic:dump --no-debug
```
