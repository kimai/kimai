# Users

## Roles

There are multiple pre-defined roles in Kimai, which define the ACLs/permissions.

| Role name | extends | Description |
|---|---|---|
| ROLE_CUSTOMER | -  | Currently not used, reserved for future features |
| ROLE_USER | ROLE_CUSTOMER  | Normal user that wants to track working times |
| ROLE_TEAMLEAD | ROLE_USER  | This role manages teams of ROLE_USER (this feature is not yet implemented, but planned for the future) and has further permissions on invoices |
| ROLE_ADMIN | ROLE_TEAMLEAD | Admins can do almost everything in Kimai, except some user specific tasks |
| ROLE_SUPER_ADMIN | ROLE_ADMIN  | Evey Super-Admin can do anything Kimai |

### Permissions

The permission system is configurable through a configuration file. You can find further information in the [permissions](permissions.md) chapter. 

## Login

- User can login with username or email
- If you activate the `Remember me` option, you can use use the most common functions within the next days without a new login

Kimai uses the FOSUserBundle for security related tasks like user management. 
Its configuration can be found in [fos_user.yaml](../../config/packages/fos_user.yaml).

### Remember me login

If you have chosen to login with the `Remember me` option, your login will be extended to one week (default value).
After coming back and being remembered you have access to all the following features:
 
- view your own timesheet
- start and stop new records
- edit existing records

If you are an administrator, you will see all your allowed options in the menu, but will be redirected to the login 
form when you try to access them. This is a security feature to prevent abuse in case you forgot to logout in public 
environments.

The default period for the `Remember me` option can be changed in the config file [security.yaml](../../config/packages/security.yaml). 

## User registration

User registration with instant approval is activated by default, so users can register and will be able to login and start time-tracking instantly.

If you want to disable the registration or enable email verification, read further below under _Configurations_.

### Email activation
If you want your new users to use [email](emails.md) based activation add this to your `local.yaml`:

```yaml
fos_user:
    registration:
        confirmation:
            enabled: true
```

### Disable user registration 

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

## Password reset

The reset password function is enabled by default, but you need to activate [email](emails.md) support if you want to use it.

If you want to deactivate this feature you have to change the following configs:

- in `config/packages/admin_lte.yaml` remove the route alias `admin_lte.routes.adminlte_password_reset` (this will remove the link from the login form)
- in `config/routes.yaml` remove the block `fos_user_resetting` (this will deactivate the functionality)

If you want to configure the behaviour (like the allowed time between multiple retries) then configure the settings:

- in `config/packages/fos_user.yaml` the key below `fos_user.registration.resetting` (see [documentation](https://symfony.com/doc/current/bundles/FOSUserBundle/configuration_reference.html))
- the values `retry_ttl` and `token_ttl` are configured in seconds (7220 = 2 hours) 

### Disable password reset 

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

