# Configurations

## Environment specific settings (.env)

The most basic settings, which need always be adjusted are stored in the `.env` file:
 
- `MAILER_URL` - smtp connection for emails
- `MAILER_FROM` - application specific "from" address for all emails
- `APP_ENV` - environment for the runtime (use `prod` if you are unsure)
- `DATABASE_URL` - database connection for storing all application data
- `DATABASE_PREFIX` - precix for any Kimai table in the configured database
- `APP_SECRET` - secret used for hasing user password (if you cahnge this, every password is invalid afterwards) 

## Config files

Configuration of Kimai is spread in all files in the `config/`directory but mainly it these files:

- `.env` - environment specific settings
- `config/packages/kimai.yaml` - Kimai specific settings
- `config/packages/admin_lte.yaml` - Kimai base theme
- `config/packages/fos_user.yaml` - user management and email settings
- `config/packages/local.yaml` - your local configuration settings

There are several other configurations that could potentially be interesting for you in [config/packages/*.yaml](../../config/packages/).

If you want to adjust a setting from any of these files, use `local.yaml` (see below).

#### Other topics

- [Theme settings](theme.md) - in `kimai.yaml` and `admin_lte.yaml`
- [Email configuration](emails.md) - in `swiftmailer.yaml`
- [Dashboard widgets](dashboard.md) - in `kimai.yaml`
- [Calendar](calendar.md) - in `kimai.yaml`

## Overwriting local configs (local.yaml)

You can create the file `config/packages/local.yaml` and store your own settings inside. This file will NEVER be shipped with Kimai.
So having your custom settings in this file allows you to easily update Kimai. This is the same concept which is used for the `.env` file.

An example `config/packages/local.yaml` file might look like this:

```yaml
kimai:
    timesheet:
        rounding:
            default:
                begin: 15
                end: 15

admin_lte:
    options:
        default_avatar: build/apple-touch-icon.png
```

The `local.yaml` file will be imported as last configuration file, so you can overwrite any setting from the `config/packages/` directory.

Whenever the documentation asks you to edit a yaml file from the `config/packages/` directory, it means you should copy 
this specific configuration key to your `local.yaml` in order to overwrite the default configuration.

## Reload changed configurations

When you change a configuration file, Kimai will not see this change immediately. 
You can reload the configs after you are done by rebuilding the Symfony cache with:

```bash
bin/console cache:clear --env=prod
bin/console cache:warmup --env=prod
```

Depending on your setup it might be necessary to execute these commands as webserver user, 
please read the [UPGRADING guide](../../UPGRADING.md) for more details.

## Security

Kimai uses the FOSUserBundle for security related tasks like user management. Its configuration can be found in [fos_user.yaml](../../config/packages/fos_user.yaml).

### User management emails (fos_user.yaml)

Read more about [email configuration](emails.md).

### Remember me login (security.yaml)

The default period for the `Remember me` option can be changed in the config file [security.yaml](../../config/packages/security.yaml). 

### User registration

If you want your new users to use [email](emails.md) based activation add this to your `local.yaml`:

```yaml
fos_user:
    registration:
        confirmation:
            enabled: true
```

#### Disable user registration 

If you want to disable the user registration, add this your `local.yaml`: 
```yaml
kimai:
    user:
        registration: false
```

If you only want to hide the link from the login form but keep the functionality, add this your `local.yaml`: 
```yaml
admin_lte:
    routes:
        adminlte_registration: ~
```

### Password reset

If you want to configure the behaviour (like the allowed time between multiple retries) then configure the settings:

- in `config/packages/fos_user.yaml` the key below `fos_user.registration.resetting` (see [documentation](https://symfony.com/doc/current/bundles/FOSUserBundle/configuration_reference.html))
- the values `retry_ttl` and `token_ttl` are configured in seconds (7220 = 2 hours) 

#### Disable password reset 

If you want to disable the password reset, add this your `local.yaml`: 
```yaml
kimai:
    user:
        password_reset: false
```

If you only want to hide the link from the login form but keep the functionality, add this your `local.yaml`: 
```yaml
admin_lte:
    routes:
        adminlte_password_reset: ~
```

## Timesheets (kimai.yaml)

### Descriptions with Markdown

The description for every timesheet entry can be formatted in two different ways, configured with the `markdown_content` setting.

- `false` - simple newlines in the description box will be displayed in the frontend as well (default)
- `true` - description will be rendered with a markdown engine, supporting simple lists and other HTML content

Allowing Markdown in timesheet descriptions is beautiful, but also could be a [security risk](https://github.com/erusev/parsedown/blob/master/README.md#security).
Kimai will only apply the markdown in the user timesheet and not in the admin section as additional security measure.   

### Duration only

Kimai supports two modes for displaying and recording timesheet entries:

- `begin` and `end` time (default)
- `date` and `duration` (the so called `duration_only` mode)

When activating the `duration_only` mode all timesheet tables will only display the `date` and `duration` of all records.
In addition, the "edit timesheet" forms will be changed and instead of displaying the `end` date you will see a field for `duration`.
The `start` date is only visible in these forms when editing an active or starting a new record. 

You can activate the `duration_only` mode by switching the configuration key `kimai.timesheet.duration_only` to `true` in the file [kimai.yaml](../../config/packages/kimai.yaml).

For supported formats while entering the `duration` please see the [timesheet chapter](timesheet.md) 

### Rounding of begin, end and duration for timesheet records

Rounding rules are used to round the begin & end dates and the duration for timesheet records.

1. You can define as many rules as you want ("default" is only an example)
2. Every matching rule will be applied, so be careful with overlapping rules
3. The end_date of timesheet records will be used to match the day (think about entries which are recorded overnight)
4. If you set one of "begin", "end", "duration" to 0 no rounding will be applied for that field and the exact time (including seconds) is used for calculation
5. The values of the rules are minutes (not the minute of an hour), so 5 for "begin" means we round down to the previous multiple of five
6. You can define different rules for different days of the week
7. "begin" will always be rounded to the floor (down) and "end" & "duration" to the ceiling (up)
8. Rounding rules will be applied on stopped timesheet records only, so you might see an un-rounded value for the start time and duration until you stop the record

You can configure your `rounding` rules by changing the configuration file [kimai.yaml](../../config/packages/kimai.yaml).

#### Examples

A simple example to always charge at least 1 hour for weekend work (even if you only worked for 5 minutes) could look like this:

```yaml
kimai:
    timesheet:
        rounding:
            weekend:
                days: ['saturday','sunday']
                begin: 1
                end: 1
                duration: 60
```

A rule which is often used is to round up to a mulitple of 10: 

```yaml
kimai:
    timesheet:
        rounding:
            workdays:
                days: ['monday','tuesday','wednesday','thursday','friday','saturday','sunday']
                begin: 10
                end: 10
                duration: 0
```

### Hourly rates for timesheet records

If you want to apply different hourly rates multiplication `factor` for specific weekdays, you can use this `rates` configuration.

1. You can define as many rules as you want ("workdays" and "weekend" are only examples)
2. Every matching rule will be applied, so be careful with overlapping rules
3. The end_date of timesheet records will be used to match the day (think about entries which are recorded overnight)
4. "days" is an array of weekdays, where the days need to be written in english and in lowercase
5. "factor" will be used as multiplier for the applied hourly rate
6. Rate rules will be applied on stopped timesheet records only, as it can't be calculated before
7. There is no default rule active, by default the users hourly-rate is used for calculation

You can configure the `hourly_rate` rules by changing the configuration file [kimai.yaml](../../config/packages/kimai.yaml).

#### Examples

1. The "workdays" rule will use the default "hourly rate" for each timesheet entry recorded between "monday" to "friday" as a multiplication with 1 will not change the result
2. The "weekend" rule will add 50% to each timesheet entry that will be recorded on "saturdays" or "sundays"

```yaml
kimai:
    timesheet:
        rates:
            workdays:
                days: ['monday','tuesday','wednesday','thursday','friday']
                factor: 1
            weekend:
                days: ['saturday','sunday']
                factor: 1.5
```
