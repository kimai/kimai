# Translations

We try to keep the number of language files small, in order to make it easier to identify the location of application messages and to unify the codebase.

- If you add a new key, you have to add it in every language file
- Its very likely that you want to edit the file `messages` as it holds 90% of our application translations 

The files in `translations/` as a quick overview:

- `exceptions` only holds translations of error pages and exception handlers
- `flashmessages` hold all success and error messages, that will be shown as results from action calls after page reload
- `messages` holds most of the visible application translations (like all the static UI elements and form translations)
- `pagerfanta` includes the translations for the pagination component
- `sidebar` holds all the translations of the right sidebar
- `validators` only hold translations related to violations/validation of submitted form data (or API calls)

## Adding a new language

As example I choose a new hypothetical language with the locale `xx`. 

Copy each translation file from `translations/*.en.xliff` and rename them to `translations/*.xx.xliff`.

Adjust the `target-language` in the file header, as example for the new file `exceptions.xx.xliff`:
```yml
<file date="2018-08-01T20:00:00Z" source-language="en" target-language="xx" datatype="plaintext" original="exceptions.en.xliff">`
```

Adjust the file `config/packages/kimai.yaml` and add the language settings below the key `kimai.languages`: 
```yaml
kimai:
    languages:
        xx:
            date_short: 'd.m.Y'
```

Append the new locale in the file `config/services.yaml` at `parameters.app_locales` divided by a pipe:

```yaml
parameters:
    locale: en
    app_locales: en|de|ru|it|xx
```
