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
