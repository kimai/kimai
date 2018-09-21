# Timesheet

User manual on the timesheet tables and actions.

Kimai 2 provides also a [calendar view](calendar.md), which displays your timesheet entries in an easy readable format.

## Duration only

When the `duration_only` mode is activated, you will only see the `date` and `duration` fields (see [configurations chapter](configurations.md)).

The `duration` field supports entering data in the following formats:

| Name | Format | Description | Examples |
|---|---|---|---|
| Colons | {hours}:{minutes}[:{seconds}] | Seconds are optional, overflow is supported for every field | `2:27` = 2 Hours, 27 Minutes / `3:143:13` = 5 Hours, 23 Minutes, 13 Seconds|
| Natural | {hours}h{minutes}m[{seconds}s] | Seconds are optional, overflow is supported for every field | `2h27m` = 2 Hours, 27 Minutes / `3h143m13s` = 5 Hours, 23 Minutes, 13 Seconds |
| Seconds | {seconds} | | `3600` = 1 Hour / `8820` = 2 Hours, 27 Minutes |

Please note: if time rounding is activated (which is the default behaviour), then your entered seconds might be removed after submitting the form.

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
In that case and the users rate is not set or equals 0, the records rate will be set to 0.

The calculation is based on the following formular:

- __Fixed rate__: `$fixedRate`
- __Hourly rate__: `$hourlyRate * ($durationInSeconds / 3600) * $factor`

Please see also the configuration chapter about [hourly rates for timesheet records](configurations.md) to see how you 
can apply configurable multiplying factors based on day and time.
