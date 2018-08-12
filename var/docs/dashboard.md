# Dashboard

Read the [configuration chapter](configurations.md) before you start changing your configs. 

## Widgets

Widgets are defined in the configuration node `kimai.widgets` and you find the pre-defined ones in [kimai.yaml](../../config/packages/kimai.yaml).

Here is an example of one widget definition:

```yaml
kimai:
    widgets:
        userDurationToday: { title: stats.durationToday, query: duration, user: true, begin: '00:00:00', end: '23:59:59', icon: duration, color: green }
```

Widgets are currently only used in the Dashboard, but maybe used in other template parts as well in the future.

### Widget settings

- `title` - the title of your widget (will be translated)
- `query` - the allowed queries to use for populating the widget data are `duration`, `rate`, `active` and `users`
- `user` - whether the query is executed for the current user or for all users. possible values are `true` and `false` (default: `false` - all data is used to calculate the result)
- `begin` - setting the start date for the query, formatted with the [PHP DateTime syntax](http://php.net/manual/en/datetime.formats.relative.php) (default: `null` - a query matching any start date)
- `end` - setting the end date for the query, formatted with the [PHP DateTime syntax](http://php.net/manual/en/datetime.formats.relative.php) (default: `null` - a query matching any end date)
- `color` - a color name, see all possible names in [theme settings](theme.md) (default: ``)
- `icon` - an icon alias from [theme settings](theme.md) or any other icon from [Font Awesome 5](https://fontawesome.com/icons) (default: `null` - no icon)

## Dashboard sections

Within the dashboard all widgets are placed in sections (rows) like this:

```yaml
kimai:
    dashboard:
        user_duration:
            title: dashboard.you
            order: 10
            permission: ROLE_USER
            widgets: [userDurationToday, userDurationWeek, userDurationMonth, userDurationYear, userDurationTotal]
``` 

### Section settings

- `permission` - the name of a role who is allowed to see the widgets, see [users](users.md)
- `title` - the title of a section, if omitted no title will be shown (default: `null`) 
- `widgets` - an array of widget names (see above for an example)
- `order` - allows to define the order of the section

### Default sections

The dashboard has the following default sections:

- `user_duration` - order 10
- `user_rates` - order 20
- `duration` - order 30
- `active_users` - order 40
- `rates` - order 50
- `admin` - order 100 (this section is programmatically added)  

### Overwriting sections

A section with an empty list of widgets will not be rendered.
If you don't like the default sections you can remove them by overwriting their widget list like this:

```yaml
kimai:
    dashboard:
        user_duration: { widgets: [] }
        user_rates: { widgets: [] }
        duration: { widgets: [] }
        active_users: { widgets: [] }
        rates: { widgets: [] }
```

It's also possible to change the title or the list of widgets for every section like this:

```yaml
kimai:
    dashboard:
        user_duration:
            title: 'some fancy widgets'
            widgets: [userDurationWeek, userDurationMonth, userDurationYear]
```

### Reorder sections

If you want to reorder the sections, you can overwrite as many sections as you want and simply change their `order` key. 
Lower numbers will be rendered before higher numbers. 

```yaml
kimai:
    dashboard:
        user_duration: { order: 30 }
        user_rates: { order: 90 }
        duration: { order: 40 }
        active_users: { order: 20 }
        rates: { order: 50 }
```
