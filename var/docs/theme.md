# Theme

Kimai uses the [AdminLTE theme](https://github.com/kevinpapst/AdminLTEBundle/) which can be configured in the file `config/packages/admin_lte.yaml`. 
You find the theme specific documentation [here](https://github.com/kevinpapst/AdminLTEBundle/blob/master/Resources/docs/configurations.md).

All Kimai specific theme settings will be available in the twig templates with the global `kimai_context` key, e.g.

```twig
{{ kimai_context.box_color }}
``` 

## Searchable input types

The select boxes for customer, project and activity are by default the OS standard UI elements. 
This might be a limit for users with a long list of active and non-hidden elements.

Therefor a test is currently running, which can be activated setting the the following configuration:  

```yaml
kimai:
    theme:
        select_type: selectpicker
```

This will turn the select boxes into javascript elements with quick search option. 

Why is this a beta test? It's not clear, if we keep on using this javascript library or activate it by default.
Therefor your feedback is highly welcome, please post your opinion at GitHub.   

## Active entries warning

A small colored warning sign will be shown, if a user has more than X active timesheet entries.

The amount `X` is configured in your `local.yaml` with the setting `timesheet.active_entries.soft_limit` (see [configurations.md](configurations.md)).

## Colors

Kimai allows you to configure colors in several places throughout the theme. 

Possible values are:

- `aqua`
- `black`
- `blue`
- `gray`
- `green`
- `purple`
- `red`
- `yellow`

### Fallback color

Whenever a color is required but none is configured, Kimai uses a fallback color from the config key `kimai.theme.box_color`.

You can change the default color `green` to any one from the above in your `local.yaml`:

```yaml
kimai:
    theme:
        box_color: 'blue'
```

The fallback color should be applied whenever an optional color is configurable by the user:

```twig
<div class="info-box bg-{{ color|default(kimai_context.box_color) }}"></div>
```

## Icons

Kimai allows you to configure icons in several places (provided by [Font Awesome 5](https://fontawesome.com/icons)) and ships 
with a pre-defined list of icon aliases to guarantee a consistent look.  

The pre-defined icons aliases are:

`activity`, `admin`, `calendar`, `customer`, `create`,`dashboard`, `delete`, `download`, `duration`, `edit`, `filter`, 
`help`, `invoice`, `list`, `logout`, `manual`, `money`, `print`, `project`, `repeat`, `start`, `start-small`, `stop`, 
`stop-small`, `timesheet`, `trash`, `user`, `visibility`

The full list can be found in this [TwigExtension](https://github.com/kevinpapst/kimai2/blob/master/src/Twig/Extensions.php).

Icon aliases can be used by applying the `icon` filter, e.g.

```
<i class="{{ 'money'|icon }}"></i>
```
