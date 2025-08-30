# Upgrading Kimai - Version 3.x

_Make sure to create a backup before you start!_ 

Read the [updates documentation](https://www.kimai.org/documentation/updates.html) to find out how you can upgrade your Kimai installation to the latest stable release.

Check below if there are more version specific steps required, which need to be executed after the normal update process.
Perform EACH version specific task between your version and the new one, otherwise you risk data inconsistency or a broken installation.

## 3.0

**!! This release requires minimum PHP version 8.4 !!**

### Developer

Removed translations:
- `action.edit`: use `edit` instead
- `my.profile`: use `user_profile` instead
- `stats.userAmountToday`: use `` instead
- `stats.userAmountWeek`: use `` instead
- `stats.userAmountMonth`: use `` instead
- `stats.userAmountYear`: use `` instead
- `stats.userAmountTotal`: use `` instead
- `update_multiple`