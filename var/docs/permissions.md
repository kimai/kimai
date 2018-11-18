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
| ROLE_CUSTOMER | -  | -  |
| ROLE_USER | x  | -  |
| ROLE_TEAMLEAD | x  | -  |
| ROLE_ADMIN | x | -  |
| ROLE_SUPER_ADMIN | x  | -  |

## Configure permissions

Even though the permissions were safely crafted and matched to the pre-defined user-roles, we know that there are many companies  
that need a different combination of allowed actions. 

XXXXX TODO XXXXX  

```yaml
kimai:
    permissions:
```
