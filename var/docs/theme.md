# Theme

Kimai uses the [AdminLTE theme](https://github.com/kevinpapst/AdminLTEBundle/) which can be configured in the file `config/packages/admin_lte.yaml`. 
You find the theme specific documentation [here](https://github.com/kevinpapst/AdminLTEBundle/blob/master/Resources/docs/configurations.md).

All Kimai specific theme settings will be available in the twig templates with the global `kimai_context` key, e.g.

```twig
{{ kimai_context.box_color }}
``` 

## Active entries warning

A small colored warning sign will be shown, if a user has more than 3 active timesheet entries.

You can change this soft limit by setting the config key `kimai.theme.active_warning` in your `local.yaml`:

```yaml
kimai:
    theme:
        active_warning: 2
```

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

Whenever a color is required but none is configured, Kimai uses a fallback language from the config key `kimai.theme.box_color`.

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

- `user`
- `customer`
- `project`
- `activity`
- `admin`
- `invoice`
- `timesheet`
- `dashboard`
- `logout`
- `trash`
- `delete`
- `repeat`
- `edit`
- `manual`
- `help`
- `start`
- `start-small`
- `stop`
- `stop-small`
- `filter`
- `create`
- `list`
- `print`
- `visibility`
- `calendar`
- `money`
- `duration`

Icon aliases can be used by applying the `icon` filter, e.g.

```
<i class="{{ 'money'|icon }}"></i>
```


