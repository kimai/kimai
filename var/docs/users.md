# Users

There are multiple pre-defined roles in Kimai, which define the ACLs. A user can only inherit one role, where the roles extend each user.

## Roles & Permissions

| Role name | extends | Gives permission for |
|---|---|---|
| ROLE_CUSTOMER | -  | Currently has no permissions, but was reserved for future functionality  |
| ROLE_USER | ROLE_CUSTOMER  | Time-tracking  |
| ROLE_TEAMLEAD | ROLE_USER  | All of the above, plus: editing other users timesheets  |
| ROLE_ADMIN | ROLE_TEAMLEAD | All of the above, plus: editing customers, editing projects, editing activities |
| ROLE_SUPER_ADMIN | ROLE_ADMIN  | All of the above, plus: editing users  |

# Login & Authentication

- User can login with their username or email
- If you activate the `Remember me` option, you can use use the most common functions within the next days without a new login

## Remember me login

If you have chosen to login with the `Remember me` option, your login will be extended to one week (default value).
After coming back and being remembered you have access to all the following features: 
- view your own timesheet
- start and stop new records
- edit existing records

If you are an administrator, you will see all your allowed options in the menu, but will be redirected to the login 
form when you try to access them. This is a security feature to prevent abuse in case you forgot to logout in public 
environments.

The default period for the `Remember me` option can be changed in the config file [security.yaml](config/packages/security.yaml). 
