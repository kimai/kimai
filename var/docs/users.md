# Users

There are multiple pre-defined roles in Kimai, which define the ACLs. A user can only inherit one role, where the roles extend each user.

## Roles

| Role name | extends | Gives permission for |
|---|---|---|
| ROLE_CUSTOMER | -  | Currently has no permissions, but was reserved for future functionality  |
| ROLE_USER | ROLE_CUSTOMER  | Time-tracking  |
| ROLE_TEAMLEAD | ROLE_USER  | All of the above, plus: editing other users timesheets  |
| ROLE_ADMIN | ROLE_TEAMLEAD | All of the above, plus: editing customers, editing projects, editing activities |
| ROLE_SUPER_ADMIN | ROLE_ADMIN  | All of the above, plus: editing users  |

