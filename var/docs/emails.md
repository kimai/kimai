# Emails

Kimai uses the [Swift MailerBundle](https://symfony.com/doc/current/email.html) for sending emails. 
Please read their documentation, there are a lot of possible [configuration settings](https://symfony.com/doc/current/reference/configuration/swiftmailer.html) that you might want to adapt to your needs.

If not otherwise noted, all emails will be sent instantly (unless spooling is activated in [swiftmailer.yaml](../../config/packages/swiftmailer.yaml)).

## Activating email

You have to adapt two settings in your `.env` [configuration file](configurations.md):

- `MAILER_URL` - your smtp connection details for sending emails
- `MAILER_FROM` - an application wide "from" address for all emails

## All existing emails

The following emails will be sent by Kimai:

### Security related emails

If you want to change the content of the emails, please have a look at the [FOSUserBundle config](../../config/packages/fos_user.yaml) 
and its [documentation](https://symfony.com/doc/current/bundles/FOSUserBundle/emails.html).

- Password reset 
- Account approval (only if activated, see [User docu](users.md))
