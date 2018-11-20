# Permissions

Kimai 2 provides a flexible permissions system, which can be adapted though your [local.yaml](configurations.md) config 
file and that is based on [user roles](users.md).

## Understanding permission structure

Before you learn to configure the permission system, you have to understand the three involved config types:

1. `Permission sets` define a re-usable name for a list of "permission names"
2. `Permission maps` will apply a list of "permission sets" to a "user role"
3. `Permissions` apply a list of "permission names" to a "user role" 

An example and its explanation:

```yaml
    permissions:
        sets:
            ACTIVITY: [view_activity,create_activity]
            TIMESHEET: [view_own_timesheet,start_own_timesheet]
        maps:
            ROLE_USER: [TIMESHEET]
            ROLE_ADMIN: [TIMESHEET,ACTIVITY]
        roles:
            ROLE_USER: [my_profile]
            ROLE_ADMIN: [my_profile,start_other_timesheet]
```

In `sets` we define the two `permissions sets` names "ACTIVITY" and "TIMESHEET". In `maps` we apply the `permissions set` to the 
called "TIMESHEET" to the user-role "ROLE_USER" and the two `permissions set` called "TIMESHEET" and "ACTIVITY" to the user-role "ROLE_ADMIN".

At this step the role have the following permissions:

- `ROLE_USER`: view_own_timesheet,start_own_timesheet
- `ROLE_ADMIN`: view_own_timesheet,start_own_timesheet,view_activity,create_activity

As last step, the list of `permission names` will be added to the list of calculated permissions.
So we add the permission "my_profile" to the user-role "ROLE_USER" and the two permissions "my_profile" and "start_other_timesheet" to the user-role "ROLE_ADMIN".

At the end the system calculated the final list of permissions:  

- `ROLE_USER`: view_own_timesheet,start_own_timesheet,my_profile
- `ROLE_ADMIN`: view_own_timesheet,start_own_timesheet,view_activity,create_activity,my_profile,start_other_timesheet

## Existing permissions

| Permission name | Set name | Description |
|---|---|---|
| view_activity | ACTIVITIES  | -  |
| create_activity | ACTIVITIES  | -  |
| edit_activity | ACTIVITIES  | -  |
| delete_activity | ACTIVITIES  | -  |
| view_project | PROJECTS  | -  |
| create_project | PROJECTS  | -  |
| edit_project | PROJECTS  | -  |
| delete_project | PROJECTS  | -  |
| view_customer | CUSTOMERS  | -  |
| create_customer | CUSTOMERS  | -  |
| edit_customer | CUSTOMERS  | -  |
| delete_customer | CUSTOMERS  | -  |
| view_invoice | INVOICE  | -  |
| create_invoice | INVOICE  | -  |
| view_invoice_template | INVOICE_TEMPLATE  | -  |
| create_invoice_template | INVOICE_TEMPLATE  | -  |
| edit_invoice_template | INVOICE_TEMPLATE  | -  |
| delete_invoice_template | INVOICE_TEMPLATE  | -  |
| view_own_timesheet | TIMESHEET  | -  |
| start_own_timesheet | TIMESHEET  | -  |
| stop_own_timesheet | TIMESHEET  | -  |
| create_own_timesheet | TIMESHEET  | -  |
| edit_own_timesheet | TIMESHEET  | -  |
| export_own_timesheet | TIMESHEET  | -  |
| delete_own_timesheet | TIMESHEET  | -  |
| view_other_timesheet | TIMESHEET_OTHER  | -  |
| start_other_timesheet | TIMESHEET_OTHER  | -  |
| stop_other_timesheet | TIMESHEET_OTHER  | -  |
| create_other_timesheet | TIMESHEET_OTHER  | -  |
| edit_other_timesheet | TIMESHEET_OTHER  | -  |
| delete_other_timesheet | TIMESHEET_OTHER  | -  |
| view_rate_own_timesheet | RATE | -  |
| edit_rate_own_timesheet | RATE | -  |
| view_rate_other_timesheet | RATE_OTHER | -  |
| edit_rate_other_timesheet | RATE_OTHER | -  |
| view_own_profile | PROFILE  | -  |
| edit_own_profile | PROFILE  | -  |
| delete_own_profile | PROFILE  | -  |
| password_own_profile | PROFILE  | -  |
| roles_own_profile | PROFILE  | -  |
| preferences_own_profile | PROFILE  | -  |
| api-token_own_profile | PROFILE  | -  |
| view_other_profile | PROFILE_OTHER  | -  |
| edit_other_profile | PROFILE_OTHER  | -  |
| delete_other_profile | PROFILE_OTHER  | -  |
| password_other_profile | PROFILE_OTHER  | -  |
| roles_other_profile | PROFILE_OTHER  | -  |
| preferences_other_profile | PROFILE_OTHER  | -  |
| api-token_other_profile | PROFILE_OTHER  | -  |
| view_user | USER  | -  |
| create_user | USER  | -  |
| delete_user | USER  | -  |

## Configure permissions

Knowing that many companies need a different combination of allowed permissions than the default ones, you might also 
want to change the pre-configured permission.

You can do that in your [local.yaml](configurations.md). Define the permissions like in the above mentioned example, 
you might start by copying the default permissions from kimai.yaml.
