# Timesheet

User manual on the timesheet tables and actions.

Kimai 2 provides also a [calendar view](calendar.md), which displays your timesheet entries in an easy readable format.

## Starting records

You can start new timesheet records like so:
- Click the **redo** button in the "last activities" dropdown in the upper toolbar
- Click the **redo** button from one of the activities in your timesheet
- Start a completely new activity, by clicking the big **play** button in the toolbar

## Stopping records

You can stop timesheet records like so:
- Click the **stop** button in the "active records" dropdown in the upper toolbar
- Click the **stop** button from one of the running activities in your timesheet
- Edit a running activity, add an end date and save

## Duration only mode

Kimai supports two modes for displaying and recording timesheet entries:

- `begin` and `end` time (default)
- `date` and `duration` (the so called `duration_only` mode)

When activating the `duration_only` mode all timesheet tables will only display the `date` and `duration` of all records.
In addition, the "edit timesheet" forms will be changed and instead of displaying the `end` date you will see a field for `duration`.
The `start` date is only visible in these forms when editing an active or starting a new record. 

You can activate the `duration_only` mode by switching the configuration key `kimai.timesheet.duration_only` to `true` in your `local.yaml`:

```yaml
kimai:
    timesheet:
        duration_only: true
```

### Duration format

The `duration` field supports entering data in the following formats:

| Name | Format | Description | Examples |
|---|---|---|---|
| Colons | {hours}:{minutes}[:{seconds}] | Seconds are optional, overflow is supported for every field | `2:27` = 2 Hours, 27 Minutes / `3:143:13` = 5 Hours, 23 Minutes, 13 Seconds|
| Natural | {hours}h{minutes}m[{seconds}s] | Seconds are optional, overflow is supported for every field | `2h27m` = 2 Hours, 27 Minutes / `3h143m13s` = 5 Hours, 23 Minutes, 13 Seconds |
| Seconds | {seconds} | | `3600` = 1 Hour / `8820` = 2 Hours, 27 Minutes |

Please note: if time rounding is activated (which is the default behaviour), then your entered seconds might be removed after submitting the form.

## Limit active entries

To limit the amount of active entries each user can have, the configuration `active_entries` can be changed:

```yaml
kimai:
    timesheet:
        active_entries:
            soft_limit: 1
            hard_limit: 3
```

The `soft_limit` is used as theme setting (formerly "kimai.theme.active_warning") to display a warning if the user has at least X active recordings.

The `hard_limit` is used to detect how many active records are allowed per user (by default 3 active time-records are allowed). 
If `hard_limit` is 1, the active record is automatically stopped when a new one is started.
When `hard_limit` is greater than 1 and as soon as the limit is reached, the user has to manually stop at least one active 
entry (an error message is shown, indicating why it is not possible to start another one).
 
## Descriptions with Markdown

The description for every timesheet entry can be formatted in two different ways, configured with the `markdown_content` setting.

- `false` - simple newlines in the description box will be displayed in the frontend as well (default)
- `true` - description will be rendered with a markdown engine, supporting simple lists and other HTML content

Allowing Markdown in timesheet descriptions is beautiful, but also could be a [security risk](https://github.com/erusev/parsedown/blob/master/README.md#security).
Kimai will only apply the markdown in the user timesheet and not in the admin section as additional security measure.   

## Rounding of begin, end and duration for timesheet records

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

## Rate calculation

There are two rate types:

- __Fixed rate__: the value will be used to set the records rate, no matter how long the duration is
- __Hourly rate__: will be used to calculate the records rate by multiplying it with the duration (see below)

If any of the above is set to 0, the records rate will be set to 0.

While calculating the rate of a timesheet entry, the first setting that is found will be used (in order of appearance):

- Timesheet fixed rate
- Activity fixed rate
- Project fixed rate
- Customer fixed rate
- Timesheet hourly rate
- Activity hourly rate
- Project hourly rate
- Customer hourly rate
- Users hourly rate

If neither a fixed nor a hourly rate can be found, the users rate will be used to calculate the records rate.
If that is the case and if the users rate is not set or equals 0, the records rate will be set to 0.

The calculation is based on the following formula:

- __Fixed rate__: `$fixedRate`
- __Hourly rate__: `$hourlyRate * ($durationInSeconds / 3600) * $factor`

Please see below to see how you can apply configurable multiplying factors based on day and time.

### Rate multiplier for specific weekdays

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
