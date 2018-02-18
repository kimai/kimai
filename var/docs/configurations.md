# Configurations

There are several configurations that can be configured with the yaml files in `config/packages/*.yaml

## Duration only

Kimai supports two modes for displaying and recording timesheet entries:

- `begin` and `end` time (default)
- `date` and `duration` (the so called `duration_only` mode)

When activating the `duration_only` mode all timesheet tables will only display the `date` and `duration` of all records.
In addition, the "edit timesheet" forms will be changed and instead of displaying the `end` date you will see a field for `duration`.
The `start` date is only visible in these forms when editing an active or starting a new record. 

You can activate the `duration_only` mode by switching the configuration key `kimai.timesheet.duration_only` to `true` in the file [kimai.yaml](../../config/packages/kimai.yaml).

For supported formats while entering the `duration` please see the [timesheet chapter](timesheet.md) 

## Remember me login

The default period for the `Remember me` option can be changed in the config file [security.yaml](../../config/packages/security.yaml). 

## Timesheet records - rounding of begin, end and duration

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

### Examples

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

A rule which is often used is to round to a mulitple of 10: 

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

## Timesheet records - hourly rates

If you want to apply different hourly rates multiplication `factor` for specific weekdays, you can use this `rates` configuration.

1. You can define as many rules as you want ("workdays" and "weekend" are only examples)
2. Every matching rule will be applied, so be careful with overlapping rules
3. The end_date of timesheet records will be used to match the day (think about entries which are recorded overnight)
4. "days" is an array of weekdays, where the days need to be written in english and in lowercase
5. "factor" will be used as multiplier for the applied hourly rate
6. Rate rules will be applied on stopped timesheet records only, as it can't be calculated before
7. There is no default rule active, by default the users hourly-rate is used for calculation

You can configure the `hourly_rate` rules by changing the configuration file [kimai.yaml](../../config/packages/kimai.yaml).

### Examples

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
