# Upgrading Kimai - Version 3.x

_Make sure to create a backup before you start!_ 

Read the [updates documentation](https://www.kimai.org/documentation/updates.html) to find out how you can upgrade your Kimai installation to the latest stable release.

Check below if there are more version specific steps required, which need to be executed after the normal update process.
Perform EACH version specific task between your version and the new one, otherwise you risk data inconsistency or a broken installation.

## 3.0

**!! This release requires minimum PHP version 8.4 !!**

### Rename .env

Rename your file `.env` to `.env.local` or even better: move all variables to your webserver/container environment.

### Developer

Do not use method chaining: all fluent interface, especially in Entities, are no longer supported.

- Require PHP 8.4
- Bump to Symfony 7.4
- Removed old API token `X-AUTH-USER` and `X-AUTH-TOKEN`
- Removed `TimesheetConstraint` - use a normal `Constraint` as base class and attach the `#[App\Validator\Attribute\TimesheetConstraint]` attribute 
- Removed `ProjectConstraint` - use a `FormExtension` and attach your custom constraints 
- Interface `MetaTableTypeInterface` has new methods: `getSection()`, `setSection()`
- Interface `ExportRendererInterface` has new methods: `getType()`, `isInternal()`
- Interface `ExportableItem` has new methods: `getTags()`, `getBreak()` 
- Removed and renamed translations, most important `action.edit` => `edit`, `my.profile` => `user_profile`
- Removed `User::isExportDecimal()`
  - Use duration format `HH:mm` in default PDF exports
- Replace Twig `AppVariable` with custom implementation
  - You need to adjust your templates if you access anything else then `app.locale`, `app.user`. `app.current_route`. 
  - Most often used: 
    - Replace `app.request.locale` with `app.locale`
    - Replace `app.request.attributes.get('_route')` with `app.current_route`
